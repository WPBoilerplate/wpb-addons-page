<?php

namespace AcrossAI_Addon;

/**
 * Enqueues the Add-ons page CSS/JS and passes data to JavaScript.
 * Only fires on the Add-ons submenu page (gated on $hook_suffix).
 */
class Assets {

	/** @var string */
	private $package_url;

	/** @var string */
	private $package_dir;

	/** @var string */
	private $menu_slug;

	/** @var FreemiusBridge */
	private $fs_bridge;

	/** @var ButtonState */
	private $button_state;

	/** @var string|null */
	private $hook_suffix = null;

	public function __construct(
		string $package_url,
		string $package_dir,
		string $menu_slug,
		FreemiusBridge $fs_bridge,
		ButtonState $button_state
	) {
		$this->package_url  = $package_url;
		$this->package_dir  = $package_dir;
		$this->menu_slug    = $menu_slug;
		$this->fs_bridge    = $fs_bridge;
		$this->button_state = $button_state;
	}

	public function set_hook_suffix( ?string $hook_suffix ): void {
		$this->hook_suffix = $hook_suffix;
	}

	/** admin_enqueue_scripts callback. */
	public function enqueue( string $hook_suffix ): void {
		if ( null === $this->hook_suffix || $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		$asset_file = $this->package_dir . '/build/addons-page.asset.php';
		$asset      = file_exists( $asset_file ) ? require $asset_file : [
			'dependencies' => [],
			'version'      => '1.0.0',
		];

		// Unique handle per consumer menu slug to avoid wp_localize_script clobbering.
		$handle = 'wpb-addons-page-' . $this->menu_slug;

		wp_enqueue_style(
			$handle,
			$this->package_url . 'build/addons-page.css',
			[],
			$asset['version']
		);

		wp_enqueue_script(
			$handle,
			$this->package_url . 'build/addons-page.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Freemius checkout JS (only when user is opted in).
		$checkout_loaded = false;
		if ( $this->fs_bridge->is_registered() ) {
			$enqueue_checkout = apply_filters( 'wpb_addons_enqueue_freemius_checkout', true );
			if ( $enqueue_checkout ) {
				wp_enqueue_script(
					'wpb-fs-checkout',
					FreemiusBridge::checkout_js_url(),
					[],
					null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
					true
				);
				$checkout_loaded = true;
			}
		}

		// Build JS payload.
		$global_name = 'wpbAddonsPage_' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $this->menu_slug );
		wp_localize_script( $handle, $global_name, $this->build_payload( $checkout_loaded ) );
	}

	private function build_payload( bool $checkout_loaded ): array {
		$pending_addon = new PendingAddon();
		$pending_slug  = $pending_addon->get();

		$return_url = add_query_arg(
			[
				'page'              => 'wpb-addons',
				'wpb_addons_return' => '1',
			],
			admin_url( 'admin.php' )
		);

		$addons = [];
		foreach ( AddonsRegistry::all() as $addon ) {
			$state = $this->button_state->for_addon( $addon );
			$entry = array_merge( $addon, [ 'button_state' => $state ] );

			if ( 'freemius' === $addon['source'] && $this->fs_bridge->is_registered() ) {
				$entry['checkout_config'] = $this->fs_bridge->checkout_config( $addon );
			}

			$addons[] = $entry;
		}

		return [
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'wpb_addons_action' ),
			'textDomain'  => 'wpb-addons-page',
			'i18n'        => [
				'installing'    => __( 'Installing…', 'wpb-addons-page' ),
				'activating'    => __( 'Activating…', 'wpb-addons-page' ),
				'deactivating'  => __( 'Deactivating…', 'wpb-addons-page' ),
				'installed'     => __( '✓ Installed & Activated', 'wpb-addons-page' ),
				'activated'     => __( '✓ Activated', 'wpb-addons-page' ),
				'deactivated'   => __( '✓ Deactivated', 'wpb-addons-page' ),
				'installFailed' => __( 'Could not install. Please try again.', 'wpb-addons-page' ),
				'active'        => __( '● Active', 'wpb-addons-page' ),
				'retry'         => __( 'Retry', 'wpb-addons-page' ),
			],
			'freemius'    => [
				'isRegistered'    => $this->fs_bridge->is_registered(),
				'connectAgainUrl' => add_query_arg(
					[
						'action' => 'wpb_addons_connect_again',
						'nonce'  => wp_create_nonce( 'wpb_addons_connect' ),
					],
					admin_url( 'admin-post.php' )
				),
				'checkoutLoaded'  => $checkout_loaded,
			],
			'addons'      => $addons,
			'pendingSlug' => $pending_slug,
			'returnFlag'  => ! empty( $_GET['wpb_addons_return'] ), // phpcs:ignore WordPress.Security.NonceVerification
		];
	}
}
