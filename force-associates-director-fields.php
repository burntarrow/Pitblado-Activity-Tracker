<?php
/**
 * Force associate/direction fields on submit.
 * Change form IDs and field IDs below.
 */

// Activity Log form.
add_action( 'gform_pre_submission_12', 'adb_set_associate_fields_activity' );
function adb_set_associate_fields_activity( $form ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id   = get_current_user_id();
	$user      = wp_get_current_user();
	$director  = get_user_meta( $user_id, 'assigned_director', true );

	// Replace these with your actual field IDs.
	$_POST['input_25'] = $user_id;                  // associate_user_id
	$_POST['input_26'] = $user->display_name;      // associate_name
	$_POST['input_27'] = $director;                // assigned_director_at_submission (optional hybrid)
}

// 12-Month Plan form.
add_action( 'gform_pre_submission_34', 'adb_set_associate_fields_plan' );
function adb_set_associate_fields_plan( $form ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id   = get_current_user_id();
	$user      = wp_get_current_user();
	$director  = get_user_meta( $user_id, 'assigned_director', true );

	$_POST['input_90'] = $user_id;                 // associate_user_id
	$_POST['input_91'] = $user->display_name;      // associate_name
	$_POST['input_92'] = $director;                // assigned_director_at_submission (optional hybrid)
}