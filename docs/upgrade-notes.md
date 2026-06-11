# Upgrade Notes

## Upgrading jetpack-autoloader from v3 to v5

This package requires `automattic/jetpack-autoloader: ^5.0`.
Plugins built on the WordPress Plugin Boilerplate typically ship with `^3.0`.

### Steps

1. Open your plugin's `composer.json` and change the constraint:

```json
"automattic/jetpack-autoloader": "^5.0"
```

2. Run the update:

```bash
composer update automattic/jetpack-autoloader
```

3. **No code changes needed.** The v5 autoloader still generates
   `vendor/autoload_packages.php` which your plugin already loads via:

```php
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload_packages.php';
```

4. Commit the updated `composer.json` and `composer.lock`.

### Why the bump?

`acrossai-co/addons-page` uses features in jetpack-autoloader v5 that
are not available in v3. Because Composer requires all packages in the
dependency tree to satisfy a single resolved version, your root
`composer.json` must allow v5.

### Compatibility

jetpack-autoloader v5 is backwards-compatible with v3 for all standard
usage patterns. If you experience any issues after upgrading, check:
- PHP version: v5 requires PHP ≥ 7.4 (same as this package)
- WordPress version: v5 works on WordPress 5.6+
