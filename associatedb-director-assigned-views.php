<?php
/**
 * Filter director GravityView output so directors only see entries they can access.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pitblado_is_director_activity_page_request' ) ) {
	function pitblado_is_director_activity_page_request() {
		$path = wp_parse_url( (string) home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ), PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			$path = '/';
		}

		return trailingslashit( $path ) === '/director/activity/';
	}
}

if ( ! function_exists( 'pitblado_is_director_plans_page_request' ) ) {
	function pitblado_is_director_plans_page_request() {
		$path = wp_parse_url( (string) home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ), PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			$path = '/';
		}

		return trailingslashit( $path ) === '/director/plans/';
	}
}

if ( ! function_exists( 'pitblado_get_director_page_context_panel' ) ) {
	function pitblado_get_director_page_context_panel( WP_User $associate, $mode ) {
		$associate_id   = (int) $associate->ID;
		$overview_url   = add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/overview/' ) );
		$activity_url   = home_url( '/director/activity/' );
		$plans_url      = home_url( '/director/plans/' );
		$all_label      = 'plans' === $mode ? 'Back to All Plans' : 'Back to All Activity';
		$all_url        = 'plans' === $mode ? $plans_url : $activity_url;
		$focused_label  = 'plans' === $mode ? 'Associate Plan' : 'Associate Activity';

		$styles = function_exists( 'pitblado_get_director_shared_styles' ) ? pitblado_get_director_shared_styles() : '';

		ob_start();
		?>
		<div class="director-page-panel">
			<div class="director-panel-header">
				<div>
					<div class="director-panel-title"><?php echo esc_html( $focused_label ); ?></div>
					<div class="director-panel-subtitle"><?php echo esc_html( $associate->display_name ); ?> · <?php echo esc_html( $associate->user_email ); ?></div>
				</div>
				<div class="director-overview-actions">
					<a class="director-secondary-btn" href="<?php echo esc_url( $overview_url ); ?>">Back to Associate Overview</a>
					<a class="director-secondary-btn" href="<?php echo esc_url( $all_url ); ?>"><?php echo esc_html( $all_label ); ?></a>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'pitblado_render_director_associate_plan_focus' ) ) {
	function pitblado_render_director_associate_plan_focus( WP_User $associate ) {
		if ( ! class_exists( 'GFAPI' ) ) {
			$styles = function_exists( 'pitblado_get_director_shared_styles' ) ? pitblado_get_director_shared_styles() : '';
			return $styles . '<div class="director-empty-state">Plans are currently unavailable.</div>';
		}

		$entries = GFAPI::get_entries(
			4,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'   => 'created_by',
						'value' => (int) $associate->ID,
					),
				),
			),
			array(
				'key'       => 'date_created',
				'direction' => 'DESC',
			),
			array(
				'offset'    => 0,
				'page_size' => 1,
			)
		);

		if ( is_wp_error( $entries ) || empty( $entries ) ) {
			return '<div class="director-page-panel"><div class="director-empty-state">No plan submitted yet for this associate.</div></div>';
		}

		$entry = $entries[0];

		$annual_focus = trim( (string) rgar( $entry, '14' ) );
		$q1           = trim( (string) rgar( $entry, '16' ) );
		$q2           = trim( (string) rgar( $entry, '20' ) );
		$q3           = trim( (string) rgar( $entry, '22' ) );
		$q4           = trim( (string) rgar( $entry, '26' ) );
		$progress     = trim( (string) rgar( $entry, '32' ) );
		$thirty_day   = trim( (string) rgar( $entry, '36' ) );

		$month = (int) current_time( 'n' );
		if ( $month <= 3 ) {
			$current_q = 'Q1';
			$current   = $q1;
		} elseif ( $month <= 6 ) {
			$current_q = 'Q2';
			$current   = $q2;
		} elseif ( $month <= 9 ) {
			$current_q = 'Q3';
			$current   = $q3;
		} else {
			$current_q = 'Q4';
			$current   = $q4;
		}

		ob_start();
		?>
		<div class="director-page-panel">
			<div class="director-panel-header">
				<div>
					<div class="director-panel-title">Latest Submitted Plan</div>
					<div class="director-panel-subtitle">Displaying the newest Form 4 entry for this associate.</div>
				</div>
			</div>
			<div class="director-plan-grid">
				<div>
					<div class="director-mini-stat-label">Annual Focus</div>
					<div class="director-plan-text"><?php echo esc_html( $annual_focus !== '' ? $annual_focus : '—' ); ?></div>
				</div>
				<div>
					<div class="director-mini-stat-label">Current Quarter</div>
					<div class="director-plan-text"><?php echo esc_html( $current_q ); ?></div>
				</div>
				<div>
					<div class="director-mini-stat-label">Quarter Objective</div>
					<div class="director-plan-text"><?php echo esc_html( $current !== '' ? $current : '—' ); ?></div>
				</div>
				<div>
					<div class="director-mini-stat-label">Plan Progress</div>
					<div class="director-plan-text"><?php echo esc_html( $progress !== '' ? absint( $progress ) . '%' : '—' ); ?></div>
				</div>
				<div>
					<div class="director-mini-stat-label">30-Day Commitment</div>
					<div class="director-plan-text"><?php echo esc_html( $thirty_day !== '' ? absint( $thirty_day ) . '%' : '—' ); ?></div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

add_filter( 'gravityview/view/entries', 'adb_filter_entries_by_current_director', 10, 3 );
function adb_filter_entries_by_current_director( $entries, $view = null, $request = null ) {
	$target_view_ids = array( 101, 102 );

	if ( ! $view || ! in_array( (int) $view->ID, $target_view_ids, true ) ) {
		return $entries;
	}

	if ( ! is_user_logged_in() ) {
		return new \GV\Entry_Collection();
	}

	if ( ! $entries instanceof \GV\Entry_Collection ) {
		return $entries;
	}

	$is_activity_page  = pitblado_is_director_activity_page_request();
	$is_plans_page     = pitblado_is_director_plans_page_request();
	$requested_entry   = isset( $_GET['entry'] ) ? absint( $_GET['entry'] ) : 0;
	$associate_context = null;

	if ( ( $is_activity_page || $is_plans_page ) && isset( $_GET['associate_id'] ) ) {
		$associate_context = pitblado_resolve_requested_manageable_associate();
		if ( is_wp_error( $associate_context ) ) {
			return new \GV\Entry_Collection();
		}
	}

	if ( current_user_can( 'manage_options' ) && ! $associate_context ) {
		return $entries;
	}

	$return = new \GV\Entry_Collection();

	foreach ( $entries->all() as $entry ) {
		$form_id = (int) $entry->form_id;

		if ( $associate_context instanceof WP_User ) {
			$associate_id = (int) $associate_context->ID;
			$entry_owner  = absint( $entry['created_by'] ?? 0 );

			if ( $entry_owner && $entry_owner !== $associate_id ) {
				continue;
			}

			if ( 12 === $form_id ) {
				$entry_associate = absint( $entry['25'] ?? 0 );
				if ( $entry_associate && $entry_associate !== $associate_id ) {
					continue;
				}
			} elseif ( 34 === $form_id ) {
				$entry_associate = absint( $entry['90'] ?? 0 );
				if ( $entry_associate && $entry_associate !== $associate_id ) {
					continue;
				}
			}

			if ( $is_activity_page && $requested_entry > 0 && (int) $entry->ID !== $requested_entry ) {
				continue;
			}

			$return->add( $entry );
			continue;
		}

		if ( current_user_can( 'manage_options' ) ) {
			$return->add( $entry );
			continue;
		}

		if ( 12 === $form_id ) {
			$associate_user_id = absint( $entry['25'] ?? 0 );
		} elseif ( 34 === $form_id ) {
			$associate_user_id = absint( $entry['90'] ?? 0 );
		} else {
			$associate_user_id = absint( $entry['created_by'] ?? 0 );
		}

		if ( ! $associate_user_id ) {
			continue;
		}

		if ( pitblado_current_user_can_manage_associate( $associate_user_id ) ) {
			$return->add( $entry );
		}
	}

	return $return;
}

add_filter( 'the_content', function( $content ) {
	if ( ! is_user_logged_in() ) {
		return $content;
	}

	$is_activity_page = pitblado_is_director_activity_page_request();
	$is_plans_page    = pitblado_is_director_plans_page_request();

	if ( ! $is_activity_page && ! $is_plans_page ) {
		return $content;
	}

	if ( ! isset( $_GET['associate_id'] ) ) {
		return $content;
	}

	$associate_context = pitblado_resolve_requested_manageable_associate();
	if ( is_wp_error( $associate_context ) ) {
		return '<div class="director-page-panel"><div class="director-empty-state">' . esc_html( $associate_context->get_error_message() ) . '</div></div>';
	}

	$panel = pitblado_get_director_page_context_panel( $associate_context, $is_plans_page ? 'plans' : 'activity' );

	if ( $is_plans_page ) {
		return $panel . pitblado_render_director_associate_plan_focus( $associate_context );
	}

	return $panel . $content;
}, 9 );
