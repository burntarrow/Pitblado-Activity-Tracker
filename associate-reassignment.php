<?php
/**
 * Reassign Associate form
 * Form ID: 14
 * Hidden target user field: 4
 *
 * This makes the User Registration Update User feed
 * update the selected associate instead of the logged-in director.
 */

add_filter( 'gform_user_registration_update_user_id_14', 'pitblado_reassign_target_user_14', 10, 4 );
function pitblado_reassign_target_user_14( $user_id, $entry, $form, $feed ) {
	$target_user_id = absint( rgar( $entry, '4' ) );

	if ( $target_user_id > 0 ) {
		return $target_user_id;
	}

	return $user_id;
}