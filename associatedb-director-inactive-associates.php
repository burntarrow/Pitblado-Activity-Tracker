<?php
/**
 * Plugin Name: Director Inactive Associates
 * Description: Inactive associates page and reactivation flow for directors/admins.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reactivate associate from inactive associates page.
 */
add_action( 'template_redirect', function() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( empty( $_GET['reactivate_associate'] ) || empty( $_GET['associate_id'] ) ) {
		return;
	}

	$associate_id = absint( $_GET['associate_id'] );

	if ( ! $associate_id ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'reactivate_associate_' . $associate_id ) ) {
		wp_die( 'Invalid request.' );
	}

	$associate = pitblado_get_associate_user( $associate_id );

	if ( ! $associate ) {
		wp_die( 'Associate not found.' );
	}

	if ( ! pitblado_current_user_can_reactivate_associate( $associate_id ) ) {
		wp_die( 'You do not have permission to reactivate this associate.' );
	}

	delete_user_meta( $associate_id, 'associate_status' );

	wp_safe_redirect( add_query_arg( 'reactivated', '1', home_url( '/partner/associates/inactive/' ) ) );
	exit;
} );

add_shortcode( 'director_inactive_associates_page', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$current_user_id = get_current_user_id();
	$styles          = function_exists( 'pitblado_get_director_shared_styles' ) ? pitblado_get_director_shared_styles() : '';
	$is_admin        = current_user_can( 'manage_options' );

	if ( ! $is_admin && ! current_user_can( 'read' ) ) {
		return $styles . '<div class="director-empty-state">You do not have permission to view this page.</div>';
	}

	$success_notice = '';
	if ( isset( $_GET['reactivated'] ) && $_GET['reactivated'] === '1' ) {
		$success_notice = '<div class="director-success-notice">Associate reactivated successfully.</div>';
	}

	$associates = $is_admin
		? pitblado_get_all_inactive_associates()
		: pitblado_get_inactive_associates_for_director( $current_user_id );

	if ( empty( $associates ) ) {
		return $styles . '
			<div class="director-page-panel">
				' . $success_notice . '
				<div class="director-page-header-row">
					<div>
						<h1 class="director-page-title">Inactive Associates</h1>
						<p class="director-page-subtitle">Review and reactivate previously deactivated associates.</p>
					</div>
					<a class="director-secondary-btn" href="' . esc_url( home_url( '/partner/associates/' ) ) . '">Back to My Associates</a>
				</div>
				<div class="director-empty-state">No inactive associates found.</div>
			</div>
		';
	}

	$rows = array();

	foreach ( $associates as $associate ) {
		$user_id = (int) $associate->ID;

		if ( ! $is_admin ) {
			$assigned_director = absint( get_user_meta( $user_id, 'assigned_director', true ) );
			if ( $assigned_director !== $current_user_id ) {
				continue;
			}
		}

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

		$last_activity = 'No activity yet';

		if ( ! is_wp_error( $activity_entries ) && ! empty( $activity_entries ) ) {
			$last_activity = date_i18n( 'M j, Y', strtotime( $activity_entries[0]['date_created'] ) );
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

		$plan_progress = '—';

		if ( ! is_wp_error( $plan_entries ) && ! empty( $plan_entries ) ) {
			$plan_entry     = $plan_entries[0];
			$plan_progress  = rgar( $plan_entry, '32' ) !== '' ? absint( rgar( $plan_entry, '32' ) ) . '%' : '—';
		}

		$reactivate_url = add_query_arg(
			array(
				'associate_id'         => $user_id,
				'reactivate_associate' => 1,
			),
			home_url( '/partner/associates/inactive/' )
		);

		$reactivate_url = wp_nonce_url( $reactivate_url, 'reactivate_associate_' . $user_id );

		$rows[] = sprintf(
			'<tr>
				<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td><a class="director-secondary-btn" href="%s" onclick="return confirm(\'Reactivate this associate?\');">Reactivate</a></td>
			</tr>',
			esc_html( $associate->display_name ),
			esc_html( $associate->user_email ),
			esc_html( $last_activity ),
			esc_html( $plan_progress ),
			esc_url( $reactivate_url )
		);
	}

	if ( empty( $rows ) ) {
		return $styles . '
			<div class="director-page-panel">
				' . $success_notice . '
				<div class="director-page-header-row">
					<div>
						<h1 class="director-page-title">Inactive Associates</h1>
						<p class="director-page-subtitle">Review and reactivate previously deactivated associates.</p>
					</div>
					<a class="director-secondary-btn" href="' . esc_url( home_url( '/partner/associates/' ) ) . '">Back to My Associates</a>
				</div>
				<div class="director-empty-state">No inactive associates found.</div>
			</div>
		';
	}

	return $styles . '
		<div class="director-page-panel">
			' . $success_notice . '
			<div class="director-page-header-row">
				<div>
					<h1 class="director-page-title">Inactive Associates</h1>
					<p class="director-page-subtitle">Review and reactivate previously deactivated associates.</p>
				</div>
				<a class="director-secondary-btn" href="' . esc_url( home_url( '/partner/associates/' ) ) . '">Back to My Associates</a>
			</div>
			<div class="director-associates-table-wrap">
				<table class="director-associates-table">
					<thead>
						<tr>
							<th>Associate Name</th>
							<th>Email</th>
							<th>Last Activity</th>
							<th>Plan Progress</th>
							<th>Reactivate</th>
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
