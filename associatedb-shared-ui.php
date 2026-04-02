<?php
/**
 * Plugin Name: AssociateDB Shared UI Helpers
 * Description: Backward-compatible helper wrapper after moving shared portal CSS to enqueued assets.
 * Version: 1.1.0
 *
 * Shortcodes provided: none.
 * Target pages: Shared across director + associate portal views.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pitblado_get_director_shared_styles' ) ) {
	/**
	 * Legacy compatibility shim.
	 *
	 * Existing shortcodes call this helper and previously expected inline style output.
	 * CSS is now centrally enqueued by associatedb-shared-ui-loader.php.
	 *
	 * @return string
	 */
	function pitblado_get_director_shared_styles() {
		return '';
	}
}
