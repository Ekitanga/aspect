<?php
/** Configure the local checkout baseline after the shipping rebuild. */
declare(strict_types=1);

$wp_root = rtrim(str_replace('\\', '/', (string) getenv('ASPECT_TRADING_WP_ROOT')), '/');
$loader = $wp_root . '/wp-load.php';
if (!is_file($loader)) {
    fwrite(STDERR, "WordPress loader was not found.\n");
    exit(1);
}
require $loader;

$cod = get_option('woocommerce_cod_settings', array());
$known_titles = array('Local development test payment (no charge)', 'Cash on Delivery (Nairobi only)');
if (!empty($cod['title']) && !in_array($cod['title'], $known_titles, true)) {
    throw new RuntimeException('Refusing to overwrite an unrecognized COD configuration.');
}

$cod = array_merge($cod, array(
    'enabled' => 'yes',
    'title' => 'Cash on Delivery (Nairobi only)',
    'description' => 'Pay in cash when your Nairobi delivery arrives. Please have the exact amount available where possible.',
    'instructions' => 'Your order is confirmed for cash payment on delivery within Nairobi. Our courier will contact you before arrival.',
    'enable_for_methods' => array('flat_rate:7', 'free_shipping:8', 'flat_rate:10', 'free_shipping:11'),
    'enable_for_virtual' => 'no',
));
update_option('woocommerce_cod_settings', $cod);

update_option('woocommerce_enable_guest_checkout', 'yes');
update_option('woocommerce_enable_checkout_login_reminder', 'yes');
update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes');
update_option('woocommerce_enable_myaccount_registration', 'yes');
update_option('woocommerce_registration_generate_username', 'yes');
update_option('woocommerce_registration_generate_password', 'yes');

$delivery_page = get_page_by_path('delivery-information');
$delivery_updated = false;
if ($delivery_page) {
    $content = (string) $delivery_page->post_content;
    $fixed = str_replace(
        array('href="/track-order/"', "href='/track-order/'"),
        'href="' . esc_url(home_url('/track-order/')) . '"',
        $content
    );
    if ($fixed !== $content) {
        $result = wp_update_post(array('ID' => $delivery_page->ID, 'post_content' => $fixed), true);
        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }
        $delivery_updated = true;
    }
}

echo wp_json_encode(array(
    'cod_title' => $cod['title'],
    'cod_methods' => $cod['enable_for_methods'],
    'guest_checkout' => get_option('woocommerce_enable_guest_checkout'),
    'optional_account_creation' => get_option('woocommerce_enable_signup_and_login_from_checkout'),
    'my_account_registration' => get_option('woocommerce_enable_myaccount_registration'),
    'delivery_link_updated' => $delivery_updated,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
