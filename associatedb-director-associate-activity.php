<?php
/**
 * Plugin Name: AssociateDB Director Associate Activity
 * Description: Director-focused single associate activity page shell + GravityView wrapper.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'director_associate_activity_page', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$associate_id = pitblado_get_requested_associate_id();
	$associate    = pitblado_get_manageable_associate( $associate_id );
	$styles       = function_exists( 'pitblado_get_director_shared_styles' ) ? pitblado_get_director_shared_styles() : '';

	if ( is_wp_error( $associate ) ) {
		return $styles . '<div class="director-empty-state">' . esc_html( $associate->get_error_message() ) . '</div>';
	}

	$overview_url     = add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/overview/' ) );
	$all_activity_url = home_url( '/director/activity/' );
	$view_shortcode   = sprintf(
		'[gravityview id="708" secret="f4726efa8f9c" search_field="created_by" search_operator="is" search_value="%d" page_size="15" sort_direction="DESC" back_link_label="← Back to Associate Activity"]',
		$associate_id
	);

	ob_start();
	?>
	<?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="director-page-panel">
		<div class="director-page-header-row">
			<div>
				<h1 class="director-page-title">Associate Activity</h1>
				<p class="director-page-subtitle"><?php echo esc_html( $associate->display_name ); ?> · <?php echo esc_html( $associate->user_email ); ?></p>
			</div>
			<div class="director-overview-actions">
				<a class="director-secondary-btn" href="<?php echo esc_url( $overview_url ); ?>">Back to Overview</a>
				<a class="director-secondary-btn" href="<?php echo esc_url( $all_activity_url ); ?>">All Activity</a>
			</div>
		</div>
	</div>

	<div class="director-page-panel">
		<?php echo do_shortcode( $view_shortcode ); ?>
	</div>
	<?php
	return ob_get_clean();
} );
