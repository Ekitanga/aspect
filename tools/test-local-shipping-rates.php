<?php
/** Functional shipping-rate checks against the local WooCommerce cart. */
declare(strict_types=1);

$wp_root = rtrim(str_replace('\\', '/', (string) getenv('ASPECT_TRADING_WP_ROOT')), '/');
$loader = $wp_root . '/wp-load.php';
if (!is_file($loader)) {
    fwrite(STDERR, "WordPress loader was not found.\n");
    exit(1);
}
require $loader;

if (!WC()->session) {
    WC()->initialize_session();
}
if (!WC()->customer) {
    WC()->customer = new WC_Customer(0, true);
}
if (!WC()->cart) {
    WC()->initialize_cart();
}

$cases = array(
    array('label' => 'CBD below threshold', 'product' => 76, 'state' => 'KE30', 'postcode' => '00100', 'expected_cod' => true, 'expected' => array('flat_rate' => 200.0, 'local_pickup' => 0.0)),
    array('label' => 'CBD free threshold', 'product' => 89, 'state' => 'KE30', 'postcode' => '00100', 'expected_cod' => true, 'expected' => array('free_shipping' => 0.0, 'local_pickup' => 0.0)),
    array('label' => 'Nairobi Extended below threshold', 'product' => 76, 'state' => 'KE30', 'postcode' => '00623', 'expected_cod' => true, 'expected' => array('flat_rate' => 300.0, 'local_pickup' => 0.0)),
    array('label' => 'Nairobi Extended free threshold', 'product' => 89, 'state' => 'KE30', 'postcode' => '00623', 'expected_cod' => true, 'expected' => array('free_shipping' => 0.0, 'local_pickup' => 0.0)),
    array('label' => 'Major Town below threshold', 'product' => 76, 'state' => 'KE28', 'postcode' => '80100', 'expected_cod' => false, 'expected' => array('flat_rate' => 500.0, 'local_pickup' => 0.0)),
    array('label' => 'Major Town free threshold', 'product' => 89, 'state' => 'KE28', 'postcode' => '80100', 'expected_cod' => false, 'expected' => array('free_shipping' => 0.0, 'local_pickup' => 0.0)),
    array('label' => 'Rest of Kenya below threshold', 'product' => 76, 'state' => 'KE18', 'postcode' => '90200', 'expected_cod' => false, 'expected' => array('flat_rate' => 700.0, 'local_pickup' => 0.0)),
    array('label' => 'Rest of Kenya free threshold', 'product' => 89, 'state' => 'KE18', 'postcode' => '90200', 'expected_cod' => false, 'expected' => array('free_shipping' => 0.0, 'local_pickup' => 0.0)),
);

$results = array();
foreach ($cases as $case) {
    WC()->cart->empty_cart(true);
    if (!WC()->cart->add_to_cart($case['product'], 1)) {
        throw new RuntimeException('Unable to add the test product for ' . $case['label']);
    }

    WC()->customer->set_billing_country('KE');
    WC()->customer->set_billing_state($case['state']);
    WC()->customer->set_billing_postcode($case['postcode']);
    WC()->customer->set_shipping_country('KE');
    WC()->customer->set_shipping_state($case['state']);
    WC()->customer->set_shipping_postcode($case['postcode']);
    WC()->customer->set_calculated_shipping(true);
    WC()->cart->calculate_totals();

    WC()->shipping()->reset_shipping();
    $packages = WC()->shipping()->calculate_shipping(WC()->cart->get_shipping_packages());
    $rates = array();
    $delivery_rate_id = '';
    foreach (($packages[0]['rates'] ?? array()) as $rate) {
        $rates[$rate->method_id] = array(
            'label' => $rate->get_label(),
            'cost' => (float) $rate->get_cost(),
        );
        if ('local_pickup' !== $rate->method_id) {
            $delivery_rate_id = $rate->get_id();
        }
    }

    if (array_keys($rates) !== array_keys($case['expected'])) {
        throw new RuntimeException(sprintf(
            '%s returned methods [%s], expected [%s].',
            $case['label'],
            implode(', ', array_keys($rates)),
            implode(', ', array_keys($case['expected']))
        ));
    }
    foreach ($case['expected'] as $method_id => $expected_cost) {
        if (abs($rates[$method_id]['cost'] - $expected_cost) > 0.001) {
            throw new RuntimeException(sprintf('%s returned an incorrect %s cost.', $case['label'], $method_id));
        }
    }

    WC()->session->set('chosen_shipping_methods', array($delivery_rate_id));
    WC()->cart->calculate_shipping();
    $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
    $cod_available = isset($available_gateways['cod']);
    if ($cod_available !== $case['expected_cod']) {
        throw new RuntimeException(sprintf('%s returned an incorrect COD availability state.', $case['label']));
    }

    $results[] = array(
        'case' => $case['label'],
        'subtotal' => (float) WC()->cart->get_subtotal(),
        'rates' => $rates,
        'cod_available' => $cod_available,
    );
}

WC()->cart->empty_cart(true);
echo wp_json_encode(array('passed' => count($results), 'results' => $results), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
