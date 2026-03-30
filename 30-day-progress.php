<?php
/**
 * AssociateDB - Recalculate 30-Day Commitment completion %
 *
 * Parent form: 4
 * Parent nested field: 35
 * Parent percent field: 36
 *
 * Child form: 13
 * Child status field: 5
 * Completed value: "Completed"
 */

if ( ! function_exists( 'adb_recalculate_30_day_progress' ) ) {
	function adb_recalculate_30_day_progress( $parent_entry_id ) {
		static $is_updating = false;

		if ( $is_updating ) {
			return;
		}

		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}

		$parent_entry = GFAPI::get_entry( $parent_entry_id );

		if ( is_wp_error( $parent_entry ) || empty( $parent_entry ) ) {
			return;
		}

		// Only run on parent Accelerator Plan form.
		if ( (int) rgar( $parent_entry, 'form_id' ) !== 4 ) {
			return;
		}

		$raw_child_ids = rgar( $parent_entry, '35' );

		$total_items     = 0;
		$completed_items = 0;

		if ( ! empty( $raw_child_ids ) ) {
			$child_ids = array_filter( array_map( 'absint', explode( ',', $raw_child_ids ) ) );

			foreach ( $child_ids as $child_id ) {
				$child_entry = GFAPI::get_entry( $child_id );

				if ( is_wp_error( $child_entry ) || empty( $child_entry ) ) {
					continue;
				}

				// Safety: only count entries from child form 13.
				if ( (int) rgar( $child_entry, 'form_id' ) !== 13 ) {
					continue;
				}

				$total_items++;

				$status = trim( (string) rgar( $child_entry, '5' ) );

				if ( $status === 'Completed' ) {
					$completed_items++;
				}
			}
		}

		$progress_percent = $total_items > 0
			? round( ( $completed_items / $total_items ) * 100 )
			: 0;

		$is_updating = true;

		// Store the 30-day completion % in parent field 36.
		GFAPI::update_entry_field( $parent_entry_id, 36, $progress_percent );

		// Optional meta for later dashboard display.
		gform_update_meta( $parent_entry_id, '30_day_completed_count', $completed_items );
		gform_update_meta( $parent_entry_id, '30_day_total_count', $total_items );

		$is_updating = false;
	}
}

/**
 * Recalculate after parent Form 4 submission.
 */
add_action( 'gform_after_submission_4', function( $entry, $form ) {
	adb_recalculate_30_day_progress( (int) rgar( $entry, 'id' ) );
}, 30, 2 );

/**
 * Recalculate after editing parent entry in GravityView.
 */
add_action( 'gravityview/edit_entry/after_update', function( $form, $entry_id, $object ) {
	if ( empty( $form ) || (int) rgar( $form, 'id' ) !== 4 ) {
		return;
	}

	adb_recalculate_30_day_progress( (int) $entry_id );
}, 30, 3 );