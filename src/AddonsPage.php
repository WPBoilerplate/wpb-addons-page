<?php

namespace WPBoilerplate\AddonsPage;

/**
 * Public entrypoint for the Add-ons page package.
 *
 * Usage:
 *   new \WPBoilerplate\AddonsPage\AddonsPage(
 *       'your-plugin-menu-slug',
 *       __FILE__,
 *       [
 *           'fs_product_id'  => '12345',
 *           'fs_public_key'  => 'pk_your_public_key',
 *           'fs_slug'        => 'your-plugin-slug', // optional, defaults to menu_slug
 *       ]
 *   );
 */
class AddonsPage {

	/** @var string */
	private $menu_slug;

	/** @var string */
	private $consumer_main_file;

	/** @var FreemiusBridge */
	private $fs_bridge;

	/** @var AddonsRegistry */
	private $registry;

	/** @var MenuRegistrar */
	private $menu_registrar;

	/** @var PageRenderer */
	private $renderer;

	/** @var Assets */
	private $assets;

	/** @var AjaxHandlers */
	private $ajax;

	/** @var Notices */
	private $notices;

	/** @var PendingAddon */
	private $pending;

	/** @var string|null Hook suffix returned by add_submenu_page(). */
	private $hook_suffix = null;

	/**
	 * @param string      $menu_slug           Parent menu slug (the slug passed to add_menu_page()).
	 * @param string|null $consumer_main_file  Absolute path to the consumer plugin's main file (__FILE__).
	 * @param array       $args                Required Freemius credentials + optional config:
	 *                                           'fs_product_id'  (required) — Freemius product ID.
	 *                                           'fs_public_key'  (required) — Freemius public key (pk_...).
	 *                                           'fs_slug'        (optional) — Freemius product slug; defaults to $menu_slug.
	 *
	 * @throws \InvalidArgumentException If fs_product_id or fs_public_key are missing from $args.
	 * @throws \RuntimeException         If WordPress version < 6.0, or no valid plugin main file is found.
	 */
	public function __construct( string $menu_slug, ?string $consumer_main_file = null, array $args = [] ) {
		$this->assert_wp_version();

		$this->menu_slug          = sanitize_key( $menu_slug );
		$this->consumer_main_file = $this->resolve_consumer_file( $consumer_main_file );

		$fs_product_id = isset( $args['fs_product_id'] ) ? (string) $args['fs_product_id'] : '';
		$fs_public_key = isset( $args['fs_public_key'] ) ? (string) $args['fs_public_key'] : '';
		$fs_slug       = isset( $args['fs_slug'] ) ? sanitize_key( $args['fs_slug'] ) : $this->menu_slug;

		if ( '' === $fs_product_id || '' === $fs_public_key ) {
			throw new \InvalidArgumentException(
				'AddonsPage: fs_product_id and fs_public_key are required. ' .
				"Pass them via \$args: new AddonsPage( \$slug, __FILE__, [ 'fs_product_id' => '...', 'fs_public_key' => 'pk_...' ] )"
			);
		}

		$fs_instance     = FreemiusInitializer::init( $this->consumer_main_file, $this->menu_slug, $fs_product_id, $fs_public_key, $fs_slug );
		$this->fs_bridge = new FreemiusBridge( $fs_instance );
		$this->registry  = new AddonsRegistry();
		$this->notices   = new Notices();
		$this->pending   = new PendingAddon();

		$button_state        = new ButtonState( $this->fs_bridge );
		$this->renderer      = new PageRenderer( $this->registry, $this->fs_bridge, $button_state, $this->pending, $this->menu_slug );
		$this->menu_registrar = new MenuRegistrar( $this->menu_slug, $this->renderer );

		$package_dir = dirname( __DIR__ );
		$package_url = $this->detect_package_url( $package_dir );
		$this->assets = new Assets( $package_url, $package_dir, $this->menu_slug, $this->registry, $this->fs_bridge, $button_state );

		$this->ajax = new AjaxHandlers( $this->registry, new Installer(), $this->fs_bridge, $button_state );

		$this->boot();
	}

	/** Register all WordPress hooks. */
	private function boot(): void {
		add_action( 'admin_menu', [ $this->menu_registrar, 'register' ], 20 );
		add_action( 'admin_menu', [ $this, 'capture_hook_suffix' ], 21 );
		add_action( 'admin_init', [ $this->pending, 'maybe_handle_return' ] );
		add_action( 'admin_enqueue_scripts', [ $this->assets, 'enqueue' ] );
		add_action( 'admin_notices', [ $this->notices, 'render' ] );
		add_action( 'wp_ajax_wpb_addons_install_free', [ $this->ajax, 'install_free' ] );
		add_action( 'wp_ajax_wpb_addons_activate', [ $this->ajax, 'activate' ] );
		add_action( 'admin_post_wpb_addons_connect_again', [ $this, 'handle_connect_again' ] );
	}

	/** Stores the hook suffix after the submenu is registered so Assets can gate on it. */
	public function capture_hook_suffix(): void {
		$this->hook_suffix = $this->menu_registrar->get_hook_suffix();
		$this->assets->set_hook_suffix( $this->hook_suffix );
	}

	/** admin_post handler: resets Freemius anonymous mode and redirects to opt-in page. */
	public function handle_connect_again(): void {
		check_admin_referer( 'wpb_addons_connect', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wpb-addons-page' ) );
		}

		$slug = isset( $_GET['slug'] ) ? sanitize_key( $_GET['slug'] ) : '';
		if ( $slug && AddonsRegistry::find( $slug ) ) {
			$this->pending->set( $slug );
		}

		// trigger_connect_again() calls fs->connect_again() which resets anonymous mode
		// and internally calls fs_redirect() to the Freemius opt-in page — so it exits there.
		$this->fs_bridge->trigger_connect_again();

		// Fallback: if already connected or connect_again() unavailable, return to add-ons page.
		wp_safe_redirect( add_query_arg(
			[ 'page' => 'wpb-addons', 'wpb_addons_return' => '1' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function assert_wp_version(): void {
		global $wp_version;
		if ( isset( $wp_version ) && version_compare( $wp_version, '6.0', '<' ) ) {
			throw new \RuntimeException(
				'WPBoilerplate AddonsPage requires WordPress 6.0 or higher. ' .
				"Current version: {$wp_version}"
			);
		}
	}

	private function resolve_consumer_file( ?string $file ): string {
		if ( null !== $file ) {
			if ( ! file_exists( $file ) ) {
				throw new \RuntimeException(
					"AddonsPage: consumer_main_file does not exist: {$file}"
				);
			}
			return $file;
		}
		// Fallback: auto-detect via path math.
		return ConsumerPluginLocator::detect();
	}

	private function detect_package_url( string $package_dir ): string {
		// The package sits inside the consumer's vendor/ directory.
		// Walk up to WP_PLUGIN_DIR to calculate the URL.
		if ( defined( 'WP_PLUGIN_DIR' ) && defined( 'WP_PLUGIN_URL' ) ) {
			$relative = str_replace( WP_PLUGIN_DIR, '', $package_dir );
			if ( $relative !== $package_dir ) {
				return trailingslashit( WP_PLUGIN_URL . $relative );
			}
		}
		// Fallback: use plugins_url() with a file inside the package.
		return trailingslashit( plugins_url( '', $this->consumer_main_file ) . '/vendor/wpboilerplate/addons-page' );
	}
}
