<?php

namespace AcrossAI_Addon\Tests\Unit;

use AcrossAI_Addon\AddonsRegistry;
use PHPUnit\Framework\TestCase;

class AddonsRegistryTest extends TestCase {

	public function test_all_returns_non_empty_array(): void {
		$addons = AddonsRegistry::all();
		$this->assertIsArray( $addons );
		$this->assertNotEmpty( $addons );
	}

	public function test_all_entries_have_required_keys(): void {
		$required = [ 'slug', 'name', 'description', 'type', 'source' ];
		foreach ( AddonsRegistry::all() as $addon ) {
			foreach ( $required as $key ) {
				$this->assertArrayHasKey( $key, $addon, "Missing key '{$key}' in addon '{$addon['slug']}'" );
			}
		}
	}

	public function test_all_entries_have_valid_type(): void {
		foreach ( AddonsRegistry::all() as $addon ) {
			$this->assertContains( $addon['type'], [ 'free', 'paid' ], "Invalid type in '{$addon['slug']}'" );
		}
	}

	public function test_all_entries_have_valid_source(): void {
		foreach ( AddonsRegistry::all() as $addon ) {
			$this->assertContains(
				$addon['source'],
				[ 'wordpress.org', 'github', 'freemius' ],
				"Invalid source in '{$addon['slug']}'"
			);
		}
	}

	public function test_find_returns_addon_by_slug(): void {
		$first = AddonsRegistry::all()[0];
		$found = AddonsRegistry::find( $first['slug'] );
		$this->assertNotNull( $found );
		$this->assertSame( $first['slug'], $found['slug'] );
	}

	public function test_find_returns_null_for_unknown_slug(): void {
		$this->assertNull( AddonsRegistry::find( '__nonexistent__' ) );
	}

	public function test_by_type_returns_only_matching_type(): void {
		foreach ( [ 'free', 'paid' ] as $type ) {
			foreach ( AddonsRegistry::by_type( $type ) as $addon ) {
				$this->assertSame( $type, $addon['type'] );
			}
		}
	}

	public function test_by_source_returns_only_matching_source(): void {
		foreach ( [ 'wordpress.org', 'github', 'freemius' ] as $source ) {
			foreach ( AddonsRegistry::by_source( $source ) as $addon ) {
				$this->assertSame( $source, $addon['source'] );
			}
		}
	}

	public function test_github_addons_have_download_url(): void {
		foreach ( AddonsRegistry::by_source( 'github' ) as $addon ) {
			$this->assertArrayHasKey( 'download_url', $addon, "GitHub addon '{$addon['slug']}' missing download_url" );
			$this->assertNotEmpty( $addon['download_url'] );
		}
	}
}
