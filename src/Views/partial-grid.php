<?php
/**
 * Add-ons grid.
 *
 * @var array        $addons
 * @var string|null  $pending_slug
 * @var \AcrossAI_Addon\PageRenderer $renderer
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $addons ) ) : ?>
	<p><?php esc_html_e( 'No add-ons available.', 'wpb-addons-page' ); ?></p>
<?php return; endif; ?>

<div class="wpb-addons-page__grid" role="tabpanel">
	<?php foreach ( $addons as $addon ) :
		$renderer->render_partial( 'partial-card', [
			'addon'        => $addon,
			'pending_slug' => $pending_slug,
		] );
	endforeach; ?>
</div>
