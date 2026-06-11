<?php

namespace AcrossAI_Addon;

/**
 * Renders the Add-ons page by loading scoped PHP view templates.
 */
class PageRenderer {

	/** @var FreemiusBridge */
	private $fs_bridge;

	/** @var ButtonState */
	private $button_state;

	/** @var PendingAddon */
	private $pending;

	/** @var string */
	private $menu_slug;

	/** @var string Absolute path to the Views directory. */
	private $views_path;

	public function __construct(
		FreemiusBridge $fs_bridge,
		ButtonState $button_state,
		PendingAddon $pending,
		string $menu_slug = ''
	) {
		$this->fs_bridge    = $fs_bridge;
		$this->button_state = $button_state;
		$this->pending      = $pending;
		$this->menu_slug    = $menu_slug;
		$this->views_path   = dirname( __DIR__ ) . '/src/Views';
	}

	/** Submenu page callback. */
	public function render(): void {
		$addons         = AddonsRegistry::all();
		$is_registered  = $this->fs_bridge->is_registered();
		$banner_visible = ! $is_registered;
		$pending_slug   = $this->pending->get();

		// Augment each addon with its button state.
		$addons_with_state = array_map(
			function ( $addon ) {
				$addon['button_state'] = $this->button_state->for_addon( $addon );
				return $addon;
			},
			$addons
		);

		$this->render_partial(
			'page',
			[
				'addons'         => $addons_with_state,
				'is_registered'  => $is_registered,
				'banner_visible' => $banner_visible,
				'pending_slug'   => $pending_slug,
				'renderer'       => $this,
				'menu_slug'      => $this->menu_slug,
			]
		);
	}

	/**
	 * Include a view partial with scoped variables.
	 *
	 * @param string  $name View file name without .php (e.g., 'partial-banner').
	 * @param array   $vars Variables to extract into the partial's scope.
	 */
	public function render_partial( string $name, array $vars = [] ): void {
		$file = $this->views_path . '/' . $name . '.php';

		if ( ! file_exists( $file ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DontExtract -- intentional for template scope
		extract( $vars, EXTR_SKIP );

		include $file;
	}
}
