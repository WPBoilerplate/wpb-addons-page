<?php

namespace WPBoilerplate\AddonsPage;

/**
 * Public entrypoint for the Add-ons page package.
 *
 * Usage:
 *   new \WPBoilerplate\AddonsPage\AddonsPage( 'your-plugin-menu-slug', __FILE__ );
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
	 *                                          Recommended — omit only when auto-detection is acceptable.
	 * @param array       $args                Reserved for future options (text_domain, etc.).
	 *
	 * @throws \RuntimeException If WordPress version < 6.0, or if no valid plugin main file is found.
	 */
	public function __construct( string $menu_slug, ?string $consumer_main_file = null, array $args = [] ) {
		$this->assert_wp_version();

		$this->menu_slug          = sanitize_key( $menu_slug );
		$this->consumer_main_file = $this->resolve_consumer_file( $consumer_main_file );

		$fs_instance     = FreemiusInitializer::init( $this->consumer_main_file, $this->menu_slug );
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

	/** admin_post handler: sets pending add-on slug then fires connect_again(). */
	public function handle_connect_again(): void {
		check_admin_referer( 'wpb_addons_connect', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wpb-addons-page' ) );
		}

		$slug = isset( $_GET['slug'] ) ? sanitize_key( $_GET['slug'] ) : '';
		if ( $slug && AddonsRegistry::find( $slug ) ) {
			$this->pending->set( $slug );
		}

		$return_url = add_query_arg(
			[ 'page' => 'wpb-addons', 'wpb_addons_return' => '1' ],
			admin_url( 'admin.php' )
		);

		$connect_url = $this->fs_bridge->connect_again_url( $return_url );

		wp_safe_redirect( $connect_url );
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
