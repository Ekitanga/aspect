<?php
/** Final taxonomy and legacy-cleanup step for the catalogue upgrade. */
declare(strict_types=1);

$wp_root = rtrim(str_replace('\\', '/', (string) getenv('ASPECT_TRADING_WP_ROOT')), '/');
$loader = $wp_root . '/wp-load.php';
$data_file = __DIR__ . '/catalogue-upgrade-data.php';
if (!is_file($loader) || !is_file($data_file)) {
    fwrite(STDERR, "Required local files were not found.\n");
    exit(1);
}
require $loader;
$items = require $data_file;

$category_copy = array(
    'kitchenware'=>'Reliable cookware and preparation essentials selected for practical, confident everyday cooking.',
    'dinnerware'=>'Coordinated tableware, glassware and cutlery for relaxed family meals and polished entertaining.',
    'home-appliances'=>'Useful countertop and garment-care appliances chosen to make daily routines simpler.',
    'tv-audio'=>'Smart viewing and personal audio essentials for clearer, more enjoyable home entertainment.',
    'beddings'=>'Soft, easy-to-layer bedding designed to bring comfort and calm to every bedroom.',
    'gadgets-accessories'=>'Practical power, connectivity and workspace accessories for work, travel and everyday life.',
    'phone-tablets'=>'Connected devices and stands for communication, learning, entertainment and light work.',
    'furnitures'=>'Comfortable, functional furniture with clean proportions for contemporary Kenyan homes.',
    'decor-organization'=>'Warm finishing touches and flexible storage pieces that help every room feel considered.',
);
$brand_copy = array(
    'aerohome'=>'Practical home essentials with a clean, contemporary point of view.',
    'comfortliving'=>'Comfort-led furniture made for relaxed, modern homes.',
    'cuisinepro'=>'Dependable kitchen and table essentials for confident everyday use.',
    'lumina'=>'Streamlined home entertainment and personal audio essentials.',
    'officemate'=>'Useful workspace accessories for clearer, more connected desks.',
    'pureessentials'=>'Soft bedroom basics designed around calm, uncomplicated comfort.',
    'technest'=>'Connected personal devices for communication, learning and entertainment.',
    'urbangear'=>'Portable accessories built for busy days and dependable power on the move.',
);

$category_images = array();
foreach ($items as $item) {
    if (isset($category_images[$item['category']])) {
        continue;
    }
    $product = wc_get_product($item['id']);
    if (!$product || !$product->get_image_id()) {
        throw new RuntimeException("Missing upgraded image for {$item['slug']}.");
    }
    $category_images[$item['category']] = $product->get_image_id();
}

foreach ($category_copy as $slug=>$description) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        throw new RuntimeException("Missing category {$slug}.");
    }
    $result = wp_update_term($term->term_id, 'product_cat', array('description'=>$description));
    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }
    update_term_meta($term->term_id, 'thumbnail_id', $category_images[$slug]);
}

foreach ($brand_copy as $slug=>$description) {
    $term = get_term_by('slug', $slug, 'product_brand');
    if (!$term) {
        throw new RuntimeException("Missing brand {$slug}.");
    }
    $result = wp_update_term($term->term_id, 'product_brand', array('description'=>$description));
    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }
}

$deleted = array();
foreach (array(46,47,48,50,51,52) as $variation_id) {
    $post = get_post($variation_id);
    if (!$post) {
        continue;
    }
    if ($post->post_type !== 'product_variation' || $post->post_status !== 'draft') {
        throw new RuntimeException("Refusing to remove unexpected post {$variation_id}.");
    }
    if (!wp_delete_post($variation_id, true)) {
        throw new RuntimeException("Unable to remove retired variation {$variation_id}.");
    }
    $deleted[] = $variation_id;
}

wc_delete_product_transients();
echo wp_json_encode(array(
    'updated_categories'=>count($category_copy),
    'updated_brands'=>count($brand_copy),
    'deleted_retired_variations'=>$deleted,
), JSON_PRETTY_PRINT) . PHP_EOL;
