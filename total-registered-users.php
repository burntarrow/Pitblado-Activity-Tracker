<?php
add_shortcode( 'director_total_associates', function() {
	if ( is_user_logged_in() && function_exists( 'pitblado_current_user_is_global_admin' ) && ! pitblado_current_user_is_global_admin() ) {
		$users = pitblado_get_active_associates_for_partner( get_current_user_id() );
	} else {
		$users = pitblado_get_all_active_associates();
	}

	return (string) count( $users );
} );
