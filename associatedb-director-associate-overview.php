<?php
/**
 * Plugin Name: Associate Overview
 * Description: Director associate overview page, recent activity, plan snapshot, and soft deactivation.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pitblado_get_director_associate_overview_styles' ) ) {
	/**
	 * Backward-compatible wrapper around the shared director UI styles helper.
	 *
	 * @return string
	 */
	function pitblado_get_director_associate_overview_styles() {
		return function_exists( 'pitblado_get_director_shared_styles' ) ? pitblado_get_director_shared_styles() : '';
	}
}

/**
 * Soft deactivate associate from associate-overview page.
 */
add_action( 'template_redirect', function() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( empty( $_GET['deactivate_associate'] ) || empty( $_GET['associate_id'] ) ) {
		return;
	}

	$associate_id = absint( $_GET['associate_id'] );

	if ( ! $associate_id ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'deactivate_associate_' . $associate_id ) ) {
		wp_die( 'Invalid request.' );
	}

	$associate = pitblado_get_associate_user( $associate_id );

	if ( ! $associate ) {
		wp_die( 'Associate not found.' );
	}

	if ( ! pitblado_current_user_can_manage_associate( $associate_id ) ) {
		wp_die( 'You do not have permission to deactivate this associate.' );
	}

	update_user_meta( $associate_id, 'associate_status', 'inactive' );

	wp_safe_redirect( add_query_arg( 'deactivated', '1', home_url( '/director/associates/' ) ) );
	exit;
} );

/**
 * Header + KPI row
 */
add_shortcode( 'director_associate_dashboard', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$styles = pitblado_get_director_associate_overview_styles();
	$associate_id = pitblado_get_requested_associate_id();

	if ( ! $associate_id ) {
		return $styles . '<div class="director-empty-state">No associate selected.</div>';
	}

	$associate = pitblado_get_associate_user( $associate_id );
	if ( ! $associate ) {
		return $styles . '<div class="director-empty-state">Associate not found.</div>';
	}

	if ( ! pitblado_current_user_can_manage_associate( $associate_id ) ) {
		return $styles . '<div class="director-empty-state">You do not have access to this associate.</div>';
	}

	// Total activity logs + latest activity from Form 1.
	$activity_entries = GFAPI::get_entries(
		1,
		array(
			'status'        => 'active',
			'field_filters' => array(
				array(
					'key'   => 'created_by',
					'value' => $associate_id,
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
			'page_size' => 200,
		)
	);

	$total_logs    = 0;
	$last_activity = 'No activity yet';

	if ( ! is_wp_error( $activity_entries ) && ! empty( $activity_entries ) ) {
		$total_logs    = count( $activity_entries );
		$last_activity = date_i18n( 'M j, Y', strtotime( rgar( $activity_entries[0], 'date_created' ) ) );
	}

	// Latest plan entry from Form 4.
	$plan_entries = GFAPI::get_entries(
		4,
		array(
			'status'        => 'active',
			'field_filters' => array(
				array(
					'key'   => 'created_by',
					'value' => $associate_id,
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

	$plan_progress = '—';
	$thirty_day    = '—';

	if ( ! is_wp_error( $plan_entries ) && ! empty( $plan_entries ) ) {
		$plan_entry = $plan_entries[0];

		$plan_progress = rgar( $plan_entry, '32' ) !== '' ? absint( rgar( $plan_entry, '32' ) ) . '%' : '—';
		$thirty_day    = rgar( $plan_entry, '36' ) !== '' ? absint( rgar( $plan_entry, '36' ) ) . '%' : '—';
	}

    $plans_url = add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/plan/' ) );
	$reassign_url = add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/reassign/' ) );

	$deactivate_url = add_query_arg(
		array(
			'associate_id'         => $associate_id,
			'deactivate_associate' => 1,
		),
		home_url( '/director/associates/overview/' )
	);

	$deactivate_url = wp_nonce_url( $deactivate_url, 'deactivate_associate_' . $associate_id );
	$styles         = pitblado_get_director_associate_overview_styles();

	ob_start();
	?>
	<?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="director-page-panel">
		<div class="director-page-header-row">
			<div>
				<h1 class="director-page-title"><?php echo esc_html( $associate->display_name ); ?></h1>
				<p class="director-page-subtitle"><?php echo esc_html( $associate->user_email ); ?></p>
			</div>
			<div class="director-overview-actions">
				<a class="director-secondary-btn" href="<?php echo esc_url( $plans_url ); ?>">View Plan</a>
				<a class="director-secondary-btn" href="<?php echo esc_url( $reassign_url ); ?>">Reassign</a>
				<a class="director-danger-btn" href="<?php echo esc_url( $deactivate_url ); ?>" onclick="return confirm('Deactivate this associate? They will no longer be able to log in.');">Deactivate</a>
			</div>
		</div>

		<div class="director-mini-stats director-associate-kpis">
			<div class="director-mini-stat">
				<div class="director-mini-stat-label">Total Activity Logs</div>
				<div class="director-mini-stat-value"><?php echo esc_html( $total_logs ); ?></div>
			</div>
			<div class="director-mini-stat">
				<div class="director-mini-stat-label">Last Activity</div>
				<div class="director-mini-stat-value director-mini-stat-value-small"><?php echo esc_html( $last_activity ); ?></div>
			</div>
			<div class="director-mini-stat">
				<div class="director-mini-stat-label">Plan Progress</div>
				<div class="director-mini-stat-value"><?php echo esc_html( $plan_progress ); ?></div>
			</div>
			<div class="director-mini-stat">
				<div class="director-mini-stat-label">30-Day Commitment</div>
				<div class="director-mini-stat-value"><?php echo esc_html( $thirty_day ); ?></div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
} );

/**
 * Recent activity panel
 */
add_shortcode( 'director_associate_recent_activity', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$associate_id = pitblado_get_requested_associate_id();

	if ( ! $associate_id ) {
		return '';
	}

	if ( ! pitblado_current_user_can_manage_associate( $associate_id ) ) {
		return '<div class="director-empty-state">You do not have access to this associate.</div>';
	}

	$entries = GFAPI::get_entries(
		1,
		array(
			'status'        => 'active',
			'field_filters' => array(
				array(
					'key'   => 'created_by',
					'value' => $associate_id,
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
			'page_size' => 5,
		)
	);

	if ( is_wp_error( $entries ) || empty( $entries ) ) {
		return '
			' . pitblado_get_director_associate_overview_styles() . '
			<div class="director-page-panel">
				<div class="director-panel-title">Recent Activity</div>
				<div class="director-empty-state">No activity submitted yet.</div>
			</div>
		';
	}

	$rows = array();

	foreach ( $entries as $entry ) {
		$date          = date_i18n( 'm/d/Y', strtotime( rgar( $entry, 'date_created' ) ) );
		$relationship  = rgar( $entry, '1' );
		$activity_type = rgar( $entry, '5' );
		$client        = rgar( $entry, '28' );
		$entry_id      = absint( rgar( $entry, 'id' ) );

		$view_url = add_query_arg(
			array(
				'associate_id' => $associate_id,
				'entry'        => $entry_id,
			),
			home_url( '/director/associates/activity/' )
		);

		$rows[] = sprintf(
			'<tr>
				<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td><a href="%s">View</a></td>
			</tr>',
			esc_html( $date ),
			esc_html( $client ),
			esc_html( $relationship ),
			esc_html( $activity_type ),
			esc_url( $view_url )
		);
	}

	return '
		' . pitblado_get_director_associate_overview_styles() . '
		<div class="director-page-panel">
			<div class="director-panel-header">
				<div>
					<div class="director-panel-title">Recent Activity</div>
					<div class="director-panel-subtitle">Latest submissions for this associate.</div>
				</div>
				<a class="director-panel-link" href="' . esc_url( add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/activity/' ) ) ) . '">View All Activity</a>
			</div>
			<div class="director-associates-table-wrap">
				<table class="director-associates-table">
					<thead>
						<tr>
							<th>Date</th>
							<th>Client / Contact</th>
							<th>Relationship</th>
							<th>Activity Type</th>
							<th></th>
						</tr>
					</thead>
					<tbody>' . implode( '', $rows ) . '</tbody>
				</table>
			</div>
		</div>
	';
} );

/**
 * Plan snapshot panel
 */
add_shortcode( 'director_associate_plan_snapshot', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$associate_id = pitblado_get_requested_associate_id();

	if ( ! $associate_id ) {
		return '';
	}

	if ( ! pitblado_current_user_can_manage_associate( $associate_id ) ) {
		return '<div class="director-empty-state">You do not have access to this associate.</div>';
	}

	$entries = GFAPI::get_entries(
		4,
		array(
			'status'        => 'active',
			'field_filters' => array(
				array(
					'key'   => 'created_by',
					'value' => $associate_id,
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
		return '
			' . pitblado_get_director_associate_overview_styles() . '
			<div class="director-page-panel">
				<div class="director-panel-title">Plan Snapshot</div>
				<div class="director-empty-state">No plan submitted yet.</div>
			</div>
		';
	}

	$entry = $entries[0];

	$annual_focus = rgar( $entry, '14' );
	$q1 = rgar( $entry, '16' );
	$q2 = rgar( $entry, '20' );
	$q3 = rgar( $entry, '22' );
	$q4 = rgar( $entry, '26' );

	$month = (int) current_time( 'n' );
	if ( $month <= 3 ) {
		$current_q     = 'Q1';
		$current_focus = $q1;
	} elseif ( $month <= 6 ) {
		$current_q     = 'Q2';
		$current_focus = $q2;
	} elseif ( $month <= 9 ) {
		$current_q     = 'Q3';
		$current_focus = $q3;
	} else {
		$current_q     = 'Q4';
		$current_focus = $q4;
	}

	return '
		' . pitblado_get_director_associate_overview_styles() . '
		<div class="director-page-panel">
			<div class="director-panel-header">
				<div>
					<div class="director-panel-title">Plan Snapshot</div>
					<div class="director-panel-subtitle">Latest submitted accelerator plan.</div>
				</div>
				<a class="director-panel-link" href="' . esc_url( add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/plan/' ) ) ) . '">Open Full Plan</a>
			</div>

			<div class="director-plan-grid">
				<div>
					<div class="director-mini-stat-label">Annual Focus</div>
					<div class="director-plan-text">' . esc_html( $annual_focus ) . '</div>
				</div>
				<div>
					<div class="director-mini-stat-label">Current Quarter</div>
					<div class="director-plan-text">' . esc_html( $current_q ) . '</div>
				</div>
				<div>
					<div class="director-mini-stat-label">Quarter Objective</div>
					<div class="director-plan-text">' . esc_html( $current_focus ) . '</div>
				</div>
				<div>
					<div class="director-mini-stat-label">Plan Progress</div>
					<div class="director-plan-text">' . esc_html( rgar( $entry, '32' ) ) . '%</div>
				</div>
			</div>
		</div>
	';
} );
