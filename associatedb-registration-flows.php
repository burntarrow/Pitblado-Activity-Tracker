<?php
/**
 * Plugin Name: AssociateDB Registration Flows
 * Description: Associate reassignment and registration field-forcing workflow hooks.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'gform_user_registration_update_user_id_14', 'pitblado_reassign_target_user_14', 10, 4 );
function pitblado_reassign_target_user_14( $user_id, $entry, $form, $feed ) {
	$target_user_id = absint( rgar( $entry, '4' ) );
	return $target_user_id > 0 ? $target_user_id : $user_id;
}

add_action( 'gform_pre_submission_12', 'adb_set_associate_fields_activity' );
function adb_set_associate_fields_activity( $form ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id  = get_current_user_id();
	$user     = wp_get_current_user();
	$director = get_user_meta( $user_id, 'assigned_director', true );

	$_POST['input_25'] = $user_id;
	$_POST['input_26'] = $user->display_name;
	$_POST['input_27'] = $director;
}

add_action( 'gform_pre_submission_34', 'adb_set_associate_fields_plan' );
function adb_set_associate_fields_plan( $form ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id  = get_current_user_id();
	$user     = wp_get_current_user();
	$director = get_user_meta( $user_id, 'assigned_director', true );

	$_POST['input_90'] = $user_id;
	$_POST['input_91'] = $user->display_name;
	$_POST['input_92'] = $director;
}
