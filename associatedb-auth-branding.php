<?php
/**
 * Plugin Name: AssociateDB Auth Branding
 * Description: Shared login branding and password-reset UX helpers.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_password_reset', function( $user, $new_pass ) {
	if ( headers_sent() || ! ( $user instanceof WP_User ) ) {
		return;
	}

	$username = $user->user_login;

	if ( '' === $username ) {
		return;
	}

	setcookie(
		'pitblado_prefill_username',
		rawurlencode( $username ),
		time() + 600,
		COOKIEPATH ? COOKIEPATH : '/',
		COOKIE_DOMAIN,
		is_ssl(),
		false
	);

	wp_safe_redirect( home_url( '/?reset=success' ) );
	exit;
}, 10, 2 );

add_action( 'wp_footer', function() {
	if ( ! is_front_page() || empty( $_COOKIE['pitblado_prefill_username'] ) ) {
		return;
	}

	$username = sanitize_user( wp_unslash( rawurldecode( $_COOKIE['pitblado_prefill_username'] ) ), true );
	if ( '' === $username ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var selectors = ['input[name="username"]', 'input[name="log"]', 'input[name="user_login"]', '#user_login'];
		for (var i = 0; i < selectors.length; i++) {
			var field = document.querySelector(selectors[i]);
			if (field) {
				if (!field.value) {
					field.value = <?php echo wp_json_encode( $username ); ?>;
				}
				break;
			}
		}
	});
	</script>
	<?php

	setcookie( 'pitblado_prefill_username', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
}, 100 );

add_action( 'login_enqueue_scripts', function() {
	$logo_url = 'https://pitblado.niva10.com/wp-content/uploads/2026/03/pitblado_logo.svg';
	?>
	<style>
		body.login { background: #f7f8fa; font-family: inherit; }
		body.login div#login { width: 100%; max-width: 420px; padding: 40px 20px; }
		body.login div#login h1 a, body.login h1 a { background-image: url('<?php echo esc_url( $logo_url ); ?>') !important; background-size: contain !important; background-position: center center !important; background-repeat: no-repeat !important; width: 280px !important; height: 90px !important; max-width: 100% !important; display: block !important; margin-bottom: 20px !important; }
		body.login form { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08); padding: 28px; }
		body.login label { color: #1f2937; font-size: 14px; font-weight: 600; }
		body.login input[type="text"], body.login input[type="password"], body.login input[type="email"] { border: 1px solid #d1d5db; border-radius: 10px; padding: 10px 12px; min-height: 44px; box-shadow: none; }
		body.login input[type="text"]:focus, body.login input[type="password"]:focus, body.login input[type="email"]:focus { border-color: #ef626c; box-shadow: 0 0 0 3px rgba(239, 98, 108, 0.15); }
		body.login .button-primary { background: #ef626c !important; border-color: #ef626c !important; border-radius: 10px !important; min-height: 42px; padding: 0 18px !important; box-shadow: none !important; text-shadow: none !important; }
		body.login .button-primary:hover, body.login .button-primary:focus { background: #d94f59 !important; border-color: #d94f59 !important; }
		body.login #nav, body.login #backtoblog { text-align: center; }
		body.login #nav a, body.login #backtoblog a { color: #041c32; }
		body.login .message, body.login .notice, body.login .success { border-left: 4px solid #ef626c; border-radius: 10px; }
		body.login #login_error, body.login .message, body.login .success, body.login .notice { background: #ffffff; border-radius: 10px; }
	</style>
	<?php
} );
