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
	 * Return scoped dashboard styles once per request.
	 *
	 * @return string
	 */
	function pitblado_get_director_associate_overview_styles() {
		static $printed = false;

		if ( $printed ) {
			return '';
		}

		$printed = true;

		return '<style id="pitblado-director-associate-overview-css">
			.director-associate-overview-scope.director-page-panel{
				background:#fff;
				border:1px solid #e7e9ef;
				border-radius:18px;
				box-shadow:0 10px 26px rgba(15,23,42,.06);
				padding:24px;
				margin:0 0 22px;
			}
			.director-associate-overview-scope .director-page-header-row{
				display:flex;
				align-items:flex-start;
				justify-content:space-between;
				gap:14px;
				flex-wrap:wrap;
			}
			.director-associate-overview-scope .director-page-title{
				margin:0;
				font-size:32px;
				line-height:1.15;
				font-weight:700;
				color:#0f172a;
			}
			.director-associate-overview-scope .director-page-subtitle{
				margin:6px 0 0;
				font-size:14px;
				color:#667085;
			}
			.director-associate-overview-scope .director-overview-actions{
				display:flex;
				flex-wrap:wrap;
				gap:10px;
			}
			.director-associate-overview-scope a.director-primary-btn,
			.director-associate-overview-scope a.director-secondary-btn,
			.director-associate-overview-scope a.director-danger-btn{
				display:inline-flex;
				align-items:center;
				justify-content:center;
				gap:6px;
				padding:10px 14px;
				border-radius:12px;
				border:1px solid transparent;
				font-size:14px;
				font-weight:600;
				text-decoration:none;
				line-height:1.2;
				transition:all .2s ease;
			}
			.director-associate-overview-scope a.director-primary-btn{
				background:#f2497f;
				border-color:#f2497f;
				color:#fff;
			}
			.director-associate-overview-scope a.director-primary-btn:hover{
				background:#e13a70;
				border-color:#e13a70;
			}
			.director-associate-overview-scope a.director-secondary-btn{
				background:#f8fafc;
				border-color:#d0d5dd;
				color:#0f172a;
			}
			.director-associate-overview-scope a.director-secondary-btn:hover{
				background:#eef2f7;
				border-color:#c4cad5;
			}
			.director-associate-overview-scope a.director-danger-btn{
				background:#fff1f3;
				border-color:#ffd0d8;
				color:#b42318;
			}
			.director-associate-overview-scope a.director-danger-btn:hover{
				background:#ffe4e8;
				border-color:#ffbeca;
			}
			.director-associate-overview-scope .director-mini-stats{
				margin-top:22px;
				display:grid;
				grid-template-columns:repeat(4,minmax(0,1fr));
				gap:12px;
			}
			.director-associate-overview-scope .director-mini-stat{
				background:#f8fafc;
				border:1px solid #e2e8f0;
				border-radius:16px;
				padding:16px;
				min-height:96px;
			}
			.director-associate-overview-scope .director-mini-stat-label{
				font-size:12px;
				font-weight:600;
				letter-spacing:.02em;
				text-transform:uppercase;
				color:#667085;
			}
			.director-associate-overview-scope .director-mini-stat-value{
				margin-top:8px;
				font-size:30px;
				font-weight:700;
				line-height:1.05;
				color:#111827;
			}
			.director-associate-overview-scope .director-mini-stat-value-small{
				font-size:20px;
			}
			.director-associate-overview-scope .director-panel-header{
				display:flex;
				align-items:flex-start;
				justify-content:space-between;
				gap:12px;
				flex-wrap:wrap;
				margin-bottom:16px;
			}
			.director-associate-overview-scope .director-panel-title{
				margin:0;
				font-size:20px;
				font-weight:700;
				color:#0f172a;
			}
			.director-associate-overview-scope .director-panel-subtitle{
				margin-top:4px;
				font-size:13px;
				color:#667085;
			}
			.director-associate-overview-scope .director-panel-link{
				font-size:14px;
				font-weight:600;
				color:#f2497f;
				text-decoration:none;
				white-space:nowrap;
			}
			.director-associate-overview-scope .director-panel-link:hover{
				color:#e13a70;
				text-decoration:underline;
			}
			.director-associate-overview-scope .director-associates-table-wrap{
				border:1px solid #e4e7ec;
				border-radius:14px;
				overflow:auto;
			}
			.director-associate-overview-scope .director-associates-table{
				width:100%;
				min-width:640px;
				border-collapse:separate;
				border-spacing:0;
			}
			.director-associate-overview-scope .director-associates-table th{
				background:#f8fafc;
				color:#475467;
				font-size:12px;
				letter-spacing:.02em;
				text-transform:uppercase;
				font-weight:700;
				text-align:left;
				padding:12px 14px;
				border-bottom:1px solid #e4e7ec;
			}
			.director-associate-overview-scope .director-associates-table td{
				padding:12px 14px;
				font-size:14px;
				color:#344054;
				border-bottom:1px solid #eaecf0;
				vertical-align:middle;
			}
			.director-associate-overview-scope .director-associates-table tbody tr:last-child td{
				border-bottom:none;
			}
			.director-associate-overview-scope .director-associates-table td a{
				color:#f2497f;
				font-weight:600;
				text-decoration:none;
			}
			.director-associate-overview-scope .director-associates-table td a:hover{
				color:#e13a70;
				text-decoration:underline;
			}
			.director-associate-overview-scope .director-plan-grid{
				display:grid;
				grid-template-columns:repeat(2,minmax(0,1fr));
				gap:16px;
			}
			.director-associate-overview-scope .director-plan-grid > div{
				background:#f8fafc;
				border:1px solid #e2e8f0;
				border-radius:14px;
				padding:14px;
			}
			.director-associate-overview-scope .director-plan-text{
				margin-top:8px;
				font-size:16px;
				color:#111827;
				font-weight:600;
				white-space:pre-line;
			}
			.director-associate-overview-scope .director-empty-state{
				margin-top:12px;
				padding:14px 16px;
				border-radius:12px;
				border:1px dashed #d0d5dd;
				background:#f8fafc;
				color:#475467;
				font-size:14px;
			}
			@media (max-width:900px){
				.director-associate-overview-scope .director-mini-stats{
					grid-template-columns:repeat(2,minmax(0,1fr));
				}
				.director-associate-overview-scope .director-plan-grid{
					grid-template-columns:1fr;
				}
			}
			@media (max-width:640px){
				.director-associate-overview-scope.director-page-panel{
					padding:18px;
					border-radius:16px;
				}
				.director-associate-overview-scope .director-page-title{
					font-size:27px;
				}
				.director-associate-overview-scope .director-mini-stats{
					grid-template-columns:1fr;
				}
				.director-associate-overview-scope a.director-primary-btn,
				.director-associate-overview-scope a.director-secondary-btn,
				.director-associate-overview-scope a.director-danger-btn{
					width:100%;
				}
			}
		</style>';
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

	$associate_id = pitblado_get_requested_associate_id();

	if ( ! $associate_id ) {
		return '<div class="director-empty-state">No associate selected.</div>';
	}

	$associate = pitblado_get_associate_user( $associate_id );
	if ( ! $associate ) {
		return '<div class="director-empty-state">Associate not found.</div>';
	}

	if ( ! pitblado_current_user_can_manage_associate( $associate_id ) ) {
		return '<div class="director-empty-state">You do not have access to this associate.</div>';
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
	<div class="director-page-panel director-associate-overview-scope">
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
			<div class="director-page-panel director-associate-overview-scope">
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
		<div class="director-page-panel director-associate-overview-scope">
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
			<div class="director-page-panel director-associate-overview-scope">
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
		<div class="director-page-panel director-associate-overview-scope">
			<div class="director-panel-header">
				<div>
					<div class="director-panel-title">Plan Snapshot</div>
					<div class="director-panel-subtitle">Latest submitted accelerator plan.</div>
				</div>
				<a class="director-panel-link" href="' . esc_url( add_query_arg( 'associate_id', $associate_id, home_url( '/director/plans/' ) ) ) . '">Open Full Plan</a>
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
