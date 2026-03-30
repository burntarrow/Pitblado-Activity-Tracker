<?php
/**
 * AssociateDB - Recalculate Accelerator Plan progress
 *
 * Parent form: 4
 * Parent progress field: 32 (progress_percent)
 *
 * Nested Form fields on parent form 4:
 * - 17 => child form 5 (Q1)
 * - 19 => child form 6 (Q2)
 * - 23 => child form 8 (Q3)
 * - 25 => child form 7 (Q4)
 *
 * Child form status field:
 * - field 5
 * - completed value: "Completed"
 */

/**
 * GP Nested Forms + GravityView:
 * Auto-attach newly added child entries to the parent entry while editing in GravityView.
 */
add_filter( 'gpnf_set_parent_entry_id', function( $parent_entry_id ) {
	if ( ! $parent_entry_id && is_callable( 'gravityview_get_context' ) && gravityview_get_context() === 'edit' ) {
		$parent_entry_id = GravityView_frontend::is_single_entry();
	}

	return $parent_entry_id;
} );

/**
 * Main recalculation function.
 */
if ( ! function_exists( 'adb_recalculate_plan_progress' ) ) {
	function adb_recalculate_plan_progress( $parent_entry_id ) {
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

		// Only run on the parent Accelerator Plan form.
		if ( (int) rgar( $parent_entry, 'form_id' ) !== 4 ) {
			return;
		}

		$nested_field_ids   = array( 17, 19, 23, 25 );
		$total_actions      = 0;
		$completed_actions  = 0;

		foreach ( $nested_field_ids as $field_id ) {
			$raw_child_ids = rgar( $parent_entry, (string) $field_id );

			if ( empty( $raw_child_ids ) ) {
				continue;
			}

			/**
			 * GP Nested Forms commonly stores child entry IDs as a comma-separated list
			 * in the parent entry field value.
			 */
			$child_ids = array_filter( array_map( 'absint', explode( ',', $raw_child_ids ) ) );

			foreach ( $child_ids as $child_id ) {
				$child_entry = GFAPI::get_entry( $child_id );

				if ( is_wp_error( $child_entry ) || empty( $child_entry ) ) {
					continue;
				}

				$total_actions++;

				// Status field is field 5 on child forms 5, 6, 7, and 8.
				$status = trim( (string) rgar( $child_entry, '5' ) );

				if ( $status === 'Completed' ) {
					$completed_actions++;
				}
			}
		}

		$progress_percent = 0;

		if ( $total_actions > 0 ) {
			$progress_percent = round( ( $completed_actions / $total_actions ) * 100 );
		}

		$is_updating = true;

		// Update parent field 32 = progress_percent.
		GFAPI::update_entry_field( $parent_entry_id, 32, $progress_percent );

		// Optional helper meta for future dashboard use.
		gform_update_meta( $parent_entry_id, 'completed_actions_count', $completed_actions );
		gform_update_meta( $parent_entry_id, 'total_actions_count', $total_actions );

		$is_updating = false;
	}
}

/**
 * Recalculate after the parent form is newly submitted.
 */
add_action( 'gform_after_submission_4', function( $entry, $form ) {
	adb_recalculate_plan_progress( (int) rgar( $entry, 'id' ) );
}, 10, 2 );

/**
 * Recalculate after editing the parent entry in GravityView.
 *
 * Hook signature documented by GravityKit:
 * gravityview/edit_entry/after_update( $form, $entry_id, $object )
 */
add_action( 'gravityview/edit_entry/after_update', function( $form, $entry_id, $object ) {
	if ( empty( $form ) || (int) rgar( $form, 'id' ) !== 4 ) {
		return;
	}

	adb_recalculate_plan_progress( (int) $entry_id );
}, 10, 3 );