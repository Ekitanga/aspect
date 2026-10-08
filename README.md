# Aspect Trading storefront

This directory is the Aspect Trading Elessi child-theme source. The confirmed parent slug is `elessi-theme` (`Template: elessi-theme`).

WordPress Custom Logo and Site Icon support provide dashboard-managed settings and defaults where the active theme uses those APIs. Elessi renders its header through `elessi_logo()` and native Nasa options. The child theme supplies the bundled desktop and mobile SVGs only when Elessi's `site_logo` and `site_logo_m` options are empty; existing choices made in Elessi's theme settings take precedence. Do not edit the Elessi parent theme.

Brand assets are in `assets/images/branding/`. Global color tokens are in `assets/css/branding.css`. The approved storefront direction uses burnt orange (`#9A3412`), espresso (`#431708`), warm amber (`#F2A65A`), and ivory surfaces. Light, dark, mobile, mark, monochrome, and favicon SVG variants follow the same system.

## Local Development

The independent WordPress development site is served by XAMPP from `C:\xampp\htdocs\aspect-trading`. Its database uses a separate MariaDB data directory at `C:\xampp\aspect-trading-db-browser` on loopback port 3308. Local credentials remain in that site's ignored `wp-config.php`, not in this source tree.

The development catalogue contains 27 products, six published variations, eight assigned brand terms, and a distinct featured/gallery WebP pair for every product. It follows the client's confirmed top-level taxonomy: Kitchenware, Dinnerware, Home Appliances, TV & Audio, Beddings, Gadgets & Accessories, Phone & Tablets, Furnitures, and Decor & Organization. There are three published products in each category. The former **Development Demo** product tag and customer-facing demo wording have been removed. Descriptions, weights, dimensions, pricing, inventory, and generated imagery are complete for local testing but still require client or supplier approval before launch.

The local WordPress site currently uses a separate standalone `aspect-trading-preview` theme to visually exercise the shared marketplace shell against live WordPress/WooCommerce data. Its preview-only metadata and fallback template are tracked in `preview/`; the child theme retains `Template: elessi-theme`. Keep the preview theme standalone: adding a `Template` header to `preview/style.css` changes its CSS/runtime behavior. A complete matching licensed Elessi package remains a production prerequisite for activating the child-theme build.

Wishlist support is provided locally by the official **YITH WooCommerce Wishlist 4.18.1** plugin. The theme detects the plugin before rendering its header and product-card wishlist controls, so it remains usable when the optional plugin is absent. On first use, the theme enables catalogue buttons only when the plugin has no saved loop preference; later dashboard changes remain authoritative. The local plugin package is kept outside version control in `.local/plugin-packages/`.

The marketplace shell templates and helper functions are in the theme root and `inc/`. They query WordPress pages and WooCommerce categories/products dynamically. The homepage campaign slider uses `assets/js/hero-slider.js` and supports buttons, indicator keys, touch swipes, pause-on-interaction, and reduced-motion preferences. Elessi-specific logo defaults are applied only when its native desktop/mobile settings are unset.

Run `tools/sync-local-theme.ps1` to synchronize the shared source, CSS, slider script, and brand assets into both local theme copies while preserving their different `style.css` metadata. Run `tools/backup-local-database.ps1` before material data changes. The idempotent `tools/rebuild-client-catalogue.ps1` archives the former demo catalogue and rebuilds the confirmed taxonomy and placeholder products; its `-ValidateOnly` switch syntax-checks the generated migration without changing data.

Use `tests/site-inventory.ps1` for a credential-safe WordPress/catalogue inventory, `tools/audit-local-commerce.ps1` for shipping/payment settings, `tools/test-local-shipping-rates.ps1` for the eight below/above-threshold rate cases, `tools/test-local-checkout-order.ps1` for the self-cleaning Nairobi COD lifecycle, and `tests/browser-smoke.ps1` for the ten-breakpoint responsive and customer-journey matrix. The current verified local shipping and launch gaps are documented in [PROJECT-AUDIT.md](PROJECT-AUDIT.md).

## Production Migration

Complete [MIGRATION-CHECKLIST.md](MIGRATION-CHECKLIST.md) before deploying to Namecheap EasyWP or another production host.
