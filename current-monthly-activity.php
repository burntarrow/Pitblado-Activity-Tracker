<?php
add_shortcode( 'director_logs_month_compare', function() {
	if ( ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$context       = function_exists( 'pitblado_director_dashboard_get_range_context' )
		? pitblado_director_dashboard_get_range_context()
		: array(
			'key'                   => '30d',
			'label'                 => 'Last 30 Days',
			'start_date'            => gmdate( 'Y-m-d H:i:s', strtotime( '-30 days', current_time( 'timestamp' ) ) ),
			'end_date'              => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'comparison_start_date' => gmdate( 'Y-m-d H:i:s', strtotime( '-60 days', current_time( 'timestamp' ) ) ),
			'comparison_end_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-30 days', current_time( 'timestamp' ) ) ),
			'comparison_label'      => 'previous 30 days',
			'comparison_sub_label'  => 'from previous 30 days',
		);

	$associate_ids = function_exists( 'pitblado_director_dashboard_get_associate_ids' )
		? pitblado_director_dashboard_get_associate_ids()
		: array();

	if ( function_exists( 'pitblado_director_dashboard_count_entries_for_associates' ) ) {
		$selected_count = pitblado_director_dashboard_count_entries_for_associates( 1, $context['start_date'], $context['end_date'], $associate_ids );
		$previous_count = pitblado_director_dashboard_count_entries_for_associates( 1, $context['comparison_start_date'], $context['comparison_end_date'], $associate_ids );
	} else {
		$selected_count = (int) GFAPI::count_entries( 1, array(
			'status'     => 'active',
			'start_date' => $context['start_date'],
			'end_date'   => $context['end_date'],
		) );

		$previous_count = (int) GFAPI::count_entries( 1, array(
			'status'     => 'active',
			'start_date' => $context['comparison_start_date'],
			'end_date'   => $context['comparison_end_date'],
		) );
	}

	$delta = $selected_count - $previous_count;

	if ( $delta > 0 ) {
		$delta_text = '+' . $delta;
	} else {
		$delta_text = (string) $delta;
	}

	return '
		<div class="director-kpi-value">' . esc_html( $selected_count ) . '</div>
		<div class="director-kpi-meta">Submissions in (' . esc_html( $context['label'] ) . ')</div>
		<div class="director-kpi-submeta">vs ' . esc_html( $previous_count ) . ' ' . esc_html( $context['comparison_label'] ) . ' • ' . esc_html( $delta_text ) . ' ' . esc_html( $context['comparison_sub_label'] ) . '</div>
	';
} );
