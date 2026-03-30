<?php
add_shortcode( 'director_logs_month_compare', function() {
	if ( ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$now = current_time( 'timestamp' );

	$this_month_start = date( 'Y-m-01 00:00:00', $now );
	$next_month_start = date( 'Y-m-01 00:00:00', strtotime( '+1 month', strtotime( $this_month_start ) ) );
	$prev_month_start = date( 'Y-m-01 00:00:00', strtotime( '-1 month', strtotime( $this_month_start ) ) );

	$this_month_count = GFAPI::count_entries( 1, array(
		'status'     => 'active',
		'start_date' => $this_month_start,
		'end_date'   => $next_month_start,
	) );

	$prev_month_count = GFAPI::count_entries( 1, array(
		'status'     => 'active',
		'start_date' => $prev_month_start,
		'end_date'   => $this_month_start,
	) );

	$this_month_count = intval( $this_month_count );
	$prev_month_count = intval( $prev_month_count );
	$delta            = $this_month_count - $prev_month_count;

	if ( $delta > 0 ) {
		$delta_text = '+' . $delta;
	} else {
		$delta_text = (string) $delta;
	}

	return '
		<div class="director-kpi-value">' . esc_html( $this_month_count ) . '</div>
		<div class="director-kpi-meta">vs ' . esc_html( $prev_month_count ) . ' last month</div>
		<div class="director-kpi-submeta">' . esc_html( $delta_text ) . ' from previous month</div>
	';
} );