<?php
add_shortcode( 'director_my_associates_page', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$director_id = get_current_user_id();
	$scope       = isset( $_GET['scope'] ) ? sanitize_key( wp_unslash( $_GET['scope'] ) ) : 'mine';
	$scope       = in_array( $scope, array( 'mine', 'all' ), true ) ? $scope : 'mine';

	$success_notice = '';
	if ( isset( $_GET['deactivated'] ) && $_GET['deactivated'] === '1' ) {
		$success_notice = '<div class="director-success-notice">Associate deactivated successfully.</div>';
	}

	$associates = 'all' === $scope
		? pitblado_get_all_active_associates()
		: pitblado_get_active_associates_for_director( $director_id );

	$page_title            = 'mine' === $scope ? 'My Associates' : 'All Associates';
	$first_stat_label      = 'mine' === $scope ? 'Assigned Associates' : 'Total Associates';
	$scope_mine_button     = 'mine' === $scope ? 'director-primary-btn' : 'director-secondary-btn';
	$scope_all_button      = 'all' === $scope ? 'director-primary-btn' : 'director-secondary-btn';
	$scope_mine_url        = add_query_arg( 'scope', 'mine', home_url( '/director/associates/' ) );
	$scope_all_url         = add_query_arg( 'scope', 'all', home_url( '/director/associates/' ) );
	$inactive_url          = add_query_arg( 'scope', $scope, home_url( '/director/associates/inactive/' ) );
	$empty_state_message   = 'mine' === $scope
		? 'No associates are currently assigned to you.'
		: 'No active associates found.';

if ( empty( $associates ) ) {
	return '
		<div class="director-page-panel">
			' . $success_notice . '
			<div class="director-page-header-row">
				<div class="director-overview-actions">
					<a class="' . esc_attr( $scope_mine_button ) . '" href="' . esc_url( $scope_mine_url ) . '">My Associates</a>
					<a class="' . esc_attr( $scope_all_button ) . '" href="' . esc_url( $scope_all_url ) . '">All Associates</a>
				</div>
				<div class="director-overview-actions">
					<a class="director-primary-btn" href="' . esc_url( home_url( '/director/associates/add/' ) ) . '">Add Associate</a>
					<a class="director-secondary-btn" href="' . esc_url( $inactive_url ) . '">View Inactive Associates</a>
				</div>
			</div>
			<div class="director-empty-state">' . esc_html( $empty_state_message ) . '</div>
		</div>
	';
}

	$total_associates   = count( $associates );
	$with_plan_count    = 0;
	$no_activity_count  = 0;
	$rows               = array();
	$cutoff_timestamp   = strtotime( '-14 days', current_time( 'timestamp' ) );

	foreach ( $associates as $associate ) {
		$user_id = (int) $associate->ID;

		$activity_entries = GFAPI::get_entries(
			1,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'   => 'created_by',
						'value' => $user_id,
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

		$last_activity      = 'No activity yet';
		$last_activity_ts   = 0;

		if ( ! is_wp_error( $activity_entries ) && ! empty( $activity_entries ) ) {
			$last_activity_ts = strtotime( $activity_entries[0]['date_created'] );
			$last_activity    = date_i18n( 'M j, Y', $last_activity_ts );
		}

		if ( ! $last_activity_ts || $last_activity_ts < $cutoff_timestamp ) {
			$no_activity_count++;
		}

		$plan_entries = GFAPI::get_entries(
			4,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'   => 'created_by',
						'value' => $user_id,
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

		$plan_progress    = '—';
		$thirty_day       = '—';
		$plan_status      = 'No Plan';

		if ( ! is_wp_error( $plan_entries ) && ! empty( $plan_entries ) ) {
			$with_plan_count++;
			$plan_entry    = $plan_entries[0];
			$plan_progress = rgar( $plan_entry, '32' ) !== '' ? absint( rgar( $plan_entry, '32' ) ) . '%' : '—';
			$thirty_day    = rgar( $plan_entry, '36' ) !== '' ? absint( rgar( $plan_entry, '36' ) ) . '%' : '—';
			$plan_status   = 'Submitted';
		}

		$rows[] = sprintf(
			'<tr>
				<td>
					<div class="director-associate-name">%s</div>
					<div class="director-associate-email">%s</div>
				</td>
				<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td><span class="director-plan-status %s">%s</span></td>
				<td>
					<div class="director-table-actions">
						<a href="%s">View Associate</a>
					</div>
				</td>
			</tr>',
			esc_html( $associate->display_name ),
			esc_html( $associate->user_email ),
			esc_html( $last_activity ),
			esc_html( $plan_progress ),
			esc_html( $thirty_day ),
			$plan_status === 'Submitted' ? 'is-submitted' : 'is-missing',
			esc_html( $plan_status ),
			esc_url( add_query_arg( 'associate_id', $user_id, home_url( '/director/associates/overview/' ) ) )
		);
	}

	return '
		<div class="director-page-panel">
			' . $success_notice . '


			<div class="director-page-header-row">
				<div class="director-overview-actions">
					<a class="' . esc_attr( $scope_mine_button ) . '" href="' . esc_url( $scope_mine_url ) . '">My Associates</a>
					<a class="' . esc_attr( $scope_all_button ) . '" href="' . esc_url( $scope_all_url ) . '">All Associates</a>
				</div>
				<div class="director-overview-actions">
					<a class="director-primary-btn" href="' . esc_url( home_url( '/director/associates/add/' ) ) . '">Add Associate</a>
					<a class="director-secondary-btn" href="' . esc_url( $inactive_url ) . '">View Inactive Associates</a>
				</div>
			</div>

			<div class="director-mini-stats">
				<div class="director-mini-stat">
					<div class="director-mini-stat-label">' . esc_html( $first_stat_label ) . '</div>
					<div class="director-mini-stat-value">' . esc_html( $total_associates ) . '</div>
				</div>
				<div class="director-mini-stat">
					<div class="director-mini-stat-label">With Submitted Plan</div>
					<div class="director-mini-stat-value">' . esc_html( $with_plan_count ) . '</div>
				</div>
				<div class="director-mini-stat">
					<div class="director-mini-stat-label">No Activity in 14 Days</div>
					<div class="director-mini-stat-value">' . esc_html( $no_activity_count ) . '</div>
				</div>
			</div>

			<div class="director-associates-table-wrap">
				<table class="director-associates-table">
					<thead>
						<tr>
							<th>Associate</th>
							<th>Last Activity</th>
							<th>Plan Progress</th>
							<th>30-Day</th>
							<th>Plan Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						' . implode( '', $rows ) . '
					</tbody>
				</table>
			</div>
		</div>
	';
} );


/*---Activity Page---*/
add_shortcode( 'director_associate_activity_page', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$associate_id = isset( $_GET['associate_id'] ) ? absint( $_GET['associate_id'] ) : 0;

	if ( ! $associate_id ) {
		return '<div class="director-empty-state">No associate selected.</div>';
	}

	$associate = get_user_by( 'id', $associate_id );

	if ( ! $associate || ! in_array( 'associate', (array) $associate->roles, true ) ) {
		return '<div class="director-empty-state">Associate not found.</div>';
	}

	$is_admin          = current_user_can( 'manage_options' );
	$assigned_director = absint( get_user_meta( $associate_id, 'assigned_director', true ) );

	if ( ! $is_admin && $assigned_director !== get_current_user_id() ) {
		return '<div class="director-empty-state">You do not have access to this associate.</div>';
	}

	$overview_url = add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/overview/' ) );
	$all_activity_url = home_url( '/director/activity/' );

	$view_shortcode = sprintf(
		'[gravityview id="708" secret="f4726efa8f9c" search_field="created_by" search_operator="is" search_value="%d" page_size="15" sort_direction="DESC" back_link_label="← Back to Associate Activity"]',
		$associate_id
	);

	ob_start();
	?>
	<div class="director-page-panel">
		<div class="director-page-header-row">
			<div>
				<h1 class="director-page-title">Associate Activity</h1>
				<p class="director-page-subtitle"><?php echo esc_html( $associate->display_name ); ?> · <?php echo esc_html( $associate->user_email ); ?></p>
			</div>
			<div class="director-overview-actions">
				<a class="director-secondary-btn" href="<?php echo esc_url( $overview_url ); ?>">Back to Overview</a>
				<a class="director-secondary-btn" href="<?php echo esc_url( $all_activity_url ); ?>">All Activity</a>
			</div>
		</div>
	</div>

	<div class="director-page-panel">
		<?php echo do_shortcode( $view_shortcode ); ?>
	</div>
	<?php
	return ob_get_clean();
} );


/*---Individual Plan View---*/
add_shortcode( 'director_associate_plan_page', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$associate_id = isset( $_GET['associate_id'] ) ? absint( $_GET['associate_id'] ) : 0;

	if ( ! $associate_id ) {
		return '<div class="director-empty-state">No associate selected.</div>';
	}

	$associate = get_user_by( 'id', $associate_id );

	if ( ! $associate || ! in_array( 'associate', (array) $associate->roles, true ) ) {
		return '<div class="director-empty-state">Associate not found.</div>';
	}

	$is_admin          = current_user_can( 'manage_options' );
	$assigned_director = absint( get_user_meta( $associate_id, 'assigned_director', true ) );

	if ( ! $is_admin && $assigned_director !== get_current_user_id() ) {
		return '<div class="director-empty-state">You do not have access to this associate.</div>';
	}

	$overview_url = add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/overview/' ) );
	$all_plans_url = home_url( '/director/plans/' );

	$view_shortcode = sprintf(
		'[gravityview id="PLAN_VIEW_ID" secret="PLAN_VIEW_SECRET" search_field="created_by" search_operator="is" search_value="%d" page_size="1" sort_direction="DESC"]',
		$associate_id
	);

	ob_start();
	?>
	<div class="director-page-panel">
		<div class="director-page-header-row">
			<div>
				<h1 class="director-page-title">Associate Plan</h1>
				<p class="director-page-subtitle"><?php echo esc_html( $associate->display_name ); ?> · <?php echo esc_html( $associate->user_email ); ?></p>
			</div>
			<div class="director-overview-actions">
				<a class="director-secondary-btn" href="<?php echo esc_url( $overview_url ); ?>">Back to Overview</a>
				<a class="director-secondary-btn" href="<?php echo esc_url( $all_plans_url ); ?>">All Plans</a>
			</div>
		</div>
	</div>

	<div class="director-page-panel">
		<?php echo do_shortcode( $view_shortcode ); ?>
	</div>
	<?php
	return ob_get_clean();
} );

