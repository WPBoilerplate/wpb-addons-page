<?php

namespace AcrossAI_Addon;

/**
 * Auto-detects the consumer plugin's main file by walking up from the package's
 * own directory until it exits vendor/, then scanning for a Plugin Name: header.
 *
 * This is a fallback — callers should pass __FILE__ explicitly to AddonsPage::__construct().
 */
class ConsumerPluginLocator {

	/** @var string|null Cached result per request. */
	private static $detected = null;

	/**
	 * @throws \RuntimeException If no plugin main file can be found.
	 */
	public static function detect(): string {
		if ( null !== self::$detected ) {
			return self::$detected;
		}

		$plugin_root = self::find_plugin_root();
		$main_file   = self::find_main_file( $plugin_root );

		self::$detected = $main_file;
		return $main_file;
	}

	/**
	 * Walk up from the package directory until we exit vendor/.
	 */
	private static function find_plugin_root(): string {
		$dir = dirname( __DIR__ ); // start at the package root (addons-page/)

		// Walk up until we're no longer inside a directory named 'vendor'.
		$max_depth = 10;
		while ( $max_depth-- > 0 ) {
			$parent = dirname( $dir );
			if ( basename( $dir ) === 'vendor' ) {
				// $parent is the plugin root.
				return $parent;
			}
			if ( $parent === $dir ) {
				break; // reached filesystem root
			}
			$dir = $parent;
		}

		throw new \RuntimeException(
			"AddonsPage: Could not locate the vendor/ directory above the package path. \n" .
			"Pass __FILE__ of your plugin's main file as the second constructor argument:\n" .
			'  new \\AcrossAI_Addon\\AddonsPage( $menu_slug, __FILE__ );'
		);
	}

	/**
	 * Scan the plugin root for a .php file with a Plugin Name: header.
	 */
	private static function find_main_file( string $plugin_root ): string {
		$files = glob( trailingslashit( $plugin_root ) . '*.php' );
		if ( empty( $files ) ) {
			self::throw_not_found( $plugin_root );
		}

		foreach ( $files as $file ) {
			$contents = self::read_header( $file );
			if ( false !== strpos( $contents, 'Plugin Name:' ) ) {
				return $file;
			}
		}

		self::throw_not_found( $plugin_root );
	}

	/** Read only the first 8 KB of a file (enough for a plugin header). */
	private static function read_header( string $file ): string {
		$handle = fopen( $file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $handle ) {
			return '';
		}
		$content = fread( $handle, 8192 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return $content ?: '';
	}

	/** @throws \RuntimeException always */
	private static function throw_not_found( string $plugin_root ): never {
		throw new \RuntimeException(
			"AddonsPage: No WordPress plugin main file found in: {$plugin_root}\n" .
			"This package must be instantiated from within a WordPress plugin context.\n" .
			"Pass __FILE__ of your plugin's main file as the second constructor argument:\n" .
			'  new \\AcrossAI_Addon\\AddonsPage( $menu_slug, __FILE__ );'
		);
	}
}
