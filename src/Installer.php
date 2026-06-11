<?php

namespace AcrossAI_Addon;

/**
 * Handles silent plugin installation and activation via WordPress's own upgrader.
 */
class Installer {

	/**
	 * Install a free add-on from either WordPress.org or a GitHub ZIP URL.
	 *
	 * @return array{success:bool, message:string, plugin_file:string}
	 */
	public function install_from_source( array $addon ): array {
		$this->load_upgrader_files();

		$download_url = $this->resolve_download_url( $addon );
		if ( is_wp_error( $download_url ) ) {
			return [
				'success'     => false,
				'message'     => $download_url->get_error_message(),
				'plugin_file' => '',
			];
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $download_url );

		if ( is_wp_error( $result ) ) {
			return [
				'success'     => false,
				'message'     => $result->get_error_message(),
				'plugin_file' => '',
			];
		}

		if ( $skin->get_errors()->has_errors() ) {
			return [
				'success'     => false,
				'message'     => implode( ' ', $skin->get_errors()->get_error_messages() ),
				'plugin_file' => '',
			];
		}

		// Re-scan get_plugins() to find the actual installed basename.
		$plugin_file = $this->find_installed_file( $addon['slug'] );

		return [
			'success'     => true,
			/* translators: %s: add-on name */
			'message'     => sprintf( __( '%s installed and activated.', 'wpb-addons-page' ), esc_html( $addon['name'] ) ),
			'plugin_file' => $plugin_file ?? '',
		];
	}

	/**
	 * Activate an already-installed plugin.
	 *
	 * @return array{success:bool, message:string}
	 */
	public function activate( string $plugin_file, string $addon_name ): array {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$result = activate_plugin( $plugin_file );

		if ( is_wp_error( $result ) ) {
			return [
				'success' => false,
				'message' => $result->get_error_message(),
			];
		}

		return [
			'success' => true,
			/* translators: %s: add-on name */
			'message' => sprintf( __( '%s activated.', 'wpb-addons-page' ), esc_html( $addon_name ) ),
		];
	}

	/**
	 * Deactivate an active plugin.
	 *
	 * @return array{success:bool, message:string}
	 */
	public function deactivate( string $plugin_file, string $addon_name ): array {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		deactivate_plugins( $plugin_file );

		return [
			'success' => true,
			/* translators: %s: add-on name */
			'message' => sprintf( __( '%s deactivated.', 'wpb-addons-page' ), esc_html( $addon_name ) ),
		];
	}

	// -------------------------------------------------------------------------

	private function load_upgrader_files(): void {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( ! class_exists( 'WP_Filesystem_Base' ) ) {
			WP_Filesystem();
		}
	}

	/**
	 * Resolves the download URL for a free add-on.
	 *
	 * @return string|\WP_Error
	 */
	private function resolve_download_url( array $addon ) {
		if ( 'wordpress.org' === $addon['source'] ) {
			$info = plugins_api(
				'plugin_information',
				[
					'slug'   => $addon['slug'],
					'fields' => [
						'sections' => false,
						'reviews'  => false,
					],
				]
			);

			if ( is_wp_error( $info ) ) {
				return $info;
			}

			if ( empty( $info->download_link ) ) {
				return new \WP_Error(
					'no_download_link',
					/* translators: %s: plugin slug */
					sprintf( __( 'Could not retrieve download URL for %s from WordPress.org.', 'wpb-addons-page' ), esc_html( $addon['slug'] ) )
				);
			}

			return $info->download_link;
		}

		if ( 'github' === $addon['source'] ) {
			if ( empty( $addon['download_url'] ) ) {
				return new \WP_Error(
					'missing_download_url',
					/* translators: %s: add-on name */
					sprintf( __( 'No download_url configured for %s.', 'wpb-addons-page' ), esc_html( $addon['name'] ) )
				);
			}
			return $addon['download_url'];
		}

		return new \WP_Error( 'invalid_source', __( 'Invalid add-on source for free install.', 'wpb-addons-page' ) );
	}

	/**
	 * Scans get_plugins() for a plugin whose folder matches the slug.
	 * GitHub ZIPs may extract to non-canonical folder names, so we scan rather than guess.
	 */
	private function find_installed_file( string $slug ): ?string {
		wp_clean_plugins_cache();
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		foreach ( array_keys( $plugins ) as $plugin_file ) {
			$folder = explode( '/', $plugin_file )[0];
			if ( $folder === $slug ) {
				return $plugin_file;
			}
		}
		// Also try partial match for GitHub slugs that differ from the folder name.
		foreach ( array_keys( $plugins ) as $plugin_file ) {
			$folder = explode( '/', $plugin_file )[0];
			if ( false !== strpos( $folder, $slug ) || false !== strpos( $slug, $folder ) ) {
				return $plugin_file;
			}
		}
		return null;
	}
}
