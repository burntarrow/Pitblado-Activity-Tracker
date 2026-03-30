<?php
/**
 * Plugin Name: AssociateDB Director Dashboard Helpers
 * Description: Shortcodes and helper logic for director dashboard panels.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**Users with no plan**/

add_shortcode( 'director_users_no_plan', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$director_id = get_current_user_id();

	$associates = get_users( array(
		'role'       => 'associate',
		'meta_key'   => 'assigned_director',
		'meta_value' => $director_id,
	) );

	if ( empty( $associates ) ) {
		return '<div class="director-alert-empty">No associates assigned.</div>';
	}

	$rows = array();

	foreach ( $associates as $associate ) {
		$entries = GFAPI::get_entries(
			4,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'   => 'created_by',
						'value' => $associate->ID,
					),
				),
			),
			null,
			array(
				'offset'    => 0,
				'page_size' => 1,
			)
		);

		if ( ! is_wp_error( $entries ) && ! empty( $entries ) ) {
			continue;
		}

		$rows[] = sprintf(
			'<tr>
				<td>%s</td>
				<td><a href="%s">View</a></td>
			</tr>',
			esc_html( $associate->display_name ),
			esc_url( home_url( '/director/plans/' ) )
		);
	}

	if ( empty( $rows ) ) {
		return '<div class="director-alert-empty">All assigned associates have submitted a plan.</div>';
	}

	return '
		<div class="director-alert-panel">
			<div class="director-alert-header">
				<div>
					<div class="director-alert-title">No Plan Submitted</div>
					<div class="director-alert-subtitle">Assigned associates missing a submitted accelerator plan.</div>
				</div>
			</div>
			<div class="director-alert-table-wrap">
				<table class="director-alert-table">
					<thead>
						<tr>
							<th>Associate</th>
							<th></th>
						</tr>
					</thead>
					<tbody>' . implode( '', array_slice( $rows, 0, 10 ) ) . '</tbody>
				</table>
			</div>
		</div>
	';
} );

/**Users with no activity in 14 days**/

add_shortcode( 'director_users_no_activity_14_days', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$director_id = get_current_user_id();
	$cutoff      = gmdate( 'Y-m-d H:i:s', strtotime( '-14 days', current_time( 'timestamp', true ) ) );

	$associates = get_users( array(
		'role'       => 'associate',
		'meta_key'   => 'assigned_director',
		'meta_value' => $director_id,
	) );

	if ( empty( $associates ) ) {
		return '<div class="director-alert-empty">No associates assigned.</div>';
	}

	$rows = array();

	foreach ( $associates as $associate ) {
		$entries = GFAPI::get_entries(
			1,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'   => 'created_by',
						'value' => $associate->ID,
					),
				),
				'start_date'    => $cutoff,
			),
			null,
			array(
				'offset'    => 0,
				'page_size' => 1,
			)
		);

		if ( ! is_wp_error( $entries ) && ! empty( $entries ) ) {
			continue;
		}

		// Get latest activity date, if any, for reference.
		$latest = GFAPI::get_entries(
			1,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'   => 'created_by',
						'value' => $associate->ID,
					),
				),
			),
			array(
				'key'        => 'date_created',
				'direction'  => 'DESC',
				'is_numeric' => false,
			),
			array(
				'offset'    => 0,
				'page_size' => 1,
			)
		);

		$last_activity = 'No activity yet';

		if ( ! is_wp_error( $latest ) && ! empty( $latest ) ) {
			$last_activity = date_i18n( 'M j, Y', strtotime( $latest[0]['date_created'] ) );
		}

		$rows[] = sprintf(
			'<tr>
				<td>%s</td>
				<td>%s</td>
				<td><a href="%s">View</a></td>
			</tr>',
			esc_html( $associate->display_name ),
			esc_html( $last_activity ),
			esc_url( home_url( '/director/activity/' ) )
		);
	}

	if ( empty( $rows ) ) {
		return '<div class="director-alert-empty">All assigned associates have activity within the last 14 days.</div>';
	}

	return '
		<div class="director-alert-panel">
			<div class="director-alert-header">
				<div>
					<div class="director-alert-title">No Activity in 14 Days</div>
					<div class="director-alert-subtitle">Assigned associates without recent activity log submissions.</div>
				</div>
			</div>
			<div class="director-alert-table-wrap">
				<table class="director-alert-table">
					<thead>
						<tr>
							<th>Associate</th>
							<th>Last Activity</th>
							<th></th>
						</tr>
					</thead>
					<tbody>' . implode( '', array_slice( $rows, 0, 10 ) ) . '</tbody>
				</table>
			</div>
		</div>
	';
} );


/***Plan Progress***/

add_shortcode( 'director_average_plan_progress', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$director_id = get_current_user_id();

	$associates = get_users( array(
		'role'       => 'associate',
		'meta_key'   => 'assigned_director',
		'meta_value' => $director_id,
	) );

	if ( empty( $associates ) ) {
		return '
			<div class="director-kpi-value">—</div>
			<div class="director-kpi-meta">No assigned associates</div>
		';
	}

	$total_progress = 0;
	$count          = 0;

	foreach ( $associates as $associate ) {
		$entries = GFAPI::get_entries(
			4,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'   => 'created_by',
						'value' => $associate->ID,
					),
				),
			),
			array(
				'key'        => 'date_created',
				'direction'  => 'DESC',
				'is_numeric' => false,
			),
			array(
				'offset'    => 0,
				'page_size' => 1,
			)
		);

		if ( is_wp_error( $entries ) || empty( $entries ) ) {
			continue;
		}

		$value = rgar( $entries[0], '32' );

		if ( $value === '' || ! is_numeric( $value ) ) {
			continue;
		}

		$total_progress += floatval( $value );
		$count++;
	}

	if ( $count === 0 ) {
		return '
			<div class="director-kpi-value">—</div>
			<div class="director-kpi-meta">No plan progress available</div>
		';
	}

	$average = round( $total_progress / $count );

	return '
		<div class="director-kpi-value">' . esc_html( $average ) . '%</div>
		<div class="director-kpi-meta">Average plan progress</div>
		<div class="director-kpi-submeta">Across ' . esc_html( $count ) . ' associate plans</div>
	';
} );

/**Average 30-day commitment**/

add_shortcode( 'director_average_30_day_completion', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$director_id = get_current_user_id();

	$associates = get_users( array(
		'role'       => 'associate',
		'meta_key'   => 'assigned_director',
		'meta_value' => $director_id,
	) );

	if ( empty( $associates ) ) {
		return '
			<div class="director-kpi-value">—</div>
			<div class="director-kpi-meta">No assigned associates</div>
		';
	}

	$total_progress = 0;
	$count          = 0;

	foreach ( $associates as $associate ) {
		$entries = GFAPI::get_entries(
			4,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'   => 'created_by',
						'value' => $associate->ID,
					),
				),
			),
			array(
				'key'        => 'date_created',
				'direction'  => 'DESC',
				'is_numeric' => false,
			),
			array(
				'offset'    => 0,
				'page_size' => 1,
			)
		);

		if ( is_wp_error( $entries ) || empty( $entries ) ) {
			continue;
		}

		$value = rgar( $entries[0], '36' );

		if ( $value === '' || ! is_numeric( $value ) ) {
			continue;
		}

		$total_progress += floatval( $value );
		$count++;
	}

	if ( $count === 0 ) {
		return '
			<div class="director-kpi-value">—</div>
			<div class="director-kpi-meta">No 30-day data available</div>
		';
	}

	$average = round( $total_progress / $count );

	return '
		<div class="director-kpi-value">' . esc_html( $average ) . '%</div>
		<div class="director-kpi-meta">Average 30-day completion</div>
		<div class="director-kpi-submeta">Across ' . esc_html( $count ) . ' associate plans</div>
	';
} );