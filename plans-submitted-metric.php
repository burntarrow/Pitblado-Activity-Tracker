<?php
add_shortcode( 'director_plan_submission_card', function() {
	if ( ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
		$associates = pitblado_get_active_associates_for_director( get_current_user_id() );
	} else {
		$associates = pitblado_get_all_active_associates();
	}

	$total_associates = count( $associates );
	$with_plan        = 0;

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
			$with_plan++;
		}
	}

	$no_plan = max( 0, $total_associates - $with_plan );

	return '
		<div class="director-plan-card" data-with-plan="' . esc_attr( $with_plan ) . '" data-no-plan="' . esc_attr( $no_plan ) . '">
			<div class="director-plan-chart-wrap">
				<div class="director-plan-chart">
					<svg viewBox="0 0 120 120" class="director-plan-svg" aria-hidden="true">
						<circle class="director-plan-bg" cx="60" cy="60" r="52"></circle>
						<circle class="director-plan-submitted" cx="60" cy="60" r="52"></circle>
					</svg>

					<div class="director-plan-center">
						<div class="director-plan-center-number">0%</div>
						<div class="director-plan-center-label">Submitted</div>
					</div>
				</div>
			</div>

			<div class="director-plan-legend">
				<div class="director-plan-legend-item">
					<span class="director-plan-dot director-plan-dot-submitted"></span>
					<span>With Plan: <strong class="director-plan-with-plan">' . esc_html( $with_plan ) . '</strong></span>
				</div>
				<div class="director-plan-legend-item">
					<span class="director-plan-dot director-plan-dot-no-plan"></span>
					<span>No Plan: <strong class="director-plan-no-plan">' . esc_html( $no_plan ) . '</strong></span>
				</div>
			</div>
		</div>
	';
} );
