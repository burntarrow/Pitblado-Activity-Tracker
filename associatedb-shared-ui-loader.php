<?php
/**
 * Plugin Name: AssociateDB Shared UI Loader
 * Description: Enqueues centralized portal UI CSS bundles for director and associate pages.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pitblado_is_portal_page_request' ) ) {
	function pitblado_is_portal_page_request() {
		if ( is_admin() || ! is_user_logged_in() ) {
			return false;
		}

		$request_path = wp_parse_url( (string) home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ), PHP_URL_PATH );
		if ( ! is_string( $request_path ) ) {
			return false;
		}

		$request_path = trailingslashit( $request_path );

		return 0 === strpos( $request_path, '/director/' ) || 0 === strpos( $request_path, '/associate/' );
	}
}

if ( ! function_exists( 'pitblado_enqueue_shared_ui_css' ) ) {
	function pitblado_enqueue_shared_ui_css() {
		if ( ! pitblado_is_portal_page_request() ) {
			return;
		}

		$css_base_dir = __DIR__ . '/assets/css/';
		$css_base_url = plugin_dir_url( __FILE__ ) . 'assets/css/';

		$styles = array(
			'associatedb-shared-ui' => 'associatedb-shared-ui.css',
			'associatedb-director'  => 'associatedb-director.css',
			'associatedb-associate' => 'associatedb-associate.css',
		);

		foreach ( $styles as $handle => $file ) {
			$file_path = $css_base_dir . $file;
			if ( ! file_exists( $file_path ) ) {
				continue;
			}

			wp_enqueue_style( $handle, $css_base_url . $file, array(), (string) filemtime( $file_path ) );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'pitblado_enqueue_shared_ui_css', 20 );
