<?php
/**
 * After password reset:
 * - store username briefly in a cookie
 * - redirect user to homepage where [uwp_login] is displayed
 */
add_action( 'after_password_reset', function( $user, $new_pass ) {
	if ( headers_sent() || ! ( $user instanceof WP_User ) ) {
		return;
	}

	$username = $user->user_login;

	if ( $username === '' ) {
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

/**
 * On the homepage, prefill the UsersWP login username field if the cookie exists.
 */
add_action( 'wp_footer', function() {
	if ( ! is_front_page() ) {
		return;
	}

	if ( empty( $_COOKIE['pitblado_prefill_username'] ) ) {
		return;
	}

	$username = sanitize_user(
		wp_unslash( rawurldecode( $_COOKIE['pitblado_prefill_username'] ) ),
		true
	);

	if ( $username === '' ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var selectors = [
			'input[name="username"]',
			'input[name="log"]',
			'input[name="user_login"]',
			'#user_login'
		];

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

	setcookie(
		'pitblado_prefill_username',
		'',
		time() - 3600,
		COOKIEPATH ? COOKIEPATH : '/',
		COOKIE_DOMAIN,
		is_ssl(),
		false
	);
}, 100 );