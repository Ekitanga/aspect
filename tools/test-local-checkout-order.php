<?php
/** Create, process, verify, and remove one local Nairobi COD test order. */
declare(strict_types=1);

$wp_root = rtrim(str_replace('\\', '/', (string) getenv('ASPECT_TRADING_WP_ROOT')), '/');
$loader = $wp_root . '/wp-load.php';
if (!is_file($loader)) {
    fwrite(STDERR, "WordPress loader was not found.\n");
    exit(1);
}
require $loader;

foreach (array('new_order', 'cancelled_order', 'failed_order', 'customer_on_hold_order', 'customer_processing_order', 'customer_completed_order', 'customer_invoice', 'customer_note') as $email_id) {
    add_filter('woocommerce_email_enabled_' . $email_id, '__return_false');
}

if (!WC()->session) {
    WC()->initialize_session();
}
if (!WC()->customer) {
    WC()->customer = new WC_Customer(0, true);
}
if (!WC()->cart) {
    WC()->initialize_cart();
}

$order_id = 0;
$summary = array();
try {
    WC()->cart->empty_cart(true);
    if (!WC()->cart->add_to_cart(76, 1)) {
        throw new RuntimeException('Unable to add the checkout test product.');
    }

    $address = array(
        'first_name' => 'Local',
        'last_name' => 'Checkout Test',
        'company' => '',
        'address_1' => 'Superior Center, Shop L3',
        'address_2' => '',
        'city' => 'Nairobi',
        'state' => 'KE30',
        'postcode' => '00100',
        'country' => 'KE',
        'email' => 'checkout-test@example.invalid',
        'phone' => '0712345678',
    );
    foreach ($address as $key => $value) {
        $billing_setter = 'set_billing_' . $key;
        if (is_callable(array(WC()->customer, $billing_setter))) {
            WC()->customer->{$billing_setter}($value);
        }
        if (!in_array($key, array('email', 'phone'), true)) {
            $shipping_setter = 'set_shipping_' . $key;
            if (is_callable(array(WC()->customer, $shipping_setter))) {
                WC()->customer->{$shipping_setter}($value);
            }
        }
    }
    WC()->customer->set_calculated_shipping(true);
    WC()->session->set('chosen_shipping_methods', array('flat_rate:7'));
    WC()->cart->calculate_totals();

    $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
    if (empty($available_gateways['cod'])) {
        throw new RuntimeException('Nairobi COD was not available to the checkout test.');
    }

    $data = array(
        'billing_first_name' => $address['first_name'],
        'billing_last_name' => $address['last_name'],
        'billing_company' => '',
        'billing_address_1' => $address['address_1'],
        'billing_address_2' => '',
        'billing_city' => $address['city'],
        'billing_state' => $address['state'],
        'billing_postcode' => $address['postcode'],
        'billing_country' => $address['country'],
        'billing_email' => $address['email'],
        'billing_phone' => $address['phone'],
        'shipping_first_name' => $address['first_name'],
        'shipping_last_name' => $address['last_name'],
        'shipping_company' => '',
        'shipping_address_1' => $address['address_1'],
        'shipping_address_2' => '',
        'shipping_city' => $address['city'],
        'shipping_state' => $address['state'],
        'shipping_postcode' => $address['postcode'],
        'shipping_country' => $address['country'],
        'payment_method' => 'cod',
        'order_comments' => 'Automated local checkout verification; remove after test.',
        'terms' => 1,
    );

    $order_id = WC_Checkout::instance()->create_order($data);
    if (is_wp_error($order_id)) {
        throw new RuntimeException($order_id->get_error_message());
    }
    $order = wc_get_order($order_id);
    if (!$order) {
        throw new RuntimeException('The checkout test did not create an order.');
    }

    $result = $available_gateways['cod']->process_payment($order_id);
    $order = wc_get_order($order_id);
    if (($result['result'] ?? '') !== 'success' || !$order || !$order->has_status('processing')) {
        throw new RuntimeException('The COD gateway did not process the local test order successfully.');
    }
    if (abs((float) $order->get_shipping_total() - 200.0) > 0.001) {
        throw new RuntimeException('The local test order has an incorrect Nairobi CBD delivery charge.');
    }
    if (abs((float) $order->get_total() - 7699.0) > 0.001) {
        throw new RuntimeException('The local test order has an incorrect final total.');
    }

    $summary = array(
        'order_id' => $order_id,
        'status' => $order->get_status(),
        'payment_method' => $order->get_payment_method(),
        'shipping_total' => (float) $order->get_shipping_total(),
        'order_total' => (float) $order->get_total(),
        'line_items' => count($order->get_items()),
        'redirect_created' => !empty($result['redirect']),
    );
} finally {
    WC()->cart->empty_cart(true);
    if ($order_id) {
        $cleanup_order = wc_get_order($order_id);
        if ($cleanup_order) {
            $cleanup_order->delete(true);
        }
        $summary['test_order_removed'] = !wc_get_order($order_id);
    }
}

if (empty($summary['test_order_removed'])) {
    throw new RuntimeException('The temporary checkout test order was not removed.');
}
echo wp_json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
