<?php
/**
 * Plugin Name: AssociateDB Director Associate Plan
 * Description: Director-focused single associate plan page shell + GravityView wrapper.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'director_associate_plan_page', function() {
	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$associate_id = pitblado_get_requested_associate_id();
	$associate    = pitblado_get_manageable_associate( $associate_id );
	$styles       = function_exists( 'pitblado_get_director_shared_styles' ) ? pitblado_get_director_shared_styles() : '';

	if ( is_wp_error( $associate ) ) {
		return $styles . '<div class="director-empty-state">' . esc_html( $associate->get_error_message() ) . '</div>';
	}

	$overview_url  = add_query_arg( 'associate_id', $associate_id, home_url( '/director/associates/overview/' ) );
	$all_plans_url = home_url( '/director/plans/' );
	$view_shortcode = sprintf(
		'[gravityview id="714" secret="8ea7768df2a0" search_field="created_by" search_operator="is" search_value="%d" page_size="1" sort_direction="DESC"]',
		$associate_id
	);

	ob_start();
	?>
	<?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="director-page-panel">
		<div class="director-page-header-row">
			<div>
				<h1 class="director-page-title">Associate Plan</h1>
				<p class="director-page-subtitle"><?php echo esc_html( $associate->display_name ); ?> · <?php echo esc_html( $associate->user_email ); ?></p>
			</div>
			<div class="director-overview-actions">
				<a class="director-secondary-btn" href="<?php echo esc_url( $overview_url ); ?>">Back to Overview</a>
				<a class="director-secondary-btn" href="<?php echo esc_url( $all_plans_url ); ?>">All Plans</a>
			</div>
		</div>
	</div>

	<div class="director-page-panel">
		<?php echo do_shortcode( $view_shortcode ); ?>
	</div>
	<?php
	return ob_get_clean();
} );
