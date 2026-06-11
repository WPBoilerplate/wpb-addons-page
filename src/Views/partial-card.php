<?php
/**
 * Single add-on card.
 *
 * @var array       $addon         Add-on data + button_state.
 * @var string|null $pending_slug  Highlighted slug after opt-in return.
 */
defined( 'ABSPATH' ) || exit;

$state       = $addon['button_state'];
$is_pending  = ( $pending_slug && $pending_slug === $addon['slug'] );
$card_class  = 'wpb-addons-page__card';
$card_class .= ' wpb-addons-page__card--' . esc_attr( $addon['type'] );
if ( $is_pending ) {
	$card_class .= ' wpb-addons-page__card--highlight';
}
?>
<div
	class="<?php echo esc_attr( $card_class ); ?>"
	data-type="<?php echo esc_attr( $addon['type'] ); ?>"
	data-slug="<?php echo esc_attr( $addon['slug'] ); ?>"
	data-source="<?php echo esc_attr( $addon['source'] ); ?>"
	<?php if ( $is_pending ) : ?>aria-label="<?php esc_attr_e( 'This is the add-on you were trying to purchase', 'wpb-addons-page' ); ?>"<?php endif; ?>
>
	<div class="wpb-addons-page__card-icon">
		<img
			src="<?php echo esc_url( $addon['icon'] ); ?>"
			alt="<?php echo esc_attr( $addon['name'] ); ?>"
			width="72"
			height="72"
			loading="lazy"
		/>
	</div>

	<div class="wpb-addons-page__card-body">
		<h3 class="wpb-addons-page__card-title">
			<a href="<?php echo esc_url( $addon['more_url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $addon['name'] ); ?>
			</a>
		</h3>

		<p class="wpb-addons-page__card-desc"><?php echo esc_html( $addon['description'] ); ?></p>

		<div class="wpb-addons-page__card-meta">
			<?php if ( 'paid' === $addon['type'] && ! empty( $addon['price_label'] ) ) : ?>
				<span class="wpb-addons-page__price"><?php echo esc_html( $addon['price_label'] ); ?></span>
			<?php else : ?>
				<span class="wpb-addons-page__badge wpb-addons-page__badge--free"><?php esc_html_e( 'FREE', 'wpb-addons-page' ); ?></span>
			<?php endif; ?>

			<?php
			$source_labels = array(
				'wordpress.org' => 'WordPress.org',
				'github'        => 'GitHub',
				'freemius'      => 'Freemius',
			);
			$source_label = $source_labels[ $addon['source'] ] ?? ucfirst( $addon['source'] );
			$source_mod   = 'wpb-addons-page__source--' . str_replace( '.', '-', $addon['source'] );
			?>
			<span class="wpb-addons-page__source <?php echo esc_attr( $source_mod ); ?>">
				<?php echo esc_html( $source_label ); ?>
			</span>
		</div>
	</div>

	<div class="wpb-addons-page__card-footer">
		<button
			class="button <?php echo esc_attr( $state['css_class'] ); ?> wpb-addons-page__btn"
			data-action="<?php echo esc_attr( $state['action'] ); ?>"
			data-slug="<?php echo esc_attr( $addon['slug'] ); ?>"
			data-source="<?php echo esc_attr( $addon['source'] ); ?>"
			<?php if ( ! empty( $state['plugin_file'] ) ) : ?>
				data-plugin-file="<?php echo esc_attr( $state['plugin_file'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $addon['fs_product_id'] ) ) : ?>
				data-fs-product-id="<?php echo esc_attr( $addon['fs_product_id'] ); ?>"
				data-fs-plan-id="<?php echo esc_attr( $addon['fs_plan_id'] ); ?>"
				data-fs-public-key="<?php echo esc_attr( $addon['fs_public_key'] ); ?>"
			<?php endif; ?>
			<?php if ( ! $state['enabled'] ) : ?>
				disabled aria-disabled="true"
			<?php endif; ?>
			aria-label="<?php
				/* translators: 1: action label, 2: add-on name */
				echo esc_attr( sprintf( '%s %s', $state['label'], $addon['name'] ) );
			?>"
			<?php if ( ! empty( $state['tooltip'] ) ) : ?>
				title="<?php echo esc_attr( $state['tooltip'] ); ?>"
			<?php endif; ?>
		>
			<?php echo esc_html( $state['label'] ); ?>
		</button>

		<div class="wpb-addons-page__card-error" role="alert" aria-live="assertive" hidden></div>
	</div>
</div>
