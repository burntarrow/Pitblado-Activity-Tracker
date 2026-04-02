<?php
/**
 * Shortcode: [associate_current_quarter_focus]
 * Outputs the current quarter objective from the latest Form 4 entry
 * for the logged-in user.
 */

add_shortcode( 'associate_current_quarter_focus', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$user_id = get_current_user_id();

	$search_criteria = array(
		'status'        => 'active',
		'field_filters' => array(
			array(
				'key'   => 'created_by',
				'value' => $user_id,
			),
		),
	);

	$sorting = array(
		'key'       => 'date_created',
		'direction' => 'DESC',
		'is_numeric'=> false,
	);

	$paging = array(
		'offset'    => 0,
		'page_size' => 1,
	);

	$entries = GFAPI::get_entries( 4, $search_criteria, $sorting, $paging );

	if ( is_wp_error( $entries ) || empty( $entries ) ) {
		return '<span class="assoc-current-quarter-empty">No plan set yet</span>';
	}

	$entry = $entries[0];
	$month = (int) current_time( 'n' );

	if ( $month >= 1 && $month <= 3 ) {
		$field_id = 16;
	} elseif ( $month >= 4 && $month <= 6 ) {
		$field_id = 20;
	} elseif ( $month >= 7 && $month <= 9 ) {
		$field_id = 22;
	} else {
		$field_id = 26;
	}

	$value = trim( wp_strip_all_tags( (string) rgar( $entry, (string) $field_id ) ) );
	$label = 'Current Quarter Objective';

	if ( $value === '' ) {
		return '<span class="assoc-current-quarter-empty">No objective set</span>';
	}

	return '<div class="assoc-current-quarter-focus-wrap">'
		. '<div class="assoc-current-quarter-focus-value">' . esc_html( $value ) . '</div>'
		. '<div class="assoc-current-quarter-focus-meta">' . esc_html( $label ) . '</div>'
		. '</div>';
} );