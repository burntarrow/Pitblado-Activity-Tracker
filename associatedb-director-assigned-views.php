<?php
/**
 * Filter GravityView entries so directors only see currently assigned, active associates.
 */

add_filter( 'gravityview/view/entries', 'adb_filter_entries_by_current_director', 10, 3 );
function adb_filter_entries_by_current_director( $entries, $view = null, $request = null ) {

	$target_view_ids = array( 101, 102 );

	if ( ! $view || ! in_array( (int) $view->ID, $target_view_ids, true ) ) {
		return $entries;
	}

	if ( ! is_user_logged_in() ) {
		return new \GV\Entry_Collection();
	}

	if ( current_user_can( 'manage_options' ) ) {
		return $entries;
	}

	if ( ! $entries instanceof \GV\Entry_Collection ) {
		return $entries;
	}

	$return = new \GV\Entry_Collection();

	foreach ( $entries->all() as $entry ) {
		$form_id = (int) $entry->form_id;

		if ( $form_id === 12 ) {
			$associate_field_id = '25';
		} elseif ( $form_id === 34 ) {
			$associate_field_id = '90';
		} else {
			continue;
		}

		$associate_user_id = absint( $entry[ $associate_field_id ] ?? 0 );
		if ( ! $associate_user_id ) {
			continue;
		}

		if ( pitblado_current_user_can_manage_associate( $associate_user_id ) ) {
			$return->add( $entry );
		}
	}

	return $return;
}
