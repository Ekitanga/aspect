# Aspect Trading Project Audit and Launch Gap Register

Audit updated: 8 October 2026 (Africa/Nairobi)

## Verified local baseline

This audit covers the shared theme source, active local WordPress/WooCommerce runtime, catalogue, shipping, checkout, customer-facing routes, and responsive behavior.

- The active local storefront is `aspect-trading-preview`; the production child-theme source remains `aspect-trading`, with `elessi-theme` as its declared parent.
- The client-confirmed catalogue contains 27 published products across nine top-level categories, with three products in every category.
- All 27 products have a unique SKU, realistic local placeholder pricing, a featured WebP image, a distinct gallery WebP image, substantial description copy, weight, dimensions, a brand, and stock data.
- The two variable bedding products have six healthy published variations. The six retired clothing variations were removed after backup.
- All nine category terms have descriptions and thumbnails. All eight brands have descriptions and assigned products.
- The orange design system, logo family, favicon, homepage banners, navigation, filters, product cards, product detail, wishlist, cart, checkout, account, and responsive states are implemented in the shared source.
- The final browser report contains 29 route/viewport checks, zero document-overflow failures, zero pages with captured browser errors, and successful simple product, variable product, cart removal, and checkout rendering workflows.
- A mobile cart-table defect was corrected at its grid source; prices, sale badges, quantity controls, totals, and checkout action now fit without clipping.
- Kenyan phone and postal fields are required at checkout, while guest checkout and optional account creation both remain available.

## Shipping and checkout verification

The former overlapping, zero-cost, malformed shipping configuration has been replaced.

| Zone | Paid delivery | Free-delivery threshold | Location rule |
|---|---:|---:|---|
| Nairobi CBD | KSh200 | KSh10,000 | Nairobi County plus postal codes 00100 or 00200 |
| Nairobi Extended | KSh300 | KSh10,000 | Remaining Nairobi County addresses |
| Major Towns | KSh500 | KSh15,000 | Mombasa, Kisumu, Nakuru, Uasin Gishu, and Kiambu counties |
| Rest of Kenya | KSh700 | KSh20,000 | Remaining Kenyan addresses |

- Selling and shipping are restricted to Kenya in the local configuration.
- Click & Collect at Superior Center is free and available in all four Kenya zones.
- When free delivery is available, the redundant paid rate is removed while Click & Collect remains visible.
- Eight functional rate cases passed: every zone below and above its threshold returned the expected methods and exact cost.
- Cash on Delivery is restricted to Nairobi CBD and Nairobi Extended delivery methods, including qualifying free-delivery orders. It is unavailable for Major Towns and Rest of Kenya.
- One controlled Nairobi CBD COD order completed with KSh200 shipping, KSh7,699 total, Processing status, and a valid confirmation redirect. Temporary order 169 was permanently removed after verification.
- The Delivery Information page matches the configured rates and its Track Order link now respects the local WordPress subdirectory.

Operational confirmation is still required for the assumed CBD postcodes and for treating the five named major-town counties as complete delivery regions.

## P0 — launch blockers

### 1. Production online payments are not configured

M-Pesa/Daraja and Pesapal/card processing are not installed or configured. The local COD method is correctly restricted to Nairobi, but customers elsewhere will need an online gateway before launch. Production credentials, callback/webhook URLs, failed-payment behavior, refunds, reconciliation, receipts, and end-to-end sandbox/live transactions remain mandatory.

### 2. Commercial product data requires client approval

The local catalogue is coherent and complete enough for design, shipping, and checkout testing, but its products are still representative dummy products. The client or suppliers must approve product names, prices, sale prices, stock quantities, specifications, weights, dimensions, brands, photographs, warranty terms, package contents, returns eligibility, and procurement lead times. GTIN/MPN or other supplier identifiers are not yet recorded.

The generated catalogue imagery is polished and internally consistent, but must not be treated as supplier-authenticated photography without client approval.

### 3. Production environment and security hardening are incomplete

The current runtime is local HTTP, reports its environment type as `production`, has debug logging enabled, does not force SSL for administration, and is hidden from search engines. Before deployment: use HTTPS, set the correct environment type, disable debug output, disable production file editing, rotate all exposed development credentials and WordPress salts, verify least-privilege administrators, configure backups with a tested restore, and complete malware/WAF/rate-limit review.

### 4. Transactional email is unverified

There is no verified authenticated production mail delivery, SPF/DKIM/DMARC alignment, bounce handling, or successful real-inbox coverage for order, account, password-reset, cancellation, refund, and return messages. Configure SMTP or a transactional provider and test all WooCommerce notification paths.

### 5. Tax and legal decisions are not signed off

WooCommerce tax calculation is disabled. Confirm whether displayed prices are VAT-inclusive and whether the business must calculate or display tax. Privacy, terms, returns, delivery, seller onboarding, cookie/analytics language, and the current returns period require client/legal approval.

## P1 — high-priority quality and operational gaps

### Catalogue and merchandising

- There are no genuine approved product reviews; the rating filter correctly returns no products. Do not fabricate ratings.
- The client-confirmed label “Furnitures” is preserved, but “Furniture” is standard English. Change it only with client approval.
- Desktop navigation intentionally exposes seven categories directly; the remaining two are available through All Categories and the mobile menu. Confirm that prioritization.
- Inventory ownership, low-stock thresholds, backorders, supplier feeds, and stock reconciliation require an operational process.

### Store and customer journey

- Guest checkout, optional account creation, required courier fields, rate calculation, COD restriction, and one local COD order lifecycle are verified.
- M-Pesa, card payments, real customer emails, refunds, cancellations, and production order-status operations remain untested until their services are connected.
- The published Home page is not selected as a static front page; `show_on_front=posts` relies on `front-page.php`. It works, but a dedicated static-page setup is easier for editors and SEO governance.
- A retired draft “Refund and Returns Policy” page remains alongside the published Returns Policy and should be archived or deleted after confirmation.

### SEO, analytics, performance, and accessibility

- Final titles/descriptions, social cards, canonical rules, product structured data, XML sitemap/robots behavior, redirects, Search Console, and merchant-feed setup require a production SEO pass.
- Analytics/e-commerce events, consent management, and privacy-safe attribution are not configured or verified.
- CDN/page caching, AVIF strategy, production database/cron tuning, and Core Web Vitals must be tested on production-like hosting.
- Automated responsive and workflow tests pass. Manual screen-reader, keyboard-only, zoom/reflow, color-contrast, form-error, and assistive-technology checkout testing remains required.

### Deployment architecture

- Production activation of the `aspect-trading` child theme requires the complete matching licensed Elessi parent and a staging migration rehearsal.
- Verify WordPress, theme, and plugin compatibility; licenses; update policy; backups; and rollback on production-like staging.
- Remove development-only migration and test utilities from the final deployment package.

## Evidence and recovery

- Final responsive/browser report: `.artifacts/browser-smoke/report.json`
- Catalogue/runtime inventory: `tests/site-inventory.ps1`
- Commerce audit: `tools/audit-local-commerce.ps1`
- Shipping calculation tests: `tools/test-local-shipping-rates.ps1`
- Checkout order lifecycle test: `tools/test-local-checkout-order.ps1`
- Catalogue media source: `assets/images/catalogue/`
- Pre-shipping backup: `.artifacts/backups/aspect-trading-before-shipping-rebuild-20261007-161957.sql`
- Pre-checkout backup: `.artifacts/backups/aspect-trading-before-checkout-configuration-20261008-092432.sql`

The local storefront is now coherent, responsive, and operationally testable. Launch approval remains blocked by production payments, client product truth, production infrastructure/security, authenticated email, and tax/legal sign-off.
