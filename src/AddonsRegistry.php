<?php

namespace AcrossAI_Addon;

/**
 * The hardcoded list of add-ons shown on the Add-ons page.
 * Every plugin that includes this package shows this exact same list.
 *
 * Add-on schema:
 *   slug         — WordPress plugin folder/file slug
 *   name         — display name
 *   description  — short description (2 lines max)
 *   icon         — URL to icon image
 *   more_url     — link to the add-on's marketing page
 *   type         — 'free' | 'paid'
 *   source       — 'wordpress.org' | 'github' | 'freemius'
 *
 * Source-specific keys:
 *   download_url    — ZIP URL (github source only)
 *   fs_product_id   — Freemius standalone product ID (freemius source only)
 *   fs_plan_id      — Freemius plan ID (freemius source only)
 *   fs_public_key   — Freemius public key (freemius source only)
 *   price_label     — e.g. '$49/year' (freemius source only)
 */
class AddonsRegistry {

	/** @var array[]|null */
	private static $all = null;

	/** @return array[] */
	public static function all(): array {
		if ( null !== self::$all ) {
			return self::$all;
		}

		self::$all = self::definitions();
		return self::$all;
	}

	/** @return array|null */
	public static function find( string $slug ): ?array {
		foreach ( self::all() as $addon ) {
			if ( $addon['slug'] === $slug ) {
				return $addon;
			}
		}
		return null;
	}

	/** @return array[] */
	public static function by_type( string $type ): array {
		return array_values(
			array_filter(
				self::all(),
				function ( $a ) use ( $type ) {
					return $a['type'] === $type;
				}
			)
		);
	}

	/** @return array[] */
	public static function by_source( string $source ): array {
		return array_values(
			array_filter(
				self::all(),
				function ( $a ) use ( $source ) {
					return $a['source'] === $source;
				}
			)
		);
	}

	// -------------------------------------------------------------------------
	// Hardcoded add-ons list
	// -------------------------------------------------------------------------

	/** @return array[] */
	private static function definitions(): array {
		return array(

			// ---- AcrossAI Model Manager -----------------------------------------
			array(
				'slug'        => 'acrossai-model-manager',
				'name'        => 'AcrossAI Model Manager',
				'description' => 'Control which AI model is used per capability, set request time limits, and review a full audit log of every AI generation call on your site.',
				'icon'        => 'https://ps.w.org/acrossai-model-manager/assets/icon-128x128.png',
				'more_url'    => 'https://wordpress.org/plugins/acrossai-model-manager/',
				'type'        => 'free',
				'source'      => 'wordpress.org',
			),

			// ---- Turn Off AI Features -------------------------------------------
			array(
				'slug'        => 'turn-off-ai-features',
				'name'        => 'Turn Off AI Features',
				'description' => 'Disable AI functionality in WordPress without touching code. Hooks into wp_supports_ai to return false when the option is enabled.',
				'icon'        => 'https://ps.w.org/turn-off-ai-features/assets/icon-128x128.png',
				'more_url'    => 'https://wordpress.org/plugins/turn-off-ai-features/',
				'type'        => 'free',
				'source'      => 'wordpress.org',
			),

			// ---- AcrossAI MCP Manager -------------------------------------------
			array(
				'slug'        => 'acrossai-mcp-manager',
				'name'        => 'AcrossAI MCP Manager',
				'description' => 'Seamless integration with Model Context Protocol (MCP) servers — lets AI assistants and code editors safely access your WordPress site via secure application passwords.',
				'icon'        => 'https://ps.w.org/acrossai-mcp-manager/assets/icon-128x128.png',
				'more_url'    => 'https://wordpress.org/plugins/acrossai-mcp-manager/',
				'type'        => 'free',
				'source'      => 'wordpress.org',
			),

			// ---- AcrossAI Core Abilities ----------------------------------------
			array(
				'slug'         => 'acrossai-core-abilities',
				'name'         => 'AcrossAI Core Abilities',
				'description'  => 'Register, manage, and expose site capabilities to AI agents and clients via the standard WordPress Abilities API.',
				'icon'         => '',
				'more_url'     => 'https://github.com/acrossai-co/acrossai-core-abilities/releases',
				'type'         => 'free',
				'source'       => 'github',
				'download_url' => 'https://github.com/acrossai-co/acrossai-core-abilities/releases/latest/download/acrossai-core-abilities.zip',
			),

		);
	}
}
