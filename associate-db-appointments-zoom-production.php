<?php
/**
 * Plugin Name: AssociateDB Meetings + Zoom
 * Description: Front-end meeting dashboard and listing for Gravity Forms + GP Bookings, with Zoom meeting creation for video-call bookings.
 * Version: 0.3.3
 * Author: OpenAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AssociateDB_Appointments {

	private const VERSION = '0.3.3';
	private const FORM_ID = 9;
	private const BOOKING_PRODUCT_FIELD_ID = 3;
	private const MEETING_TYPE_FIELD_ID = 10;
	private const CONSULTANT_FIELD_ID = 11;
	private const FORMAT_FIELD_ID = 7;
	private const HELP_WITH_FIELD_ID = 6;
	private const COMPANY_FIELD_ID = 8;
	private const DEFAULT_CONSULTANT_NAME = 'Norm Dupas';
	private const DEFAULT_ZOOM_HOST = 'norm@niva10.com';
	private const VIDEO_FORMAT_VALUE = 'Video call';
	private const APPOINTMENTS_PAGE_URL = '/associate/my-meetings/';
	private const BOOK_A_MEETING_URL = '/associate/book-a-meeting/';
	private const OPTION_KEY = 'assoc_db_appointments_settings';
	private const META_ZOOM_MEETING_ID = 'assoc_zoom_meeting_id';
	private const META_ZOOM_JOIN_URL = 'assoc_zoom_join_url';
	private const META_ZOOM_START_URL = 'assoc_zoom_start_url';
	private const META_ZOOM_LAST_ERROR = 'assoc_zoom_last_error';

		public static function init(): void {
			add_shortcode( 'assoc_next_appointment', [ __CLASS__, 'render_next_appointment_shortcode' ] );
			add_shortcode( 'assoc_my_appointments', [ __CLASS__, 'render_my_appointments_shortcode' ] );
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );

		add_action( 'admin_menu', [ __CLASS__, 'register_settings_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );

		add_action( 'gpb_after_booking_created', [ __CLASS__, 'maybe_create_zoom_meeting' ], 20, 4 );
			add_action( 'gpb_rescheduled', [ __CLASS__, 'maybe_reschedule_zoom_meeting' ], 20, 3 );
			add_action( 'gpb_status_transitioned', [ __CLASS__, 'maybe_handle_zoom_status_transition' ], 20, 3 );
			add_action( 'gform_after_submission_' . self::FORM_ID, [ __CLASS__, 'queue_zoom_creation_after_submission' ], 30, 2 );
			add_action( 'assoc_db_process_zoom_for_entry', [ __CLASS__, 'process_zoom_for_entry' ], 10, 1 );
			add_filter( 'gform_custom_merge_tags', [ __CLASS__, 'register_custom_merge_tags' ], 10, 4 );
			add_filter( 'gform_replace_merge_tags', [ __CLASS__, 'replace_custom_merge_tags' ], 10, 7 );
		}

	public static function register_assets(): void {
		$css = '
		.assoc-appointments{display:grid;gap:16px}
		.assoc-appointment-card{border:1px solid #e5e7eb;border-radius:16px;padding:18px;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.04)}
		.assoc-appointment-title{margin:0 0 6px;font-size:1.05rem;font-weight:700}
		.assoc-appointment-meta{margin:4px 0;color:#4b5563}
		.assoc-appointment-status{display:inline-block;margin-top:10px;padding:4px 10px;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:.85rem;font-weight:600}
		.assoc-appointment-actions{margin-top:14px;display:flex;gap:10px;flex-wrap:wrap}
		.assoc-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:600;border:1px solid #d1d5db}
		.assoc-btn-primary{background:#ef626c;border-color:#ef626c;color:#fff}
		.assoc-btn-secondary{background:#fff;color:#111827}
		.assoc-empty{padding:18px;border:1px dashed #d1d5db;border-radius:16px;background:#fff}
		.assoc-appointments-list{display:grid;gap:14px}
		.assoc-footer-link{margin-top:12px;display:inline-block;font-weight:600}
		.assoc-appointment-summary{margin-top:8px;font-size:.95rem;color:#374151}
		.assoc-zoom-note{margin-top:10px;color:#047857;font-weight:600;font-size:.92rem}
		';

		wp_register_style( 'associate-db-appointments', false, [], self::VERSION );
		wp_enqueue_style( 'associate-db-appointments' );
		wp_add_inline_style( 'associate-db-appointments', $css );
	}

	public static function register_settings_page(): void {
		add_options_page(
			'AssociateDB Meetings',
			'AssociateDB Meetings',
			'manage_options',
			'assoc-db-appointments',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	public static function register_settings(): void {
		register_setting(
			'assoc_db_appointments_group',
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
				'default'           => self::get_default_settings(),
			]
		);
	}

	public static function sanitize_settings( $input ): array {
		$defaults = self::get_default_settings();
		$input    = is_array( $input ) ? $input : [];

		return [
			'zoom_account_id' => sanitize_text_field( $input['zoom_account_id'] ?? $defaults['zoom_account_id'] ),
			'zoom_client_id'  => sanitize_text_field( $input['zoom_client_id'] ?? $defaults['zoom_client_id'] ),
			'zoom_client_secret' => sanitize_text_field( $input['zoom_client_secret'] ?? $defaults['zoom_client_secret'] ),
			'zoom_host_user'  => sanitize_text_field( $input['zoom_host_user'] ?? $defaults['zoom_host_user'] ),
		];
	}

	private static function get_default_settings(): array {
		return [
			'zoom_account_id'    => '',
			'zoom_client_id'     => '',
			'zoom_client_secret' => '',
			'zoom_host_user'     => self::DEFAULT_ZOOM_HOST,
		];
	}

	private static function get_settings(): array {
		$settings = get_option( self::OPTION_KEY, [] );
		return wp_parse_args( is_array( $settings ) ? $settings : [], self::get_default_settings() );
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1>AssociateDB Meetings</h1>
			<p>Configure Zoom Server-to-Server OAuth credentials for automatic meeting creation on video-call bookings.</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'assoc_db_appointments_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="assoc_zoom_account_id">Zoom Account ID</label></th>
						<td><input name="<?php echo esc_attr( self::OPTION_KEY ); ?>[zoom_account_id]" type="text" id="assoc_zoom_account_id" value="<?php echo esc_attr( $settings['zoom_account_id'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="assoc_zoom_client_id">Zoom Client ID</label></th>
						<td><input name="<?php echo esc_attr( self::OPTION_KEY ); ?>[zoom_client_id]" type="text" id="assoc_zoom_client_id" value="<?php echo esc_attr( $settings['zoom_client_id'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="assoc_zoom_client_secret">Zoom Client Secret</label></th>
						<td><input name="<?php echo esc_attr( self::OPTION_KEY ); ?>[zoom_client_secret]" type="password" id="assoc_zoom_client_secret" value="<?php echo esc_attr( $settings['zoom_client_secret'] ); ?>" class="regular-text" autocomplete="off"></td>
					</tr>
					<tr>
						<th scope="row"><label for="assoc_zoom_host_user">Zoom Host User</label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION_KEY ); ?>[zoom_host_user]" type="text" id="assoc_zoom_host_user" value="<?php echo esc_attr( $settings['zoom_host_user'] ); ?>" class="regular-text">
							<p class="description">Use Norm's Zoom user email or Zoom user ID.</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<hr>
			<h2>Debug</h2>
			<p><strong>Scope reminder:</strong> your Zoom Server-to-Server app should have meeting create, read, update, and delete permissions for the host account.</p>
			<p>After saving settings, make a new <strong>Video call</strong> booking. If no Zoom meeting is created, check the booking entry meta key <code>self::META_ZOOM_LAST_ERROR</code> or enable WordPress debug logging. This plugin also now retries Zoom creation shortly after form submission as a fallback.</p>
		</div>
		<?php
	}

	public static function render_next_appointment_shortcode( array $atts = [] ): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$appointments = self::get_user_appointments( get_current_user_id(), true );
		if ( empty( $appointments ) ) {
			$book_url = esc_url( home_url( self::BOOK_A_MEETING_URL ) );
			$list_url = esc_url( home_url( self::APPOINTMENTS_PAGE_URL ) );
			return sprintf(
				'<div class="assoc-empty"><p>You have no upcoming meetings.</p><div class="assoc-appointment-actions"><a class="assoc-btn assoc-btn-primary" href="%1$s">Book a Meeting</a><a class="assoc-btn assoc-btn-secondary" href="%2$s">View / Manage Meetings</a></div></div>',
				$book_url,
				$list_url
			);
		}

		$appointment = $appointments[0];
		$list_url    = esc_url( home_url( self::APPOINTMENTS_PAGE_URL ) );

		ob_start();
		?>
		<div class="assoc-appointment-card">
			<h3 class="assoc-appointment-title"><?php echo esc_html( $appointment['title'] ); ?></h3>
			<p class="assoc-appointment-meta"><?php echo esc_html( $appointment['date_line'] ); ?></p>
			<p class="assoc-appointment-meta">with <?php echo esc_html( $appointment['consultant'] ); ?></p>
			<?php if ( ! empty( $appointment['status'] ) ) : ?>
				<span class="assoc-appointment-status"><?php echo esc_html( $appointment['status'] ); ?></span>
			<?php endif; ?>
			<div class="assoc-appointment-actions">
				<?php if ( ! empty( $appointment['zoom_join_url'] ) ) : ?>
					<a class="assoc-btn assoc-btn-secondary" href="<?php echo esc_url( $appointment['zoom_join_url'] ); ?>" target="_blank" rel="noopener">Join Zoom</a>
				<?php endif; ?>
				<a class="assoc-footer-link" href="<?php echo $list_url; ?>">View / Manage Meetings</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function render_my_appointments_shortcode( array $atts = [] ): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$appointments = self::get_user_appointments( get_current_user_id(), false );
		if ( empty( $appointments ) ) {
			$book_url = esc_url( home_url( self::BOOK_A_MEETING_URL ) );
			return sprintf(
				'<div class="assoc-empty"><p>You have no meetings to display.</p><div class="assoc-appointment-actions"><a class="assoc-btn assoc-btn-primary" href="%s">Book a Meeting</a></div></div>',
				$book_url
			);
		}

		ob_start();
		?>
		<div class="assoc-appointments assoc-appointments-list">
			<?php foreach ( $appointments as $appointment ) : ?>
				<div class="assoc-appointment-card">
					<h3 class="assoc-appointment-title"><?php echo esc_html( $appointment['title'] ); ?></h3>
					<p class="assoc-appointment-meta"><?php echo esc_html( $appointment['date_line'] ); ?></p>
					<p class="assoc-appointment-meta">with <?php echo esc_html( $appointment['consultant'] ); ?></p>
					<?php if ( ! empty( $appointment['format'] ) ) : ?>
						<p class="assoc-appointment-meta"><?php echo esc_html( $appointment['format'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $appointment['help_with'] ) ) : ?>
						<p class="assoc-appointment-summary"><?php echo esc_html( $appointment['help_with'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $appointment['status'] ) ) : ?>
						<span class="assoc-appointment-status"><?php echo esc_html( $appointment['status'] ); ?></span>
					<?php endif; ?>
						<?php if ( ! empty( $appointment['zoom_join_url'] ) ) : ?>
							<p class="assoc-zoom-note">Zoom link ready</p>
						<?php endif; ?>
					<div class="assoc-appointment-actions">
						<?php if ( ! empty( $appointment['zoom_join_url'] ) ) : ?>
							<a class="assoc-btn assoc-btn-secondary" href="<?php echo esc_url( $appointment['zoom_join_url'] ); ?>" target="_blank" rel="noopener">Join Zoom</a>
						<?php endif; ?>
						<?php if ( ! empty( $appointment['manage_url'] ) ) : ?>
								<a class="assoc-btn assoc-btn-primary" href="<?php echo esc_url( $appointment['manage_url'] ); ?>">Manage Meeting</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function get_user_appointments( int $user_id, bool $upcoming_only = true ): array {
		if ( ! class_exists( 'GFAPI' ) || ! function_exists( 'gpb_get_entry_bookings' ) ) {
			return [];
		}

		$form = GFAPI::get_form( self::FORM_ID );
		if ( empty( $form ) || is_wp_error( $form ) ) {
			return [];
		}

		$search_criteria = [
			'status'        => 'active',
			'field_filters' => [
				[ 'key' => 'created_by', 'value' => $user_id ],
			],
		];

		$sorting = [
			'key'        => 'date_created',
			'direction'  => 'DESC',
			'is_numeric' => false,
		];

		$entries = GFAPI::get_entries( self::FORM_ID, $search_criteria, $sorting, [ 'offset' => 0, 'page_size' => 200 ] );
		if ( is_wp_error( $entries ) || empty( $entries ) ) {
			return [];
		}

		$appointments = [];
		$seen_keys    = [];
		$now          = current_time( 'timestamp' );

		foreach ( $entries as $entry ) {
			$bookings = gpb_get_entry_bookings( (int) rgar( $entry, 'id' ) );
			if ( empty( $bookings ) || ! is_array( $bookings ) ) {
				continue;
			}

			$entry_appointments = [];
			foreach ( $bookings as $booking ) {
				$start_ts = self::extract_booking_start_timestamp( $booking, $entry );
				if ( ! $start_ts ) {
					continue;
				}

				if ( $upcoming_only && $start_ts < $now ) {
					continue;
				}

				$booking_id = self::extract_booking_id( $booking, $entry );
				$entry_dedupe_key = $booking_id > 0
					? 'booking:' . $booking_id
					: 'start:' . $start_ts . '|title:' . self::extract_meeting_title( $form, $entry, $booking );

				if ( isset( $entry_appointments[ $entry_dedupe_key ] ) ) {
					continue;
				}

				$entry_appointments[ $entry_dedupe_key ] = [
					'entry_id'       => (int) rgar( $entry, 'id' ),
					'booking_id'     => $booking_id,
					'start_ts'       => $start_ts,
					'title'          => self::extract_meeting_title( $form, $entry, $booking ),
					'consultant'     => self::extract_consultant_name( $form, $entry ),
					'format'         => self::extract_field_display_value( $form, $entry, self::FORMAT_FIELD_ID ),
					'help_with'      => self::extract_field_display_value( $form, $entry, self::HELP_WITH_FIELD_ID ),
					'company'        => self::extract_field_display_value( $form, $entry, self::COMPANY_FIELD_ID ),
					'status'         => self::extract_booking_status( $booking, $entry ),
					'date_line'      => wp_date( 'D, M j - g:i A', $start_ts ),
					'manage_url'     => self::get_manage_url( $form, $entry, $booking ),
					'zoom_join_url'  => (string) gform_get_meta( (int) rgar( $entry, 'id' ), self::META_ZOOM_JOIN_URL ),
					'zoom_error'     => (string) gform_get_meta( (int) rgar( $entry, 'id' ), self::META_ZOOM_LAST_ERROR ),
				];
			}

			if ( empty( $entry_appointments ) ) {
				continue;
			}

			usort(
				$entry_appointments,
				static function ( array $a, array $b ): int {
					return $a['start_ts'] <=> $b['start_ts'];
				}
			);

			$primary_appointment = array_values( $entry_appointments )[0];
			$global_dedupe_key = $primary_appointment['booking_id'] > 0
				? 'booking:' . $primary_appointment['booking_id']
				: 'entry:' . $primary_appointment['entry_id'] . '|start:' . $primary_appointment['start_ts'] . '|title:' . $primary_appointment['title'];

			if ( isset( $seen_keys[ $global_dedupe_key ] ) ) {
				continue;
			}
			$seen_keys[ $global_dedupe_key ] = true;
			$appointments[] = $primary_appointment;
		}

		usort(
			$appointments,
			static function ( array $a, array $b ): int {
				return $a['start_ts'] <=> $b['start_ts'];
			}
		);

		return $appointments;
	}

	public static function maybe_create_zoom_meeting( $booking, $booking_data, $object, $entry ): void {
		if ( ! self::is_target_booking( $booking, $entry ) ) {
			return;
		}

		$entry_id = self::resolve_entry_id( $booking, is_array( $entry ) ? $entry : null );
		if ( ! $entry_id ) {
			return;
		}

		if ( ! self::entry_is_video_call( $entry ) ) {
			return;
		}

		$existing_meeting_id = (string) gform_get_meta( $entry_id, self::META_ZOOM_MEETING_ID );
		if ( $existing_meeting_id !== '' ) {
			return;
		}

		$start_ts = self::extract_booking_start_timestamp( $booking, $entry );
		if ( ! $start_ts ) {
			self::set_zoom_error( $entry_id, 'Could not determine booking start time.' );
			return;
		}

		$duration = self::extract_booking_duration_minutes( $booking, $entry );
		$topic    = self::extract_meeting_title( GFAPI::get_form( self::FORM_ID ), $entry, $booking );
		$agenda   = self::extract_field_display_value( GFAPI::get_form( self::FORM_ID ), $entry, self::HELP_WITH_FIELD_ID );

		$result = self::create_zoom_meeting([
			'topic'      => $topic,
			'agenda'     => $agenda,
			'start_time' => self::format_zoom_local_datetime( $start_ts ),
			'duration'   => $duration,
		]);

		if ( is_wp_error( $result ) ) {
			self::set_zoom_error( $entry_id, $result->get_error_message() );
			return;
		}

		gform_update_meta( $entry_id, self::META_ZOOM_MEETING_ID, (string) ( $result['id'] ?? '' ) );
		gform_update_meta( $entry_id, self::META_ZOOM_JOIN_URL, (string) ( $result['join_url'] ?? '' ) );
		gform_update_meta( $entry_id, self::META_ZOOM_START_URL, (string) ( $result['start_url'] ?? '' ) );
		gform_delete_meta( $entry_id, self::META_ZOOM_LAST_ERROR );
	}

	public static function maybe_reschedule_zoom_meeting( $booking, $new_start_datetime, $new_end_datetime ): void {
		$entry_id = self::resolve_entry_id( $booking, null );
		if ( ! $entry_id ) {
			return;
		}
		$entry = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) || ! self::entry_is_video_call( $entry ) ) {
			return;
		}

		$meeting_id = (string) gform_get_meta( $entry_id, self::META_ZOOM_MEETING_ID );
		if ( $meeting_id === '' ) {
			self::maybe_create_zoom_meeting( $booking, [], null, $entry );
			return;
		}

		$start_ts = strtotime( (string) $new_start_datetime );
		$end_ts   = strtotime( (string) $new_end_datetime );
		if ( ! $start_ts ) {
			return;
		}
		$duration = $end_ts && $end_ts > $start_ts ? (int) round( ( $end_ts - $start_ts ) / 60 ) : self::extract_booking_duration_minutes( $booking, $entry );
		$topic    = self::extract_meeting_title( GFAPI::get_form( self::FORM_ID ), $entry, $booking );
		$agenda   = self::extract_field_display_value( GFAPI::get_form( self::FORM_ID ), $entry, self::HELP_WITH_FIELD_ID );

		$result = self::update_zoom_meeting( $meeting_id, [
			'topic'      => $topic,
			'agenda'     => $agenda,
			'start_time' => self::format_zoom_local_datetime( $start_ts ),
			'duration'   => $duration,
		]);

		if ( is_wp_error( $result ) ) {
			self::set_zoom_error( $entry_id, $result->get_error_message() );
			return;
		}

		gform_delete_meta( $entry_id, self::META_ZOOM_LAST_ERROR );
	}

	public static function maybe_handle_zoom_status_transition( $booking, $new_status, $old_status ): void {
		$entry_id = self::resolve_entry_id( $booking, null );
		if ( ! $entry_id ) {
			return;
		}

		$entry = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) || ! self::entry_is_video_call( $entry ) ) {
			return;
		}

		$meeting_id = (string) gform_get_meta( $entry_id, self::META_ZOOM_MEETING_ID );
		if ( $meeting_id === '' ) {
			return;
		}

		if ( in_array( strtolower( (string) $new_status ), [ 'canceled', 'cancelled' ], true ) ) {
			$result = self::delete_zoom_meeting( $meeting_id );
			if ( is_wp_error( $result ) ) {
				self::set_zoom_error( $entry_id, $result->get_error_message() );
				return;
			}
			gform_delete_meta( $entry_id, self::META_ZOOM_MEETING_ID );
			gform_delete_meta( $entry_id, self::META_ZOOM_JOIN_URL );
			gform_delete_meta( $entry_id, self::META_ZOOM_START_URL );
			gform_delete_meta( $entry_id, self::META_ZOOM_LAST_ERROR );
			return;
		}

		if ( in_array( strtolower( (string) $old_status ), [ 'canceled', 'cancelled' ], true ) && ! in_array( strtolower( (string) $new_status ), [ 'canceled', 'cancelled' ], true ) ) {
			gform_delete_meta( $entry_id, self::META_ZOOM_MEETING_ID );
			gform_delete_meta( $entry_id, self::META_ZOOM_JOIN_URL );
			gform_delete_meta( $entry_id, self::META_ZOOM_START_URL );
			self::maybe_create_zoom_meeting( $booking, [], null, $entry );
		}
	}


	public static function queue_zoom_creation_after_submission( $entry, $form ): void {
		if ( (int) rgar( $form, 'id' ) !== self::FORM_ID ) {
			return;
		}
		if ( ! self::entry_is_video_call( $entry ) ) {
			return;
		}
		$entry_id = (int) rgar( $entry, 'id' );
		if ( ! $entry_id ) {
			return;
		}
		if ( ! wp_next_scheduled( 'assoc_db_process_zoom_for_entry', [ $entry_id ] ) ) {
			wp_schedule_single_event( time() + 15, 'assoc_db_process_zoom_for_entry', [ $entry_id ] );
		}
	}

		public static function process_zoom_for_entry( int $entry_id ): void {
		if ( ! function_exists( 'gpb_get_entry_bookings' ) || ! class_exists( 'GFAPI' ) ) {
			return;
		}
		$entry = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) || ! is_array( $entry ) ) {
			return;
		}
		if ( ! self::entry_is_video_call( $entry ) ) {
			return;
		}
		$existing_meeting_id = (string) gform_get_meta( $entry_id, self::META_ZOOM_MEETING_ID );
		if ( $existing_meeting_id !== '' ) {
			return;
		}
		$bookings = gpb_get_entry_bookings( $entry_id );
		if ( empty( $bookings ) || ! is_array( $bookings ) ) {
			self::set_zoom_error( $entry_id, 'No GP Bookings booking found for entry during fallback processing.' );
			return;
		}
		foreach ( $bookings as $booking ) {
			if ( self::is_target_booking( $booking, $entry ) ) {
				self::maybe_create_zoom_meeting( $booking, [], null, $entry );
				return;
			}
		}
			self::set_zoom_error( $entry_id, 'No service booking found for entry during fallback processing.' );
		}

		public static function register_custom_merge_tags( array $merge_tags, $form_id, $fields, $element_id ): array {
			$merge_tags[] = [
				'label' => 'Associate Zoom Join URL',
				'tag'   => '{assoc_zoom_join_url}',
			];
			$merge_tags[] = [
				'label' => 'Associate Zoom Join Button',
				'tag'   => '{assoc_zoom_join_button}',
			];

			return $merge_tags;
		}

		public static function replace_custom_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
			if ( strpos( $text, '{assoc_zoom_join_url}' ) === false && strpos( $text, '{assoc_zoom_join_button}' ) === false ) {
				return $text;
			}

			$entry_id  = is_array( $entry ) ? (int) rgar( $entry, 'id' ) : 0;
			$join_url  = $entry_id > 0 ? trim( (string) gform_get_meta( $entry_id, self::META_ZOOM_JOIN_URL ) ) : '';
			$button    = '';

			if ( $join_url !== '' ) {
				$button = sprintf(
					'<a href="%1$s" target="_blank" rel="noopener">Join Zoom Meeting</a>',
					esc_url( $join_url )
				);
			}

			$replacements = [
				'{assoc_zoom_join_url}'    => $join_url,
				'{assoc_zoom_join_button}' => $button,
			];

			foreach ( $replacements as $tag => $value ) {
				$text = str_replace( $tag, $value, $text );
			}

			return $text;
		}

	private static function is_target_booking( $booking, $entry ): bool {
		if ( empty( $booking ) || ! is_object( $booking ) ) {
			return false;
		}
		if ( is_array( $entry ) && (int) rgar( $entry, 'form_id' ) !== self::FORM_ID ) {
			return false;
		}
		if ( method_exists( $booking, 'get_type' ) ) {
			try {
				if ( $booking->get_type() !== 'service' ) {
					return false;
				}
			} catch ( \Throwable $e ) {
			}
		}
		return true;
	}

	private static function entry_is_video_call( $entry ): bool {
		if ( ! is_array( $entry ) ) {
			return false;
		}
		$form = GFAPI::get_form( self::FORM_ID );
		$value = self::extract_field_display_value( $form, $entry, self::FORMAT_FIELD_ID );
		return strtolower( trim( $value ) ) === strtolower( self::VIDEO_FORMAT_VALUE );
	}

	private static function create_zoom_meeting( array $payload ) {
		$settings = self::get_settings();
		$host     = trim( (string) $settings['zoom_host_user'] );
		if ( $host === '' ) {
			return new WP_Error( 'assoc_zoom_missing_host', 'Zoom host user is not configured.' );
		}

		$response = self::zoom_api_request( 'POST', '/users/' . rawurlencode( $host ) . '/meetings', [
			'topic'      => $payload['topic'],
			'agenda'     => $payload['agenda'],
			'type'       => 2,
			'start_time' => $payload['start_time'],
			'duration'   => max( 15, (int) $payload['duration'] ),
			'timezone'   => self::get_wp_timezone_string(),
			'settings'   => [
				'join_before_host' => false,
				'waiting_room'     => true,
			],
		] );

		return $response;
	}

	private static function update_zoom_meeting( string $meeting_id, array $payload ) {
		return self::zoom_api_request( 'PATCH', '/meetings/' . rawurlencode( $meeting_id ), [
			'topic'      => $payload['topic'],
			'agenda'     => $payload['agenda'],
			'start_time' => $payload['start_time'],
			'duration'   => max( 15, (int) $payload['duration'] ),
			'timezone'   => self::get_wp_timezone_string(),
		] );
	}

	private static function delete_zoom_meeting( string $meeting_id ) {
		return self::zoom_api_request( 'DELETE', '/meetings/' . rawurlencode( $meeting_id ) );
	}

	private static function zoom_api_request( string $method, string $path, array $body = [] ) {
		$token = self::get_zoom_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$args = [
			'method'  => strtoupper( $method ),
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'timeout' => 20,
		];

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( 'https://api.zoom.us/v2' . $path, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );
		$data = $raw !== '' ? json_decode( $raw, true ) : [];

		if ( $code >= 200 && $code < 300 ) {
			return is_array( $data ) ? $data : [];
		}

		$message = is_array( $data ) && ! empty( $data['message'] ) ? $data['message'] : 'Zoom API request failed.';
		return new WP_Error( 'assoc_zoom_api_error', sprintf( 'Zoom API error (%d): %s', $code, $message ) );
	}

	private static function get_zoom_access_token() {
		$cached = get_transient( 'assoc_db_zoom_access_token' );
		if ( is_string( $cached ) && $cached !== '' ) {
			return $cached;
		}

		$settings = self::get_settings();
		$account_id    = trim( (string) $settings['zoom_account_id'] );
		$client_id     = trim( (string) $settings['zoom_client_id'] );
		$client_secret = trim( (string) $settings['zoom_client_secret'] );

		if ( $account_id === '' || $client_id === '' || $client_secret === '' ) {
			return new WP_Error( 'assoc_zoom_missing_credentials', 'Zoom credentials are incomplete. Fill them in under Settings > AssociateDB Meetings.' );
		}

		$response = wp_remote_post(
			'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . rawurlencode( $account_id ),
			[
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
				],
				'timeout' => 20,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || empty( $body['access_token'] ) ) {
			$message = is_array( $body ) && ! empty( $body['reason'] ) ? $body['reason'] : 'Could not generate Zoom access token.';
			return new WP_Error( 'assoc_zoom_token_error', sprintf( 'Zoom token error (%d): %s', $code, $message ) );
		}

		$ttl = ! empty( $body['expires_in'] ) ? max( 60, (int) $body['expires_in'] - 60 ) : 3500;
		set_transient( 'assoc_db_zoom_access_token', (string) $body['access_token'], $ttl );
		return (string) $body['access_token'];
	}

	private static function set_zoom_error( int $entry_id, string $message ): void {
		gform_update_meta( $entry_id, self::META_ZOOM_LAST_ERROR, $message );
		error_log( '[AssociateDB Meetings] ' . $message . ' (entry ' . $entry_id . ')' );
	}

	private static function format_zoom_local_datetime( int $timestamp ): string {
		try {
			$dt = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
			return $dt->format( 'Y-m-d\TH:i:s' );
		} catch ( \Throwable $e ) {
			return gmdate( 'Y-m-d\TH:i:s', $timestamp );
		}
	}

	private static function get_wp_timezone_string(): string {
		$timezone = wp_timezone_string();
		return $timezone !== '' ? $timezone : 'UTC';
	}

	private static function resolve_entry_id( $booking, ?array $entry ): int {
		if ( is_array( $entry ) && ! empty( $entry['id'] ) ) {
			return (int) $entry['id'];
		}

		if ( is_object( $booking ) ) {
			foreach ( [ 'get_entry_id', 'entry_id' ] as $candidate ) {
				if ( method_exists( $booking, $candidate ) ) {
					try {
						$value = $booking->{$candidate}();
						if ( $value ) {
							return (int) $value;
						}
					} catch ( \Throwable $e ) {
					}
				}
			}
			foreach ( [ 'entry_id', 'entryId' ] as $property ) {
				if ( isset( $booking->{$property} ) ) {
					return (int) $booking->{$property};
				}
			}
		}

		return 0;
	}

	private static function extract_booking_start_timestamp( $booking, array $entry ): int {
		$candidates = [];

		if ( is_object( $booking ) ) {
			$method_candidates = [ 'get_start_date', 'get_start_datetime', 'get_start', 'start' ];
			foreach ( $method_candidates as $method ) {
				if ( method_exists( $booking, $method ) ) {
					try {
						$candidates[] = $booking->{$method}();
					} catch ( \Throwable $e ) {
					}
				}
			}

			$property_candidates = [ 'start_date', 'start_datetime', 'start', 'date_start' ];
			foreach ( $property_candidates as $property ) {
				if ( isset( $booking->{$property} ) ) {
					$candidates[] = $booking->{$property};
				}
			}
		}

		foreach ( $candidates as $candidate ) {
			$timestamp = self::normalize_timestamp( $candidate );
			if ( $timestamp ) {
				return $timestamp;
			}
		}

		if ( ! empty( $entry[ self::BOOKING_PRODUCT_FIELD_ID . '.2' ] ) ) {
			$timestamp = self::normalize_timestamp( $entry[ self::BOOKING_PRODUCT_FIELD_ID . '.2' ] );
			if ( $timestamp ) {
				return $timestamp;
			}
		}

		return 0;
	}

	private static function extract_booking_duration_minutes( $booking, array $entry ): int {
		$start_ts = self::extract_booking_start_timestamp( $booking, $entry );
		$end_candidates = [];

		if ( is_object( $booking ) ) {
			foreach ( [ 'get_end_date', 'get_end_datetime', 'get_end', 'end' ] as $method ) {
				if ( method_exists( $booking, $method ) ) {
					try {
						$end_candidates[] = $booking->{$method}();
					} catch ( \Throwable $e ) {
					}
				}
			}
			foreach ( [ 'end_date', 'end_datetime', 'end', 'date_end' ] as $property ) {
				if ( isset( $booking->{$property} ) ) {
					$end_candidates[] = $booking->{$property};
				}
			}
		}

		foreach ( $end_candidates as $candidate ) {
			$end_ts = self::normalize_timestamp( $candidate );
			if ( $start_ts && $end_ts && $end_ts > $start_ts ) {
				return (int) max( 15, round( ( $end_ts - $start_ts ) / 60 ) );
			}
		}

		$title = strtolower( self::extract_meeting_title( GFAPI::get_form( self::FORM_ID ), $entry, $booking ) );
		if ( strpos( $title, '60' ) !== false || strpos( $title, 'strategy' ) !== false ) {
			return 60;
		}
		if ( strpos( $title, '30' ) !== false || strpos( $title, 'business development' ) !== false ) {
			return 30;
		}
		return 15;
	}

	private static function normalize_timestamp( $value ): int {
		if ( $value instanceof DateTimeInterface ) {
			return $value->getTimestamp();
		}
		if ( is_numeric( $value ) ) {
			$value = (int) $value;
			return $value > 0 ? $value : 0;
		}
		if ( ! is_string( $value ) || $value === '' ) {
			return 0;
		}
		$value = trim( $value );
		try {
			if ( preg_match( '/(?:Z|[+-]\d{2}:?\d{2})$/', $value ) ) {
				$dt = new DateTimeImmutable( $value );
				return $dt->getTimestamp();
			}
			$dt = new DateTimeImmutable( $value, wp_timezone() );
			return $dt->getTimestamp();
		} catch ( \Throwable $e ) {
			$timestamp = strtotime( $value );
			return $timestamp ? $timestamp : 0;
		}
	}

	private static function extract_booking_id( $booking, array $entry ): int {
		if ( is_object( $booking ) ) {
			if ( method_exists( $booking, 'get_id' ) ) {
				try {
					$id = $booking->get_id();
					if ( $id ) {
						return (int) $id;
					}
				} catch ( \Throwable $e ) {
				}
			}
			if ( isset( $booking->id ) ) {
				return (int) $booking->id;
			}
		}

		return ! empty( $entry[ self::BOOKING_PRODUCT_FIELD_ID . '.4' ] ) ? (int) $entry[ self::BOOKING_PRODUCT_FIELD_ID . '.4' ] : 0;
	}

	private static function extract_booking_status( $booking, array $entry ): string {
		if ( is_object( $booking ) ) {
			if ( method_exists( $booking, 'get_status' ) ) {
				try {
					$status = $booking->get_status();
					if ( is_string( $status ) && $status !== '' ) {
						return ucwords( str_replace( [ '_', '-' ], ' ', $status ) );
					}
				} catch ( \Throwable $e ) {
				}
			}
			if ( isset( $booking->status ) && is_string( $booking->status ) ) {
				return ucwords( str_replace( [ '_', '-' ], ' ', $booking->status ) );
			}
		}

		return 'Confirmed';
	}

	private static function extract_meeting_title( $form, array $entry, $booking ): string {
		$title = self::extract_field_display_value( $form, $entry, self::MEETING_TYPE_FIELD_ID );
		if ( $title !== '' ) {
			return $title;
		}

		if ( is_object( $booking ) ) {
			foreach ( [ 'get_name', 'get_title' ] as $method ) {
				if ( method_exists( $booking, $method ) ) {
					try {
						$value = $booking->{$method}();
						if ( is_string( $value ) && $value !== '' ) {
							return $value;
						}
					} catch ( \Throwable $e ) {
					}
				}
			}
		}

		return 'Meeting';
	}

	private static function extract_consultant_name( $form, array $entry ): string {
		$value = self::extract_field_display_value( $form, $entry, self::CONSULTANT_FIELD_ID );
		if ( $value !== '' ) {
			return $value;
		}
		return self::DEFAULT_CONSULTANT_NAME;
	}

	private static function extract_field_display_value( $form, array $entry, int $field_id ): string {
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return isset( $entry[ (string) $field_id ] ) ? trim( (string) $entry[ (string) $field_id ] ) : '';
		}

		foreach ( $form['fields'] as $field ) {
			if ( (int) rgar( $field, 'id' ) !== $field_id ) {
				continue;
			}
			if ( class_exists( 'GFCommon' ) ) {
				$value = GFCommon::get_lead_field_display( $field, rgar( $entry, (string) $field_id ), rgar( $entry, 'currency' ) );
				if ( is_string( $value ) && $value !== '' ) {
					return trim( wp_strip_all_tags( $value ) );
				}
			}
			break;
		}

		return isset( $entry[ (string) $field_id ] ) ? trim( wp_strip_all_tags( (string) $entry[ (string) $field_id ] ) ) : '';
	}

	private static function get_manage_url( $form, array $entry, $booking ): string {
		$direct = self::replace_manage_merge_tag( $entry );
		if ( $direct !== '' && strpos( $direct, 'http' ) === 0 ) {
			return $direct;
		}

		if ( is_object( $booking ) ) {
			foreach ( [ 'get_management_url', 'get_manage_url', 'get_management_link' ] as $method ) {
				if ( method_exists( $booking, $method ) ) {
					try {
						$url = $booking->{$method}();
						if ( is_string( $url ) && strpos( $url, 'http' ) === 0 ) {
							return $url;
						}
					} catch ( \Throwable $e ) {
					}
				}
			}
		}

		return '';
	}

	private static function replace_manage_merge_tag( array $entry ): string {
		if ( ! class_exists( 'GFCommon' ) ) {
			return '';
		}

		$form = GFAPI::get_form( self::FORM_ID );
		if ( empty( $form ) || is_wp_error( $form ) ) {
			return '';
		}

		try {
			$result = GFCommon::replace_variables( '{gpb_manage_booking_url}', $form, $entry, false, false, false, 'text' );
			return is_string( $result ) ? trim( $result ) : '';
		} catch ( \Throwable $e ) {
			return '';
		}
	}
}

AssociateDB_Appointments::init();
