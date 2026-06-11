<?php

namespace AcrossAI_Addon;

/**
 * Loads the Freemius SDK and creates a per-plugin FS instance.
 *
 * Each consumer plugin passes its own Freemius product credentials so that
 * activations, opt-ins, and analytics are tracked separately per plugin in
 * the Freemius dashboard.
 */
class FreemiusInitializer {

	/** @var object[] Memoized FS instances keyed by product ID. */
	private static $instances = [];

	/**
	 * Load the SDK (if not already loaded) and return the FS instance for the given product.
	 *
	 * @param string $consumer_main_file Absolute path to the consumer plugin's main file.
	 * @param string $menu_slug          Consumer's parent admin menu slug.
	 * @param string $product_id         Freemius product ID (numeric string).
	 * @param string $public_key         Freemius product public key (pk_...).
	 * @param string $slug               Freemius product slug.
	 *
	 * @return object Freemius instance.
	 */
	public static function init(
		string $consumer_main_file,
		string $menu_slug,
		string $product_id,
		string $public_key,
		string $slug
	): object {
		if ( isset( self::$instances[ $product_id ] ) ) {
			return self::$instances[ $product_id ];
		}

		self::load_sdk();

		if ( ! function_exists( 'fs_dynamic_init' ) ) {
			throw new \RuntimeException(
				'AddonsPage: Freemius SDK loaded but fs_dynamic_init() is unavailable. ' .
				'Ensure vendor/freemius/wordpress-sdk/start.php is accessible.'
			);
		}

		self::$instances[ $product_id ] = fs_dynamic_init(
			array(
				'id'             => $product_id,
				'slug'           => $slug,
				'type'           => 'plugin',
				'public_key'     => $public_key,
				'is_premium'     => false,
				'has_addons'     => false,
				'has_paid_plans' => false,
				'menu'           => array(
					'slug'    => $menu_slug,
					'account' => false,
					'contact' => false,
					'support' => false,
					'upgrade' => false,
					'pricing' => false,
					'addons'  => false,
				),
				'navigation'     => 'menu',
				'file'           => $consumer_main_file,
			)
		);

		return self::$instances[ $product_id ];
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
		// This package lives at: {consumer}/vendor/acrossai-co/addons-page/src/
		// Walk up: src -> addons-page -> acrossai-co -> vendor, then into freemius/wordpress-sdk.
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
