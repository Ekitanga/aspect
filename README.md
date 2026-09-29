# Aspect Trading branding

This directory is the Aspect Trading Elessi child-theme source. The confirmed parent slug is `elessi-theme` (`Template: elessi-theme`).

WordPress Custom Logo and Site Icon support provide dashboard-managed settings and defaults where the active theme uses those APIs. Elessi renders its header through `elessi_logo()` and native Nasa options. The child theme supplies the bundled desktop and mobile SVGs only when Elessi's `site_logo` and `site_logo_m` options are empty; existing choices made in Elessi's theme settings take precedence. Do not edit the Elessi parent theme.

Brand assets are in `assets/images/branding/`. Global color tokens are in `assets/css/branding.css`.

## Local Development

The independent WordPress development site is served by XAMPP from `C:\xampp\htdocs\aspect-trading`. Its database uses a separate MariaDB data directory at `C:\xampp\aspect-trading-db-browser` on loopback port 3308. Local credentials remain in that site's ignored `wp-config.php`, not in this source tree.

The development catalog contains 24 products tagged **Development Demo**, six variations, category terms, four demo brands, and locally imported development photography. Do not present demo descriptions, imagery, or availability as final commercial information.

The local WordPress site currently uses a separate `aspect-trading-preview` theme to visually exercise the shared marketplace shell against live WordPress/WooCommerce data. The supplied staged Elessi 6.6.2 parent copy is missing 393 files, including required admin files, and triggers a fatal during activation. A complete matching licensed package is required before `aspect-trading` can be activated; no older Elessi files have been mixed into the parent copy.

The marketplace shell templates and helper functions are in the theme root and `inc/`. They query WordPress pages and WooCommerce categories/products dynamically. Elessi-specific logo defaults are applied only when its native desktop/mobile settings are unset.

## Production Migration

Complete [MIGRATION-CHECKLIST.md](MIGRATION-CHECKLIST.md) before deploying to Namecheap EasyWP or another production host.
