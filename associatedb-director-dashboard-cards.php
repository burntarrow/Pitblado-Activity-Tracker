<?php
/**
 * Plugin Name: AssociateDB Director Dashboard Helpers
 * Description: Shortcodes and helper logic for director dashboard panels.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pitblado_director_dashboard_get_range_context' ) ) {
	function pitblado_director_dashboard_get_range_context() {
		$range_key = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '30d';

		$ranges = array(
			'30d'         => 'Last 30 Days',
			'90d'         => 'Last 90 Days',
			'this_month'  => 'This Month',
			'this_quarter'=> 'This Quarter',
		);

		if ( ! isset( $ranges[ $range_key ] ) ) {
			$range_key = '30d';
		}

		$now = current_time( 'timestamp' );

		switch ( $range_key ) {
			case '90d':
				$start_ts            = strtotime( '-90 days', $now );
				$end_ts              = $now;
				$compare_end_ts      = $start_ts;
				$compare_start_ts    = strtotime( '-90 days', $start_ts );
				$comparison_label    = 'previous 90 days';
				$comparison_sub_label = 'from previous 90 days';
				break;

			case 'this_month':
				$start_ts            = strtotime( date_i18n( 'Y-m-01 00:00:00', $now ) );
				$end_ts              = $now;
				$compare_start_ts    = strtotime( '-1 month', $start_ts );
				$compare_end_ts      = $start_ts;
				$comparison_label    = 'last month';
				$comparison_sub_label = 'from previous month';
				break;

			case 'this_quarter':
				$month               = (int) date_i18n( 'n', $now );
				$year                = (int) date_i18n( 'Y', $now );
				$quarter_start_month = (int) floor( ( $month - 1 ) / 3 ) * 3 + 1;
				$start_ts            = strtotime( sprintf( '%04d-%02d-01 00:00:00', $year, $quarter_start_month ) );
				$end_ts              = $now;
				$compare_start_ts    = strtotime( '-3 months', $start_ts );
				$compare_end_ts      = $start_ts;
				$comparison_label    = 'last quarter';
				$comparison_sub_label = 'from previous quarter';
				break;

			case '30d':
			default:
				$start_ts            = strtotime( '-30 days', $now );
				$end_ts              = $now;
				$compare_end_ts      = $start_ts;
				$compare_start_ts    = strtotime( '-30 days', $start_ts );
				$comparison_label    = 'previous 30 days';
				$comparison_sub_label = 'from previous 30 days';
				break;
		}

		return array(
			'key'                  => $range_key,
			'label'                => $ranges[ $range_key ],
			'start_date'           => gmdate( 'Y-m-d H:i:s', $start_ts ),
			'end_date'             => gmdate( 'Y-m-d H:i:s', $end_ts ),
			'comparison_start_date'=> gmdate( 'Y-m-d H:i:s', $compare_start_ts ),
			'comparison_end_date'  => gmdate( 'Y-m-d H:i:s', $compare_end_ts ),
			'comparison_label'     => $comparison_label,
			'comparison_sub_label' => $comparison_sub_label,
		);
	}
}

if ( ! function_exists( 'pitblado_director_dashboard_get_associate_ids' ) ) {
	function pitblado_director_dashboard_get_associate_ids() {
		if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
			$associates = pitblado_get_active_associates_for_director( get_current_user_id() );
		} else {
			$associates = pitblado_get_all_active_associates();
		}

		if ( empty( $associates ) ) {
			return array();
		}

		return array_map( 'intval', wp_list_pluck( $associates, 'ID' ) );
	}
}

if ( ! function_exists( 'pitblado_director_dashboard_count_entries_for_associates' ) ) {
	function pitblado_director_dashboard_count_entries_for_associates( $form_id, $start_date, $end_date, $associate_ids ) {
		if ( empty( $associate_ids ) ) {
			return 0;
		}

		$total = 0;

		foreach ( $associate_ids as $associate_id ) {
			$count = GFAPI::count_entries(
				$form_id,
				array(
					'status'        => 'active',
					'start_date'    => $start_date,
					'end_date'      => $end_date,
					'field_filters' => array(
						array(
							'key'   => 'created_by',
							'value' => (int) $associate_id,
						),
					),
				)
			);

			if ( is_wp_error( $count ) ) {
				continue;
			}

			$total += (int) $count;
		}

		return $total;
	}
}

add_shortcode( 'director_dashboard_range_selector', function() {
	$context = pitblado_director_dashboard_get_range_context();
	$ranges  = array(
		'30d'          => 'Last 30 Days',
		'90d'          => 'Last 90 Days',
		'this_month'   => 'This Month',
		'this_quarter' => 'This Quarter',
	);

	$base_url = remove_query_arg( 'range' );
	$links    = array();

	foreach ( $ranges as $key => $label ) {
		$url = add_query_arg( 'range', $key, $base_url );

		$links[] = sprintf(
			'<a class="director-range-pill %s" href="%s">%s</a>',
			$context['key'] === $key ? 'is-active' : '',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	static $styles_printed = false;
	$styles = '';

	if ( ! $styles_printed ) {
		$styles_printed = true;
		$styles         = '<style>
			.director-range-selector{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 14px}
			.director-range-pill{display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;border-radius:999px;border:1px solid #d8dce4;background:#fff;color:#1f2937;font-size:13px;font-weight:600;text-decoration:none;line-height:1.3}
			.director-range-pill:hover{border-color:#c3cada;background:#f8fafc}
			.director-range-pill.is-active{background:#1f2937;border-color:#1f2937;color:#fff}
		</style>';
	}

	return $styles . '<div class="director-range-selector" role="group" aria-label="Director dashboard date range">' . implode( '', $links ) . '</div>';
} );

add_shortcode( 'director_relationship_type_chart', function() {
	if ( ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$context       = pitblado_director_dashboard_get_range_context();
	$associate_ids = pitblado_director_dashboard_get_associate_ids();

	if ( empty( $associate_ids ) ) {
		return '<div class="director-kpi-meta">No assigned associates</div>';
	}

	$relationship_counts = array();

	foreach ( $associate_ids as $associate_id ) {
		$paging = array(
			'offset'    => 0,
			'page_size' => 200,
		);

		do {
			$entries = GFAPI::get_entries(
				1,
				array(
					'status'        => 'active',
					'start_date'    => $context['start_date'],
					'end_date'      => $context['end_date'],
					'field_filters' => array(
						array(
							'key'   => 'created_by',
							'value' => (int) $associate_id,
						),
					),
				),
				null,
				$paging
			);

			if ( is_wp_error( $entries ) || empty( $entries ) ) {
				break;
			}

			foreach ( $entries as $entry ) {
				$relationship = trim( (string) rgar( $entry, '1' ) );

				if ( '' === $relationship ) {
					$relationship = 'Unspecified';
				}

				if ( ! isset( $relationship_counts[ $relationship ] ) ) {
					$relationship_counts[ $relationship ] = 0;
				}

				$relationship_counts[ $relationship ]++;
			}

			$paging['offset'] += $paging['page_size'];
		} while ( count( $entries ) === $paging['page_size'] );
	}

	if ( empty( $relationship_counts ) ) {
		return '<div class="director-kpi-meta">No relationship logs in ' . esc_html( strtolower( $context['label'] ) ) . '</div>';
	}

	arsort( $relationship_counts );
	$colors         = array( '#3b82f6', '#22c55e', '#f59e0b', '#a855f7', '#ef4444', '#06b6d4', '#64748b' );
	$total          = array_sum( $relationship_counts );
	$conic_parts    = array();
	$legend_rows    = array();
	$current_angle  = 0;
	$index          = 0;

	foreach ( $relationship_counts as $label => $count ) {
		$color       = $colors[ $index % count( $colors ) ];
		$percentage  = ( $count / $total ) * 100;
		$start_angle = $current_angle;
		$end_angle   = min( 100, $current_angle + $percentage );

		$conic_parts[] = sprintf( '%1$s %2$.2f%% %1$s %3$.2f%%', $color, $start_angle, $end_angle );

		$legend_rows[] = sprintf(
			'<div class="director-relationship-legend-row"><span class="director-relationship-dot" style="background:%s"></span><span class="director-relationship-name">%s</span><span class="director-relationship-count">%d</span></div>',
			esc_attr( $color ),
			esc_html( $label ),
			(int) $count
		);

		$current_angle = $end_angle;
		$index++;
	}

	static $styles_printed = false;
	$styles = '';

	if ( ! $styles_printed ) {
		$styles_printed = true;
		$styles         = '<style>
			.director-relationship-card{display:grid;grid-template-columns:minmax(0,180px) minmax(0,1fr);align-items:center;gap:16px}
			.director-relationship-donut{width:170px;height:170px;border-radius:50%;position:relative;margin:0 auto;background:conic-gradient(var(--segments));}
			.director-relationship-donut:after{content:"";position:absolute;inset:25px;border-radius:50%;background:#fff}
			.director-relationship-total{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;z-index:2;font-weight:700;color:#111827}
			.director-relationship-total small{font-size:12px;color:#6b7280;font-weight:600}
			.director-relationship-legend-row{display:grid;grid-template-columns:14px 1fr auto;align-items:center;gap:8px;margin:6px 0;font-size:13px}
			.director-relationship-dot{width:10px;height:10px;border-radius:999px;display:inline-block}
			.director-relationship-count{font-weight:700}
			@media (max-width:640px){.director-relationship-card{grid-template-columns:1fr}}
		</style>';
	}

	return $styles .
		'<div class="director-kpi-submeta" style="margin-bottom:8px;">' . esc_html( $context['label'] ) . '</div>' .
		'<div class="director-relationship-card">' .
			'<div class="director-relationship-donut" style="--segments:' . esc_attr( implode( ',', $conic_parts ) ) . ';">' .
				'<div class="director-relationship-total">' . esc_html( $total ) . '<small>Total logs</small></div>' .
			'</div>' .
			'<div class="director-relationship-legend">' . implode( '', $legend_rows ) . '</div>' .
		'</div>';
} );

/**Users with no plan**/

add_shortcode( 'director_users_no_plan', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$director_id = get_current_user_id();

	$associates = pitblado_get_active_associates_for_director( $director_id );

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

	$associates = pitblado_get_active_associates_for_director( $director_id );

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

	$associates = pitblado_get_active_associates_for_director( $director_id );

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

	$associates = pitblado_get_active_associates_for_director( $director_id );

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
