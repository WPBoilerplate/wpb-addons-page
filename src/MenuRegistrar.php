<?php

namespace AcrossAI_Addon;

class MenuRegistrar {

	/** @var string */
	private $parent_slug;

	/** @var PageRenderer */
	private $renderer;

	/** @var string|null Hook suffix returned by add_submenu_page(). */
	private $hook_suffix = null;

	public function __construct( string $parent_slug, PageRenderer $renderer ) {
		$this->parent_slug = $parent_slug;
		$this->renderer    = $renderer;
	}

	/** admin_menu callback. */
	public function register(): void {
		$this->hook_suffix = add_submenu_page(
			$this->parent_slug,
			__( 'Add-ons', 'wpb-addons-page' ),
			__( 'Add-ons', 'wpb-addons-page' ),
			'install_plugins',
			'wpb-addons',
			[ $this->renderer, 'render' ]
		);
	}

	public function get_hook_suffix(): ?string {
		return $this->hook_suffix;
	}
}
