<?php
/** Read-only local WooCommerce shipping and checkout audit. */
declare(strict_types=1);

$wp_root = rtrim(str_replace('\\', '/', (string) getenv('ASPECT_TRADING_WP_ROOT')), '/');
$loader = $wp_root . '/wp-load.php';
if (!is_file($loader)) {
    fwrite(STDERR, "WordPress loader was not found.\n");
    exit(1);
}

require $loader;

$zones = array();
foreach (WC_Shipping_Zones::get_zones('admin') as $zone_data) {
    $zone = new WC_Shipping_Zone((int) $zone_data['zone_id']);
    $methods = array();
    foreach ($zone->get_shipping_methods(false, 'admin') as $method) {
        $methods[] = array(
            'id' => $method->id,
            'instance_id' => $method->get_instance_id(),
            'enabled' => $method->enabled,
            'title' => $method->title,
            'settings' => $method->instance_settings,
        );
    }
    $zones[] = array(
        'id' => $zone->get_id(),
        'name' => $zone->get_zone_name(),
        'order' => $zone->get_zone_order(),
        'locations' => array_map(
            static fn($location): array => array('code' => $location->code, 'type' => $location->type),
            $zone->get_zone_locations()
        ),
        'methods' => $methods,
    );
}

$rest = new WC_Shipping_Zone(0);
$rest_methods = array();
foreach ($rest->get_shipping_methods(false, 'admin') as $method) {
    $rest_methods[] = array(
        'id' => $method->id,
        'instance_id' => $method->get_instance_id(),
        'enabled' => $method->enabled,
        'title' => $method->title,
        'settings' => $method->instance_settings,
    );
}
$zones[] = array(
    'id' => 0,
    'name' => $rest->get_zone_name(),
    'order' => PHP_INT_MAX,
    'locations' => array(),
    'methods' => $rest_methods,
);

$gateways = array();
foreach (WC()->payment_gateways()->payment_gateways() as $gateway) {
    $gateways[] = array(
        'id' => $gateway->id,
        'title' => $gateway->get_title(),
        'enabled' => $gateway->enabled,
        'description' => $gateway->get_description(),
        'settings' => $gateway->settings,
        'supports' => $gateway->supports,
    );
}

$output = array(
    'shipping_enabled' => 'disabled' !== get_option('woocommerce_ship_to_countries'),
    'ship_to_countries' => get_option('woocommerce_ship_to_countries'),
    'specific_ship_to_countries' => get_option('woocommerce_specific_ship_to_countries'),
    'allowed_countries' => get_option('woocommerce_allowed_countries'),
    'specific_allowed_countries' => get_option('woocommerce_specific_allowed_countries'),
    'default_country' => get_option('woocommerce_default_country'),
    'shipping_destination' => get_option('woocommerce_ship_to_destination'),
    'hide_shipping_until_address' => get_option('woocommerce_shipping_cost_requires_address'),
    'tax_enabled' => wc_tax_enabled(),
    'currency' => get_woocommerce_currency(),
    'kenya_states' => WC()->countries->get_states('KE'),
    'zones' => $zones,
    'gateways' => $gateways,
    'checkout' => array(
        'guest_checkout' => get_option('woocommerce_enable_guest_checkout'),
        'login_reminder' => get_option('woocommerce_enable_checkout_login_reminder'),
        'account_creation_checkout' => get_option('woocommerce_enable_signup_and_login_from_checkout'),
        'account_creation_myaccount' => get_option('woocommerce_enable_myaccount_registration'),
        'privacy_page_id' => (int) get_option('wp_page_for_privacy_policy'),
        'terms_page_id' => (int) get_option('woocommerce_terms_page_id'),
    ),
);

echo wp_json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
