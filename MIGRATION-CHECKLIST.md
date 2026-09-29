# Production Migration Checklist

- [ ] Change the WordPress administrator credentials.
- [ ] Review every WordPress administrator account.
- [ ] Replace local development database credentials.
- [ ] Remove unused development database accounts.
- [ ] Replace `admin@aspect-trading.local` with an approved business email.
- [ ] Remove other development-only email addresses.
- [ ] Set `WP_DEBUG` to false.
- [ ] Disable `WP_DEBUG_LOG` and keep `WP_DEBUG_DISPLAY` disabled.
- [ ] Remove or replace development/demo products.
- [ ] Remove test orders and test customers.
- [ ] Configure and verify production payment credentials.
- [ ] Configure and test production shipping/delivery methods.
- [ ] Configure and test production email delivery.
- [ ] Review all production API credentials and integrations.
- [ ] Confirm no development credentials or `wp-config.php` are in source control or deployment artifacts.
- [ ] Configure production backups and verify the restore procedure.
- [ ] Enable HTTPS and verify secure cookies.
- [ ] Generate production-specific WordPress salts/security keys.
- [ ] Remove development-only accounts, tools, setup helpers, and debug artifacts.
- [ ] Review administrator capabilities and least-privilege access.
- [ ] Complete the final security review, theme/plugin licensing check, and production smoke test.

The local Aspect Trading database and administrator credentials are temporary development credentials and must be replaced before production launch.
