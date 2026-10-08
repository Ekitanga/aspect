<?php
/** Rebuild the local Kenya shipping matrix with supported WooCommerce rules. */
declare(strict_types=1);

$wp_root = rtrim(str_replace('\\', '/', (string) getenv('ASPECT_TRADING_WP_ROOT')), '/');
$loader = $wp_root . '/wp-load.php';
if (!is_file($loader)) {
    fwrite(STDERR, "WordPress loader was not found.\n");
    exit(1);
}

require $loader;

$recognized_zone_names = array(
    'Nairobi (CBD & Surrounds)',
    'Nairobi (Extended)',
    'Major Towns (Mombasa, Kisumu, Nakuru, Eldoret, Thika)',
    'Rest of Kenya',
    'Click & Collect - Superior Center',
    'Nairobi CBD',
    'Nairobi Extended',
    'Major Towns',
);

$current_zones = WC_Shipping_Zones::get_zones('admin');
foreach ($current_zones as $zone_data) {
    if (!in_array($zone_data['zone_name'], $recognized_zone_names, true)) {
        throw new RuntimeException('Refusing to replace unrecognized shipping zone: ' . $zone_data['zone_name']);
    }
}

foreach ($current_zones as $zone_data) {
    WC_Shipping_Zones::delete_zone((int) $zone_data['zone_id']);
}

$uncovered = new WC_Shipping_Zone(0);
foreach ($uncovered->get_shipping_methods(false, 'admin') as $method) {
    $uncovered->delete_shipping_method($method->get_instance_id());
}

$zone_specs = array(
    array(
        'name' => 'Nairobi CBD',
        'order' => 0,
        'locations' => array(
            array('code' => 'KE:KE30', 'type' => 'state'),
            array('code' => '00100', 'type' => 'postcode'),
            array('code' => '00200', 'type' => 'postcode'),
        ),
        'delivery_title' => 'Nairobi CBD delivery',
        'cost' => '200',
        'free_minimum' => '10000',
    ),
    array(
        'name' => 'Nairobi Extended',
        'order' => 1,
        'locations' => array(
            array('code' => 'KE:KE30', 'type' => 'state'),
        ),
        'delivery_title' => 'Nairobi extended-area delivery',
        'cost' => '300',
        'free_minimum' => '10000',
    ),
    array(
        'name' => 'Major Towns',
        'order' => 2,
        'locations' => array(
            array('code' => 'KE:KE28', 'type' => 'state'),
            array('code' => 'KE:KE17', 'type' => 'state'),
            array('code' => 'KE:KE31', 'type' => 'state'),
            array('code' => 'KE:KE44', 'type' => 'state'),
            array('code' => 'KE:KE13', 'type' => 'state'),
        ),
        'delivery_title' => 'Major-town delivery',
        'cost' => '500',
        'free_minimum' => '15000',
    ),
    array(
        'name' => 'Rest of Kenya',
        'order' => 3,
        'locations' => array(
            array('code' => 'KE', 'type' => 'country'),
        ),
        'delivery_title' => 'Kenya nationwide delivery',
        'cost' => '700',
        'free_minimum' => '20000',
    ),
);

$created = array();
foreach ($zone_specs as $spec) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name($spec['name']);
    $zone->set_zone_order($spec['order']);
    $zone->save();
    foreach ($spec['locations'] as $location) {
        $zone->add_location($location['code'], $location['type']);
    }
    $zone->save();

    $flat_rate_id = $zone->add_shipping_method('flat_rate');
    $free_shipping_id = $zone->add_shipping_method('free_shipping');
    $pickup_id = $zone->add_shipping_method('local_pickup');
    if (!$flat_rate_id || !$free_shipping_id || !$pickup_id) {
        throw new RuntimeException('Unable to create all methods for ' . $spec['name']);
    }

    update_option('woocommerce_flat_rate_' . $flat_rate_id . '_settings', array(
        'title' => $spec['delivery_title'],
        'tax_status' => 'none',
        'cost' => $spec['cost'],
    ));
    update_option('woocommerce_free_shipping_' . $free_shipping_id . '_settings', array(
        'title' => 'Free delivery',
        'requires' => 'min_amount',
        'min_amount' => $spec['free_minimum'],
        'ignore_discounts' => 'no',
    ));
    update_option('woocommerce_local_pickup_' . $pickup_id . '_settings', array(
        'title' => 'Click & Collect — Superior Center',
        'tax_status' => 'none',
        'cost' => '0',
    ));

    $created[] = array(
        'id' => $zone->get_id(),
        'name' => $spec['name'],
        'flat_rate' => (int) $flat_rate_id,
        'free_shipping' => (int) $free_shipping_id,
        'local_pickup' => (int) $pickup_id,
    );
}

update_option('woocommerce_allowed_countries', 'specific');
update_option('woocommerce_specific_allowed_countries', array('KE'));
update_option('woocommerce_ship_to_countries', 'specific');
update_option('woocommerce_specific_ship_to_countries', array('KE'));
update_option('woocommerce_ship_to_destination', 'shipping');
update_option('woocommerce_shipping_cost_requires_address', 'yes');
update_option('woocommerce_shipping_debug_mode', 'no');

WC_Cache_Helper::invalidate_cache_group('shipping_zones');
WC_Cache_Helper::get_transient_version('shipping', true);

$match_cases = array(
    array('label' => 'Nairobi CBD 00100', 'country' => 'KE', 'state' => 'KE30', 'postcode' => '00100', 'expected' => 'Nairobi CBD'),
    array('label' => 'Nairobi Extended 00623', 'country' => 'KE', 'state' => 'KE30', 'postcode' => '00623', 'expected' => 'Nairobi Extended'),
    array('label' => 'Mombasa', 'country' => 'KE', 'state' => 'KE28', 'postcode' => '80100', 'expected' => 'Major Towns'),
    array('label' => 'Eldoret', 'country' => 'KE', 'state' => 'KE44', 'postcode' => '30100', 'expected' => 'Major Towns'),
    array('label' => 'Kitui', 'country' => 'KE', 'state' => 'KE18', 'postcode' => '90200', 'expected' => 'Rest of Kenya'),
);

$matches = array();
foreach ($match_cases as $case) {
    $package = array('destination' => array(
        'country' => $case['country'],
        'state' => $case['state'],
        'postcode' => $case['postcode'],
    ));
    $matched = WC_Shipping_Zones::get_zone_matching_package($package)->get_zone_name();
    if ($matched !== $case['expected']) {
        throw new RuntimeException(sprintf('%s matched %s instead of %s.', $case['label'], $matched, $case['expected']));
    }
    $matches[$case['label']] = $matched;
}

echo wp_json_encode(array(
    'created_zones' => $created,
    'verified_matches' => $matches,
    'shipping_country' => get_option('woocommerce_specific_ship_to_countries'),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
