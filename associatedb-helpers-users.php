<?php
/**
 * Plugin Name: AssociateDB Helpers Users
 * Description: Shared helpers for active associate assignment, ownership checks, and inactive login blocking.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pitblado_get_requested_associate_id' ) ) {
	function pitblado_get_requested_associate_id() {
		return isset( $_GET['associate_id'] ) ? absint( $_GET['associate_id'] ) : 0;
	}
}


if ( ! function_exists( 'pitblado_resolve_requested_manageable_associate' ) ) {
	function pitblado_resolve_requested_manageable_associate() {
		$associate_id = pitblado_get_requested_associate_id();

		if ( ! $associate_id ) {
			return new WP_Error( 'missing_associate', __( 'No associate selected.', 'pitblado' ) );
		}

		return pitblado_get_manageable_associate( $associate_id );
	}
}


if ( ! function_exists( 'pitblado_get_manageable_associate' ) ) {
	/**
	 * Resolve an associate and verify current user can manage that associate.
	 *
	 * @param int $associate_id Associate user ID.
	 * @param bool $allow_inactive Whether inactive associates are still allowed for access checks.
	 * @return WP_User|WP_Error
	 */
	function pitblado_get_manageable_associate( $associate_id, $allow_inactive = false ) {
		$associate_id = absint( $associate_id );

		if ( ! $associate_id ) {
			return new WP_Error( 'missing_associate', __( 'No associate selected.', 'pitblado' ) );
		}

		$associate = pitblado_get_associate_user( $associate_id );
		if ( ! $associate ) {
			return new WP_Error( 'invalid_associate', __( 'Associate not found.', 'pitblado' ) );
		}

		if ( current_user_can( 'manage_options' ) ) {
			return $associate;
		}

		if ( ! $allow_inactive && pitblado_is_associate_inactive( $associate_id ) ) {
			return new WP_Error( 'forbidden_associate', __( 'You do not have access to this associate.', 'pitblado' ) );
		}

		$assigned_director = absint( get_user_meta( $associate_id, 'assigned_director', true ) );
		if ( $assigned_director !== get_current_user_id() ) {
			return new WP_Error( 'forbidden_associate', __( 'You do not have access to this associate.', 'pitblado' ) );
		}

		return $associate;
	}
}

if ( ! function_exists( 'pitblado_get_associate_user' ) ) {
	function pitblado_get_associate_user( $associate_id ) {
		$user = get_user_by( 'id', absint( $associate_id ) );

		if ( ! $user ) {
			return false;
		}

		if ( ! in_array( 'associate', (array) $user->roles, true ) ) {
			return false;
		}

		return $user;
	}
}

if ( ! function_exists( 'pitblado_is_associate_inactive' ) ) {
	function pitblado_is_associate_inactive( $associate_id ) {
		return get_user_meta( absint( $associate_id ), 'associate_status', true ) === 'inactive';
	}
}

if ( ! function_exists( 'pitblado_get_active_associates_for_director' ) ) {
	function pitblado_get_active_associates_for_director( $director_id ) {
		$director_id = absint( $director_id );

		if ( ! $director_id ) {
			return array();
		}

		return get_users(
			array(
				'role'       => 'associate',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => 'assigned_director',
						'value' => $director_id,
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'associate_status',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'associate_status',
							'value'   => 'inactive',
							'compare' => '!=',
						),
					),
				),
			)
		);
	}
}


if ( ! function_exists( 'pitblado_get_all_active_associates' ) ) {
	function pitblado_get_all_active_associates() {
		return get_users(
			array(
				'role'       => 'associate',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => 'associate_status',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'associate_status',
						'value'   => 'inactive',
						'compare' => '!=',
					),
				),
			)
		);
	}
}

if ( ! function_exists( 'pitblado_get_inactive_associates_for_director' ) ) {
	function pitblado_get_inactive_associates_for_director( $director_id ) {
		$director_id = absint( $director_id );

		if ( ! $director_id ) {
			return array();
		}

		return get_users(
			array(
				'role'       => 'associate',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => 'assigned_director',
						'value' => $director_id,
					),
					array(
						'key'   => 'associate_status',
						'value' => 'inactive',
					),
				),
			)
		);
	}
}

if ( ! function_exists( 'pitblado_get_all_inactive_associates' ) ) {
	function pitblado_get_all_inactive_associates() {
		return get_users(
			array(
				'role'       => 'associate',
				'meta_query' => array(
					array(
						'key'   => 'associate_status',
						'value' => 'inactive',
					),
				),
			)
		);
	}
}

if ( ! function_exists( 'pitblado_current_user_can_manage_associate' ) ) {
	function pitblado_current_user_can_manage_associate( $associate_id ) {
		$associate_id = absint( $associate_id );

		if ( ! is_user_logged_in() || ! $associate_id ) {
			return false;
		}

		return ! is_wp_error( pitblado_get_manageable_associate( $associate_id ) );
	}
}

if ( ! function_exists( 'pitblado_current_user_can_reactivate_associate' ) ) {
	function pitblado_current_user_can_reactivate_associate( $associate_id ) {
		$associate_id = absint( $associate_id );

		if ( ! is_user_logged_in() || ! $associate_id ) {
			return false;
		}

		return ! is_wp_error( pitblado_get_manageable_associate( $associate_id, true ) );
	}
}

add_filter(
	'authenticate',
	function( $user, $username, $password ) {
		if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
			return $user;
		}

		if ( in_array( 'associate', (array) $user->roles, true ) && pitblado_is_associate_inactive( $user->ID ) ) {
			return new WP_Error(
				'associate_inactive',
				__( 'This account has been deactivated. Please contact an administrator.', 'pitblado' )
			);
		}

		return $user;
	},
	30,
	3
);
