# Integration Prompt

Copy and paste the block below into Claude (or any AI assistant) along with access to your plugin's files. It gives the AI everything it needs to wire `acrossai-co/addons-page` into your plugin in one shot.

---

```
I want to integrate the Composer package `acrossai-co/addons-page` into my WordPress plugin.
The package lives at https://github.com/acrossai-co/addons-page and adds a fully-working
Add-ons page (free installs + Freemius paid checkout + opt-in flow) as a submenu under my
plugin's admin menu.

Here is what the integration requires. Please make all necessary file edits.

─────────────────────────────────────────────────────────────
STEP 1 — composer.json  (edit my plugin's composer.json)
─────────────────────────────────────────────────────────────

1a. Ensure `automattic/jetpack-autoloader` is `^5.0` (not ^3.0).
    If it is currently `^3.0`, bump it to `^5.0`.

1b. Add `acrossai-co/addons-page` to the `require` block:

    "acrossai-co/addons-page": "^1.0"

    (If I am working from a local path clone instead of Packagist, add a
     path repository entry pointing at the local clone and use `"@dev"` as
     the version constraint instead.)

1c. Make sure `automattic/jetpack-autoloader` is listed under
    `config.allow-plugins`:

    "config": {
        "allow-plugins": {
            "automattic/jetpack-autoloader": true
        }
    }

After editing, tell me to run:

    composer update automattic/jetpack-autoloader acrossai-co/addons-page

─────────────────────────────────────────────────────────────
STEP 2 — Autoloader bootstrap  (main plugin file)
─────────────────────────────────────────────────────────────

Locate the line in my plugin's main PHP file that loads Composer.
It should already read:

    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload_packages.php';

If it loads `vendor/autoload.php` instead, change it to
`vendor/autoload_packages.php` — jetpack-autoloader generates this file
and the package requires it.

If the line is missing entirely, add it early in the main plugin file,
before any class instantiation.

─────────────────────────────────────────────────────────────
STEP 3 — Instantiate AddonsPage  (admin hooks / init)
─────────────────────────────────────────────────────────────

Find the place in my plugin where admin hooks are registered
(commonly a `define_admin_hooks()` method, a `plugins_loaded` callback,
or an `admin_init` action).

Add exactly this block there:

    new \AcrossAI_Addon\AddonsPage(
        'YOUR_PLUGIN_MENU_SLUG', // replace with the slug passed to add_menu_page()
        __FILE__,                // the main plugin file — do NOT change this
        [
            'fs_product_id' => 'YOUR_FS_PRODUCT_ID', // numeric ID from Freemius dashboard
            'fs_public_key' => 'YOUR_FS_PUBLIC_KEY', // pk_... from Freemius dashboard
            'fs_slug'       => 'your-plugin-slug',   // optional — defaults to menu slug
        ]
    );

Replace:
- `YOUR_PLUGIN_MENU_SLUG` — the actual first argument passed to `add_menu_page()`
  (search the codebase for `add_menu_page` to find it)
- `YOUR_FS_PRODUCT_ID` — numeric product ID from Freemius dashboard
  (Dashboard → your product → Settings → General)
- `YOUR_FS_PUBLIC_KEY` — public key (pk_...) from the same settings page

Each plugin must have its own Freemius product so activations and analytics
are tracked separately. Go to dashboard.freemius.com → Add New Product:
  - What are you selling?  → WordPress Products
  - Product type           → WordPress Plugin
  - Integration purpose    → Get Analytics through Freemius
  - Monetization           → Paid add-ons and/or add-on bundles
  - Free plan              → ON

The constructor registers the "Add-ons" submenu, enqueues assets,
and wires all AJAX and Freemius hooks automatically. No other code is needed.

─────────────────────────────────────────────────────────────
STEP 4 — readme.txt  (for wordpress.org submissions, optional)
─────────────────────────────────────────────────────────────

If my plugin has a `readme.txt` for wordpress.org, append the following
sections (copy them verbatim from the package's
`vendor/acrossai-co/addons-page/docs/readme-template.txt`):

- == Installation ==
- == External Services ==
- == Privacy Policy ==

These sections are required by wordpress.org's plugin guidelines whenever
the plugin connects to an external service.

─────────────────────────────────────────────────────────────
CONTEXT: what the package does (do not re-implement any of this)
─────────────────────────────────────────────────────────────

- Registers an "Add-ons" submenu under my plugin's top-level menu.
- Renders a responsive card grid of add-ons (free and paid).
- Installs free add-ons silently via the WordPress Plugin_Upgrader API
  (WordPress.org plugins) or a direct GitHub ZIP URL — no page reload.
- Opens the Freemius JS checkout popup for paid add-ons when the user
  is opted in; otherwise redirects to the Freemius opt-in flow first.
- Handles the opt-in round-trip automatically (pending-slug transient,
  return flag, welcome notice, card highlight).
- Shares opt-in state with any other plugin on the site that also uses
  this package (single Freemius product = one shared account).
- Enqueues its own JS and CSS only on the Add-ons page — no global impact.

─────────────────────────────────────────────────────────────
FILES TO LOOK AT IN MY PLUGIN
─────────────────────────────────────────────────────────────

Please read the following files before making any edits so you understand
the existing structure:

1. The main plugin PHP file (the one with the `Plugin Name:` header)
2. `composer.json`
3. The file that registers admin hooks / menus
   (search for `add_menu_page` to locate it)

Make all edits, then summarise what you changed and confirm the exact
`composer update` command I need to run.
```
