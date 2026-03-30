<?php
/**
 * Filter GravityView entries by the associate's CURRENT assigned director.
 *
 * Change:
 * - view IDs
 * - associate_user_id field IDs
 */

add_filter( 'gravityview/view/entries', 'adb_filter_entries_by_current_director', 10, 3 );
function adb_filter_entries_by_current_director( $entries, $view = null, $request = null ) {

	// Only apply to the "My Associates" Views.
	$target_view_ids = array( 101, 102 ); // e.g. 101 = My Associates Activity, 102 = My Associates Plans

	if ( ! $view || ! in_array( (int) $view->ID, $target_view_ids, true ) ) {
		return $entries;
	}

	if ( ! is_user_logged_in() ) {
		return new \GV\Entry_Collection();
	}

	$current_user_id = get_current_user_id();

	// Optional: admins can see everything.
	if ( current_user_can( 'manage_options' ) ) {
		return $entries;
	}

	if ( ! $entries instanceof \GV\Entry_Collection ) {
		return $entries;
	}

	$return = new \GV\Entry_Collection();

	foreach ( $entries->all() as $entry ) {

		$form_id = (int) $entry->form_id;

		// Map form ID to the field ID that stores associate_user_id.
		if ( $form_id === 12 ) {
			$associate_field_id = '25'; // Activity Log: associate_user_id
		} elseif ( $form_id === 34 ) {
			$associate_field_id = '90'; // 12-Month Plan: associate_user_id
		} else {
			continue;
		}

		$associate_user_id = absint( $entry[ $associate_field_id ] ?? 0 );
		if ( ! $associate_user_id ) {
			continue;
		}

		$assigned_director = absint( get_user_meta( $associate_user_id, 'assigned_director', true ) );

		if ( $assigned_director === $current_user_id ) {
			$return->add( $entry );
		}
	}

	return $return;
}