<?php
/**
 * Plugin Name: Associate Activity Contact Sync
 * Description: Creates Form 12 contact entries from Form 1 "New Connection" submissions and auto-fills existing contact details in Form 1.
 * Version: 1.2.0
 * Author: Niva10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'NIVA_Associate_Activity_Contact_Sync' ) ) {

	class NIVA_Associate_Activity_Contact_Sync {

		// Form IDs
		const ACTIVITY_FORM_ID      = 1;
		const CONTACT_CHILD_FORM_ID = 12;

		// Form 1 fields
		const FIELD_REL_STATUS                   = 1;
		const FIELD_EXISTING_CONTACT             = 25;
		const FIELD_CLIENT_CODE                  = 27; // Used for New Connection submissions
		const FIELD_EXISTING_CONTACT_CLIENT_CODE = 28; // Auto-filled from Existing Contact
		const FIELD_RELATIONSHIP                 = 8;
		const FIELD_ORG_TYPE                     = 9;

		// Form 12 fields
		const CHILD_CONTACT_CODE = 1;
		const CHILD_RELATIONSHIP = 3;
		const CHILD_ORG_TYPE     = 4;

		// Expected Form 1 Relationship Status value
		const STATUS_NEW_CONNECTION = 'New Connection';

		// AJAX
		const AJAX_ACTION = 'niva_get_existing_contact';
		const AJAX_NONCE  = 'niva_existing_contact_nonce';

		public function __construct() {
			add_action( 'gform_after_submission_' . self::ACTIVITY_FORM_ID, array( $this, 'handle_new_connection_submission' ), 10, 2 );
			add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ajax_get_existing_contact' ) );
			add_action( 'gform_enqueue_scripts_' . self::ACTIVITY_FORM_ID, array( $this, 'enqueue_autofill_script' ), 10, 2 );
		}

		/**
		 * On Form 1 submit, create a Form 12 contact entry if Relationship Status is "New Connection".
		 */
		public function handle_new_connection_submission( $entry, $form ) {
			if ( ! class_exists( 'GFAPI' ) ) {
				$this->log( 'GFAPI not available.' );
				return;
			}

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				$this->log( 'Submission skipped because user is not logged in.' );
				return;
			}

			$relationship_status = $this->normalize_value( rgar( $entry, (string) self::FIELD_REL_STATUS ) );

			if ( $relationship_status !== self::STATUS_NEW_CONNECTION ) {
				return;
			}

			$client_code       = $this->normalize_value( rgar( $entry, (string) self::FIELD_CLIENT_CODE ) );
			$relationship      = $this->normalize_value( rgar( $entry, (string) self::FIELD_RELATIONSHIP ) );
			$organization_type = $this->normalize_value( rgar( $entry, (string) self::FIELD_ORG_TYPE ) );

			// Do not create blank contacts.
			if ( $client_code === '' && $relationship === '' && $organization_type === '' ) {
				$this->log( 'Skipped creating contact because all mapped values were empty.' );
				return;
			}

			// Duplicate check scoped to this user only.
			if ( $this->child_contact_exists_for_user( $user_id, $client_code, $relationship, $organization_type ) ) {
				$this->log(
					sprintf(
						'Skipped duplicate contact for user %d: %s | %s | %s',
						$user_id,
						$client_code,
						$relationship,
						$organization_type
					)
				);
				return;
			}

			$child_entry = array(
				'form_id'                          => self::CONTACT_CHILD_FORM_ID,
				'created_by'                       => $user_id,
				(string) self::CHILD_CONTACT_CODE => $client_code,
				(string) self::CHILD_RELATIONSHIP => $relationship,
				(string) self::CHILD_ORG_TYPE     => $organization_type,
			);

			$child_entry_id = GFAPI::add_entry( $child_entry );

			if ( is_wp_error( $child_entry_id ) ) {
				$this->log( 'Failed creating Form 12 entry: ' . $child_entry_id->get_error_message() );
				return;
			}

			$this->log(
				sprintf(
					'Created Form 12 contact entry %d for user %d.',
					(int) $child_entry_id,
					(int) $user_id
				)
			);
		}

		/**
		 * AJAX endpoint used to auto-fill Form 1 fields from a selected Form 12 contact.
		 */
		public function ajax_get_existing_contact() {
			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => 'Not logged in.' ), 403 );
			}

			if ( ! class_exists( 'GFAPI' ) ) {
				wp_send_json_error( array( 'message' => 'Gravity Forms API unavailable.' ), 500 );
			}

			check_ajax_referer( self::AJAX_NONCE, 'nonce' );

			$user_id  = get_current_user_id();
			$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;

			if ( ! $entry_id ) {
				wp_send_json_error( array( 'message' => 'Missing entry ID.' ), 400 );
			}

			$entry = GFAPI::get_entry( $entry_id );

			if ( is_wp_error( $entry ) || empty( $entry ) ) {
				wp_send_json_error( array( 'message' => 'Entry not found.' ), 404 );
			}

			if ( (int) rgar( $entry, 'form_id' ) !== self::CONTACT_CHILD_FORM_ID ) {
				wp_send_json_error( array( 'message' => 'Invalid contact entry.' ), 400 );
			}

			// Only allow users to fetch their own contacts.
			if ( (int) rgar( $entry, 'created_by' ) !== (int) $user_id ) {
				wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
			}

			wp_send_json_success(
				array(
					'client_code'       => (string) rgar( $entry, (string) self::CHILD_CONTACT_CODE ),
					'relationship'      => (string) rgar( $entry, (string) self::CHILD_RELATIONSHIP ),
					'organization_type' => (string) rgar( $entry, (string) self::CHILD_ORG_TYPE ),
				)
			);
		}

		/**
		 * Output JS for auto-filling Form 1 when Existing Contact changes.
		 * Field 27 is intentionally NOT modified here.
		 */
		public function enqueue_autofill_script( $form, $is_ajax ) {
			$ajax_url = admin_url( 'admin-ajax.php' );
			$nonce    = wp_create_nonce( self::AJAX_NONCE );
			$form_id  = (int) self::ACTIVITY_FORM_ID;
			?>
			<script>
			(function() {
				function initNivaAutofill() {
					const formWrapper = document.getElementById('gform_wrapper_<?php echo $form_id; ?>');
					if (!formWrapper) return;

					const select = formWrapper.querySelector('#input_<?php echo $form_id; ?>_<?php echo (int) self::FIELD_EXISTING_CONTACT; ?>');
					if (!select || select.dataset.nivaBound === '1') return;

					select.dataset.nivaBound = '1';

					function setTextValue(fieldId, value) {
						const input = formWrapper.querySelector('#input_<?php echo $form_id; ?>_' + fieldId);
						if (!input) return;
						input.value = value || '';
						input.dispatchEvent(new Event('input', { bubbles: true }));
						input.dispatchEvent(new Event('change', { bubbles: true }));
					}

					function setRadioValue(fieldId, value) {
						const radios = formWrapper.querySelectorAll('input[name="input_' + fieldId + '"]');
						if (!radios.length) return;

						radios.forEach(function(radio) {
							radio.checked = (radio.value === value);
							if (radio.checked) {
								radio.dispatchEvent(new Event('change', { bubbles: true }));
							}
						});
					}

					function clearRadioValue(fieldId) {
						const radios = formWrapper.querySelectorAll('input[name="input_' + fieldId + '"]');
						radios.forEach(function(radio) {
							radio.checked = false;
						});
					}

					function clearFields() {
						setTextValue(<?php echo (int) self::FIELD_EXISTING_CONTACT_CLIENT_CODE; ?>, '');
						clearRadioValue(<?php echo (int) self::FIELD_RELATIONSHIP; ?>);
						clearRadioValue(<?php echo (int) self::FIELD_ORG_TYPE; ?>);
					}

					function fetchContact(entryId) {
						const formData = new FormData();
						formData.append('action', '<?php echo esc_js( self::AJAX_ACTION ); ?>');
						formData.append('nonce', '<?php echo esc_js( $nonce ); ?>');
						formData.append('entry_id', entryId);

						fetch('<?php echo esc_url( $ajax_url ); ?>', {
							method: 'POST',
							credentials: 'same-origin',
							body: formData
						})
						.then(function(response) {
							return response.json();
						})
						.then(function(data) {
							if (!data || !data.success || !data.data) {
								return;
							}

							setTextValue(<?php echo (int) self::FIELD_EXISTING_CONTACT_CLIENT_CODE; ?>, data.data.client_code || '');
							setRadioValue(<?php echo (int) self::FIELD_RELATIONSHIP; ?>, data.data.relationship || '');
							setRadioValue(<?php echo (int) self::FIELD_ORG_TYPE; ?>, data.data.organization_type || '');
						})
						.catch(function() {
							// Fail silently on the front end.
						});
					}

					select.addEventListener('change', function() {
						const entryId = this.value;

						if (!entryId) {
							clearFields();
							return;
						}

						fetchContact(entryId);
					});
				}

				document.addEventListener('DOMContentLoaded', initNivaAutofill);

				document.addEventListener('gform_post_render', function(event) {
					if (event && event.detail && parseInt(event.detail.formId, 10) === <?php echo $form_id; ?>) {
						initNivaAutofill();
					} else {
						initNivaAutofill();
					}
				});
			})();
			</script>
			<?php
		}

		/**
		 * Check if a matching Form 12 contact already exists for the current user.
		 */
		private function child_contact_exists_for_user( $user_id, $client_code, $relationship, $organization_type ) {
			$entries = GFAPI::get_entries(
				self::CONTACT_CHILD_FORM_ID,
				array(
					'status'        => 'active',
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key'   => 'created_by',
							'value' => $user_id,
						),
						array(
							'key'   => (string) self::CHILD_CONTACT_CODE,
							'value' => $client_code,
						),
						array(
							'key'   => (string) self::CHILD_RELATIONSHIP,
							'value' => $relationship,
						),
						array(
							'key'   => (string) self::CHILD_ORG_TYPE,
							'value' => $organization_type,
						),
					),
				),
				null,
				array(
					'offset'    => 0,
					'page_size' => 1,
				)
			);

			return ! is_wp_error( $entries ) && ! empty( $entries );
		}

		/**
		 * Normalize values for reliable comparison/storage.
		 */
		private function normalize_value( $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
			}

			return trim( wp_strip_all_tags( (string) $value ) );
		}

		/**
		 * Small debug logger.
		 */
		private function log( $message ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Associate Activity Contact Sync] ' . $message );
			}
		}
	}

	new NIVA_Associate_Activity_Contact_Sync();
}