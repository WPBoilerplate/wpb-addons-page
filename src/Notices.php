<?php

namespace AcrossAI_Addon;

/**
 * Queues and renders WP admin notices for the Add-ons page.
 * Notices are stored per-user in a transient so they survive redirects.
 */
class Notices {

	const TRANSIENT_KEY = 'wpb_addons_notices_';

	/** @param string $type 'success' | 'error' | 'warning' */
	public function queue( string $type, string $message ): void {
		$key       = self::TRANSIENT_KEY . get_current_user_id();
		$pending   = get_transient( $key ) ?: [];
		$pending[] = [
			'type'    => $type,
			'message' => $message,
		];
		set_transient( $key, $pending, MINUTE_IN_SECONDS * 5 );
	}

	/** admin_notices callback. */
	public function render(): void {
		$key     = self::TRANSIENT_KEY . get_current_user_id();
		$pending = get_transient( $key );

		if ( empty( $pending ) ) {
			return;
		}

		delete_transient( $key );

		foreach ( $pending as $notice ) {
			$class = 'notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible';
			printf(
				'<div class="%s" role="status" aria-live="polite"><p>%s</p></div>',
				esc_attr( $class ),
				esc_html( $notice['message'] )
			);
		}
	}
}
