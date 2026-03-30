<?php
add_shortcode( 'director_total_associates', function() {
	$users = get_users( array(
		'role'   => 'associate',
		'fields' => 'ID',
	) );

	return (string) count( $users );
} );