<?php
add_shortcode( 'director_total_associates', function() {
	if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
		$users = pitblado_get_active_associates_for_director( get_current_user_id() );
	} else {
		$users = pitblado_get_all_active_associates();
	}

	return (string) count( $users );
} );
