<?php
/**
 * Plugin Name: AssociateDB Director Assigned GravityView Filters
 * Description: Restricts /partner/activity and /partner/plans GravityView content to accessible associates and injects context panels.
 * Version: 1.1.0
 *
 * Shortcodes: none.
 * Target pages: /partner/activity/, /partner/plans/
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

		return trailingslashit( $path ) === '/partner/activity/';
	}
}

if ( ! function_exists( 'pitblado_is_director_plans_page_request' ) ) {
	function pitblado_is_director_plans_page_request() {
		$path = wp_parse_url( (string) home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ), PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			$path = '/';
		}

		return trailingslashit( $path ) === '/partner/plans/';
	}
}


if ( ! function_exists( 'pitblado_get_current_director_associate_ids' ) ) {
	/**
	 * Get active associate user IDs that are currently assigned to the logged-in partner/director.
	 *
	 * This intentionally reads the current associate user meta relationship instead of any
	 * Gravity Forms helper field that may have been captured at submission time.
	 *
	 * @return int[]
	 */
	function pitblado_get_current_director_associate_ids() {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$associates = pitblado_get_active_associates_for_director( get_current_user_id() );
		if ( empty( $associates ) ) {
			return array();
		}

		return array_values( array_unique( array_map( 'intval', wp_list_pluck( $associates, 'ID' ) ) ) );
	}
}

if ( ! function_exists( 'pitblado_entry_associate_user_id' ) ) {
	/**
	 * Resolve the associate user ID represented by a Gravity Forms/GravityView entry.
	 *
	 * Form 1 and Form 4 entries are owned by the associate via created_by. Forms 12 and 34
	 * are supported for existing migrated/helper views that store the associate in hidden
	 * fields 25 and 90 respectively.
	 *
	 * @param array|ArrayAccess $entry GravityView entry object/array.
	 * @return int
	 */
	function pitblado_entry_associate_user_id( $entry ) {
		$form_id = absint( $entry['form_id'] ?? ( $entry->form_id ?? 0 ) );

		if ( 12 === $form_id ) {
			return absint( $entry['25'] ?? 0 );
		}

		if ( 34 === $form_id ) {
			return absint( $entry['90'] ?? 0 );
		}

		return absint( $entry['created_by'] ?? 0 );
	}
}

if ( ! function_exists( 'pitblado_render_gravityview_shortcode_without_stale_director_filters' ) ) {
	/**
	 * Re-render partner Activity/Plans GravityView shortcodes without stale shortcode filters.
	 *
	 * Partner-facing aggregate views are filtered after GravityView loads entries by checking
	 * the current associate assignment relationship. Shortcode-level filters such as
	 * search_field="23" search_value="{user:ID}" can exclude valid currently assigned
	 * associates before that current-assignment check runs, so they are removed here.
	 *
	 * @param array $attr GravityView shortcode attributes.
	 * @return string
	 */
	function pitblado_render_gravityview_shortcode_without_stale_director_filters( $attr ) {
		$attr = is_array( $attr ) ? $attr : array();

		foreach ( array( 'search_field', 'search_operator', 'search_value' ) as $stale_filter_attr ) {
			unset( $attr[ $stale_filter_attr ] );
		}

		$parts = array();
		foreach ( $attr as $key => $value ) {
			if ( is_int( $key ) || is_array( $value ) || is_object( $value ) ) {
				continue;
			}

			$parts[] = sanitize_key( $key ) . '="' . esc_attr( (string) $value ) . '"';
		}

		$GLOBALS['pitblado_rendering_current_assignment_gravityview'] = true;
		$output = do_shortcode( '[gravityview ' . implode( ' ', $parts ) . ']' );
		unset( $GLOBALS['pitblado_rendering_current_assignment_gravityview'] );

		return $output;
	}
}

if ( ! function_exists( 'pitblado_get_director_page_context_panel' ) ) {
	function pitblado_get_director_page_context_panel( WP_User $associate, $mode ) {
		$associate_id   = (int) $associate->ID;
		$overview_url   = add_query_arg( 'associate_id', $associate_id, home_url( '/partner/associates/overview/' ) );
		$activity_url   = home_url( '/partner/activity/' );
		$plans_url      = home_url( '/partner/plans/' );
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

add_filter( 'pre_do_shortcode_tag', 'pitblado_strip_stale_director_gravityview_shortcode_filters', 10, 4 );
function pitblado_strip_stale_director_gravityview_shortcode_filters( $return, $tag, $attr, $m ) {
	if ( 'gravityview' !== $tag || ! is_user_logged_in() ) {
		return $return;
	}

	if ( ! empty( $GLOBALS['pitblado_rendering_current_assignment_gravityview'] ) ) {
		return $return;
	}

	$is_director_assignment_page = pitblado_is_director_activity_page_request() || pitblado_is_director_plans_page_request();
	if ( ! $is_director_assignment_page ) {
		return $return;
	}

	if ( function_exists( 'pitblado_current_user_is_global_admin' ) && pitblado_current_user_is_global_admin() && empty( $_GET['associate_id'] ) ) {
		return $return;
	}

	$attr = is_array( $attr ) ? $attr : array();
	if ( ! isset( $attr['search_field'], $attr['search_value'] ) ) {
		return $return;
	}

	return pitblado_render_gravityview_shortcode_without_stale_director_filters( $attr );
}

add_filter( 'gravityview/view/entries', 'adb_filter_entries_by_current_director', 10, 3 );
function adb_filter_entries_by_current_director( $entries, $view = null, $request = null ) {
	$target_view_ids = array( 101, 102, 528, 708, 714 );
	$view_id         = $view && isset( $view->ID ) ? (int) $view->ID : 0;
	$is_activity_page = pitblado_is_director_activity_page_request();
	$is_plans_page    = pitblado_is_director_plans_page_request();

	if ( ! ( $is_activity_page || $is_plans_page || in_array( $view_id, $target_view_ids, true ) ) ) {
		return $entries;
	}

	if ( ! is_user_logged_in() ) {
		return new \GV\Entry_Collection();
	}

	if ( ! $entries instanceof \GV\Entry_Collection ) {
		return $entries;
	}

	$requested_entry   = isset( $_GET['entry'] ) ? absint( $_GET['entry'] ) : 0;
	$associate_context = null;

	if ( ( $is_activity_page || $is_plans_page ) && isset( $_GET['associate_id'] ) ) {
		$associate_context = pitblado_resolve_requested_manageable_associate();
		if ( is_wp_error( $associate_context ) ) {
			return new \GV\Entry_Collection();
		}
	}

	if ( function_exists( 'pitblado_current_user_is_global_admin' ) && pitblado_current_user_is_global_admin() && ! $associate_context ) {
		return $entries;
	}

	$allowed_associate_ids = array();
	if ( ! $associate_context ) {
		$allowed_associate_ids = pitblado_get_current_director_associate_ids();
		if ( empty( $allowed_associate_ids ) ) {
			return new \GV\Entry_Collection();
		}
	}

	$return = new \GV\Entry_Collection();

	foreach ( $entries->all() as $entry ) {
		$associate_user_id = pitblado_entry_associate_user_id( $entry );

		if ( $associate_context instanceof WP_User ) {
			if ( $associate_user_id && $associate_user_id !== (int) $associate_context->ID ) {
				continue;
			}

			if ( $is_activity_page && $requested_entry > 0 && (int) $entry->ID !== $requested_entry ) {
				continue;
			}

			$return->add( $entry );
			continue;
		}

		if ( ! $associate_user_id || ! in_array( $associate_user_id, $allowed_associate_ids, true ) ) {
			continue;
		}

		$return->add( $entry );
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
