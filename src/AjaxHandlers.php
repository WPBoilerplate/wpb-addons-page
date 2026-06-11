<?php

namespace AcrossAI_Addon;

/**
 * Handles wp_ajax_* requests for free add-on install and activate.
 */
class AjaxHandlers {

	/** @var Installer */
	private $installer;

	/** @var ButtonState */
	private $button_state;

	public function __construct( Installer $installer, ButtonState $button_state ) {
		$this->installer    = $installer;
		$this->button_state = $button_state;
	}

	/** wp_ajax_wpb_addons_install_free */
	public function install_free(): void {
		check_ajax_referer( 'wpb_addons_action', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to install plugins.', 'wpb-addons-page' ),
					'code'    => 'forbidden',
				]
			);
		}

		$slug   = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';
		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';

		if ( ! in_array( $source, [ 'wordpress.org', 'github' ], true ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Invalid source.', 'wpb-addons-page' ),
					'code'    => 'invalid_source',
				]
			);
		}

		$addon = AddonsRegistry::find( $slug );
		if ( null === $addon || 'paid' === $addon['type'] ) {
			wp_send_json_error(
				[
					'message' => __( 'Add-on not found.', 'wpb-addons-page' ),
					'code'    => 'not_found',
				]
			);
		}

		$result = $this->installer->install_from_source( $addon );

		if ( ! $result['success'] ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: %s: add-on name */
						__( 'Could not install %s. Please try again.', 'wpb-addons-page' ),
						esc_html( $addon['name'] )
					),
					'detail'  => $result['message'],
					'code'    => 'install_failed',
				]
			);
		}

		// Auto-activate after install.
		if ( ! empty( $result['plugin_file'] ) ) {
			$this->installer->activate( $result['plugin_file'], $addon['name'] );
		}

		wp_send_json_success(
			[
				'message'     => $result['message'],
				'plugin_file' => $result['plugin_file'],
				'state'       => $this->button_state->for_addon( $addon ),
			]
		);
	}

	/** wp_ajax_wpb_addons_deactivate */
	public function deactivate(): void {
		check_ajax_referer( 'wpb_addons_action', 'nonce' );

		if ( ! current_user_can( 'deactivate_plugins' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to deactivate plugins.', 'wpb-addons-page' ),
					'code'    => 'forbidden',
				]
			);
		}

		$slug        = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';
		$plugin_file = isset( $_POST['plugin_file'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) ) : '';

		if ( empty( $plugin_file ) || substr_count( $plugin_file, '/' ) !== 1 ) {
			wp_send_json_error(
				[
					'message' => __( 'Invalid plugin file.', 'wpb-addons-page' ),
					'code'    => 'invalid_plugin_file',
				]
			);
		}

		$addon = AddonsRegistry::find( $slug );
		if ( null === $addon ) {
			wp_send_json_error(
				[
					'message' => __( 'Add-on not found.', 'wpb-addons-page' ),
					'code'    => 'not_found',
				]
			);
		}

		$result = $this->installer->deactivate( $plugin_file, $addon['name'] );

		wp_send_json_success(
			[
				'message' => $result['message'],
				'state'   => $this->button_state->for_addon( $addon ),
			]
		);
	}

	/** wp_ajax_wpb_addons_activate */
	public function activate(): void {
		check_ajax_referer( 'wpb_addons_action', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to activate plugins.', 'wpb-addons-page' ),
					'code'    => 'forbidden',
				]
			);
		}

		$slug        = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';
		$plugin_file = isset( $_POST['plugin_file'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) ) : '';

		// Sanitize plugin_file: must be relative path with single slash.
		if ( empty( $plugin_file ) || substr_count( $plugin_file, '/' ) !== 1 ) {
			wp_send_json_error(
				[
					'message' => __( 'Invalid plugin file.', 'wpb-addons-page' ),
					'code'    => 'invalid_plugin_file',
				]
			);
		}

		$addon = AddonsRegistry::find( $slug );
		if ( null === $addon ) {
			wp_send_json_error(
				[
					'message' => __( 'Add-on not found.', 'wpb-addons-page' ),
					'code'    => 'not_found',
				]
			);
		}

		$result = $this->installer->activate( $plugin_file, $addon['name'] );

		if ( ! $result['success'] ) {
			wp_send_json_error(
				[
					'message' => $result['message'],
					'code'    => 'activate_failed',
				]
			);
		}

		wp_send_json_success(
			[
				'message' => $result['message'],
				'state'   => $this->button_state->for_addon( $addon ),
			]
		);
	}
}
