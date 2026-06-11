/**
 * Add-ons page JS entry point.
 * Imports SCSS (extracted to addons-page.css by webpack).
 */
import '../scss/addons-page.scss';
import { initFilters } from './modules/filters';
import { initInstall } from './modules/install';
import { initCheckout } from './modules/checkout';

document.addEventListener( 'DOMContentLoaded', () => {
	const page = document.querySelector( '.wpb-addons-page' );
	if ( ! page ) return;

	// Filters are purely DOM-driven and work regardless of config.
	initFilters( page );

	// Find the localised data object for this page instance.
	const instanceKey = page.dataset.wpbAddonsInstance || '';
	const globalName = 'wpbAddonsPage_' + instanceKey.replace( /[^a-zA-Z0-9_]/g, '_' );
	const config = window[ globalName ];
	if ( ! config ) return;

	initInstall( page, config );
	initCheckout( page, config );

	// Scroll + focus pending card on opt-in return.
	if ( config.pendingSlug ) {
		const pendingCard = page.querySelector( `[data-slug="${ config.pendingSlug }"]` );
		if ( pendingCard ) {
			pendingCard.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			const btn = pendingCard.querySelector( '.wpb-addons-page__btn' );
			if ( btn ) btn.focus();
		}
	}
} );
