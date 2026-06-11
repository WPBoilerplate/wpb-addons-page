<?php

namespace AcrossAI_Addon;

/**
 * Manages the 5-minute transient that remembers which paid add-on the user
 * was trying to buy before being sent through the Freemius opt-in flow.
 */
class PendingAddon {

	const TRANSIENT_PREFIX = 'wpb_addons_pending_';

	private function key(): string {
		return self::TRANSIENT_PREFIX . get_current_user_id();
	}

	public function set( string $slug ): void {
		set_transient( $this->key(), $slug, 5 * MINUTE_IN_SECONDS );
	}

	public function get(): ?string {
		$slug = get_transient( $this->key() );
		return $slug ?: null;
	}

	public function clear(): void {
		delete_transient( $this->key() );
	}

	/**
	 * admin_init callback.
	 * Detects the ?wpb_addons_return=1 query arg set after Freemius redirects back,
	 * queues the appropriate admin notice, and preserves the pending slug for the
	 * page renderer to highlight the relevant card.
	 */
	public function maybe_handle_return(): void {
		if ( empty( $_GET['wpb_addons_return'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		if ( ! is_admin() || ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$pending = $this->get();
		$notices = new Notices();

		if ( $pending ) {
			$notices->queue(
				'success',
				__( 'Account connected! Now click "Buy" on the add-on you wanted.', 'wpb-addons-page' )
			);
		} else {
			$notices->queue(
				'success',
				__( 'Account connected! Your purchased add-ons now show an "Install" button.', 'wpb-addons-page' )
			);
		}

		// The pending slug is kept in the transient so the renderer can highlight the card.
		// It will be cleared by AjaxHandlers after the user acts on it.
	}
}
