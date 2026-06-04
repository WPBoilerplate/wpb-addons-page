<?php

namespace WPBoilerplate\AddonsPage;

/**
 * Loads the Freemius SDK and creates the single shared FS instance.
 *
 * All consumer plugins that include this package share one Freemius product
 * ("WPB Add-ons Page"). This enables automatic shared opt-in across all
 * consumers on the same site — the first consumer to load creates the instance,
 * subsequent consumers receive the memoized instance back.
 */
class FreemiusInitializer {

	const FS_PRODUCT_ID  = '31225';
	const FS_SLUG        = 'wpb-addons-page';
	const FS_PUBLIC_KEY  = 'pk_2a57beb16d277532f564f3daaf7c6';

	/** @var object|null Memoized Freemius instance. */
	private static $instance = null;

	/**
	 * Load the SDK (if not already loaded) and return the shared FS instance.
	 *
	 * @param string $consumer_main_file Absolute path to the consumer plugin's main file.
	 * @param string $menu_slug          Consumer's parent admin menu slug.
	 *
	 * @return object Freemius instance.
	 */
	public static function init( string $consumer_main_file, string $menu_slug ): object {
		if ( null !== self::$instance ) {
			return self::$instance;
		}

		self::load_sdk();

		if ( ! function_exists( 'fs_dynamic_init' ) ) {
			throw new \RuntimeException(
				'AddonsPage: Freemius SDK loaded but fs_dynamic_init() is unavailable. ' .
				'Ensure vendor/freemius/wordpress-sdk/start.php is accessible.'
			);
		}

		self::$instance = fs_dynamic_init( array(
			'id'                => self::FS_PRODUCT_ID,
			'slug'              => self::FS_SLUG,
			'type'              => 'plugin',
			'public_key'        => self::FS_PUBLIC_KEY,
			'is_premium'        => false,
			'has_addons'        => false,
			'has_paid_plans'    => false,
			'anonymous_mode'    => true,
			'opt_in_moderation' => array(
				'new' => 0,
			),
			'menu'              => array(
				'slug'    => $menu_slug,
				'account' => false,
				'contact' => false,
				'support' => false,
				'upgrade' => false,
				'pricing' => false,
				'addons'  => false,
			),
			'navigation'        => 'menu',
			'file'              => $consumer_main_file,
		) );

		return self::$instance;
	}

	/**
	 * Require the Freemius SDK start.php.
	 * Guards against double-loading — FS itself also guards internally.
	 */
	private static function load_sdk(): void {
		if ( function_exists( 'fs_dynamic_init' ) ) {
			return;
		}

		// When installed via Composer the SDK lives at:
		// {consumer}/vendor/freemius/wordpress-sdk/start.php
		// This package lives at: {consumer}/vendor/wpboilerplate/addons-page/src/
		// Walk up: src -> addons-page -> wpboilerplate -> vendor, then into freemius/wordpress-sdk.
		$sdk_path = dirname( __DIR__, 3 ) . '/freemius/wordpress-sdk/start.php';

		if ( ! file_exists( $sdk_path ) ) {
			throw new \RuntimeException(
				"AddonsPage: Freemius SDK not found at expected path: {$sdk_path}\n" .
				'Run `composer install` in your plugin directory to install dependencies.'
			);
		}

		require_once $sdk_path;
	}
}
