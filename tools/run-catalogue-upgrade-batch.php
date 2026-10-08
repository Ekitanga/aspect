<?php
/** Process a resumable three-product catalogue upgrade batch. */
declare(strict_types=1);

$wp_root = rtrim(str_replace('\\', '/', (string) getenv('ASPECT_TRADING_WP_ROOT')), '/');
$source_root = rtrim(str_replace('\\', '/', (string) getenv('ASPECT_TRADING_SOURCE_ROOT')), '/');
$loader = $wp_root . '/wp-load.php';
$asset_dir = $source_root . '/assets/images/catalogue';
$data_file = __DIR__ . '/catalogue-upgrade-data.php';
if (!is_file($loader) || !is_dir($asset_dir) || !is_file($data_file)) {
    fwrite(STDERR, "Required local files were not found.\n");
    exit(1);
}
require $loader;
$items = require $data_file;

function aspect_batch_import_image(string $path, string $title, string $alt, string $key): int
{
    $existing = get_posts(array(
        'post_type'=>'attachment', 'post_status'=>'inherit', 'posts_per_page'=>1,
        'fields'=>'ids', 'meta_key'=>'_aspect_catalogue_asset', 'meta_value'=>$key,
    ));
    if ($existing) {
        $id = (int) $existing[0];
        $file = get_attached_file($id);
        if ($file && is_file($file)) {
            update_post_meta($id, '_wp_attachment_image_alt', $alt);
            return $id;
        }
    }
    if (!is_file($path)) {
        throw new RuntimeException("Missing image: {$path}");
    }
    $contents = file_get_contents($path);
    $filename = 'aspect-' . basename($path);
    $upload = wp_upload_bits($filename, null, $contents);
    if (!empty($upload['error'])) {
        throw new RuntimeException((string) $upload['error']);
    }
    $type = wp_check_filetype($filename, null);
    $id = wp_insert_attachment(array(
        'post_mime_type'=>$type['type'], 'post_title'=>$title,
        'post_content'=>'', 'post_status'=>'inherit',
    ), $upload['file']);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
    update_post_meta($id, '_wp_attachment_image_alt', $alt);
    update_post_meta($id, '_aspect_catalogue_asset', $key);
    return (int) $id;
}

function aspect_batch_set_specs(WC_Product $product, array $specs): void
{
    $attributes = $product->get_attributes();
    foreach ($specs as $label=>$value) {
        $attribute = new WC_Product_Attribute();
        $attribute->set_name($label);
        $attribute->set_options(array($value));
        $attribute->set_position(count($attributes));
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $attributes[sanitize_title($label)] = $attribute;
    }
    $product->set_attributes($attributes);
}

function aspect_batch_description(WC_Product $product, array $item): string
{
    $stories = array(
        'kitchenware'=>'Designed for practical daily cooking, this piece combines straightforward care with a considered contemporary finish.',
        'dinnerware'=>'Its clean profile layers easily with different table linens and serving pieces for weekday meals and relaxed entertaining.',
        'home-appliances'=>'Clear controls and an approachable footprint make it a useful addition to a busy modern home.',
        'tv-audio'=>'The understated design fits neatly into contemporary living spaces while keeping everyday entertainment simple.',
        'beddings'=>'Calm colour, soft texture and easy layering help create a comfortable, restful bedroom.',
        'gadgets-accessories'=>'A compact, work-ready design helps you stay powered, connected and productive through the day.',
        'phone-tablets'=>'Built around communication, learning and entertainment, it balances portability with practical function.',
        'furnitures'=>'Clean proportions and warm materials make it easy to place in contemporary Kenyan homes.',
        'decor-organization'=>'A warm, versatile finish helps bring both order and visual calm to the room.',
    );
    $short = trim(wp_strip_all_tags($product->get_short_description()));
    $list = '';
    foreach ($item['specs'] as $label=>$value) {
        $list .= '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
    }
    return '<p>' . esc_html($short) . '</p><p>' . esc_html($stories[$item['category']]) . '</p>'
        . '<h3>Product details</h3><ul>' . $list . '</ul>'
        . '<h3>Delivery &amp; care</h3><p>' . esc_html($item['note']) . '</p>';
}

$offset = max(0, (int) getenv('ASPECT_BATCH_OFFSET'));
$batch_size = max(1, (int) (getenv('ASPECT_BATCH_SIZE') ?: 1));
$batch = array_slice($items, $offset, $batch_size);
$updated = array();
foreach ($batch as $item) {
    $post = get_post($item['id']);
    if (!$post || $post->post_type !== 'product' || $post->post_name !== $item['slug']) {
        throw new RuntimeException("Product identity check failed for {$item['id']} / {$item['slug']}.");
    }
    $product = wc_get_product($item['id']);
    if (!$product) {
        throw new RuntimeException("Unable to load product {$item['id']}.");
    }
    echo 'PROCESSING ' . $item['id'] . ' ' . $item['slug'] . PHP_EOL;

    $featured = aspect_batch_import_image(
        $asset_dir . '/' . $item['image'], $product->get_name(),
        $product->get_name() . ' — Aspect Trading', 'featured:' . $item['slug']
    );
    $detail_name = preg_replace('/\.webp$/', '-detail.webp', $item['image']);
    $gallery = aspect_batch_import_image(
        $asset_dir . '/' . $detail_name, $product->get_name() . ' detail',
        $product->get_name() . ' product detail — Aspect Trading', 'gallery:' . $item['slug']
    );

    $product->set_image_id($featured);
    $product->set_gallery_image_ids(array($gallery));
    $product->set_description(aspect_batch_description($product, $item));
    $product->set_weight($item['weight']);
    $product->set_length($item['length']);
    $product->set_width($item['width']);
    $product->set_height($item['height']);
    $product->set_stock_status($item['stock'] ?? 'instock');
    if (!$product->is_type('variable')) {
        $product->set_regular_price($item['regular']);
        $product->set_sale_price($item['sale']);
    }
    aspect_batch_set_specs($product, $item['specs']);
    $product->save();
    $brand_result = wp_set_object_terms($item['id'], $item['brand'], 'product_brand', false);
    if (is_wp_error($brand_result)) {
        throw new RuntimeException($brand_result->get_error_message());
    }

    foreach (($item['variations'] ?? array()) as $variation_id=>$prices) {
        $variation = wc_get_product($variation_id);
        if (!$variation || !$variation->is_type('variation') || (int) $variation->get_parent_id() !== (int) $item['id']) {
            throw new RuntimeException("Variation identity check failed for {$variation_id}.");
        }
        $variation->set_regular_price($prices[0]);
        $variation->set_sale_price($prices[1]);
        $variation->set_weight($item['weight']);
        $variation->set_length($item['length']);
        $variation->set_width($item['width']);
        $variation->set_height($item['height']);
        $variation->set_stock_status('instock');
        $variation->save();
    }
    if (!empty($item['variations'])) {
        WC_Product_Variable::sync($item['id']);
    }
    wc_delete_product_transients($item['id']);
    $updated[] = $item['id'];
    echo 'UPDATED ' . $item['id'] . PHP_EOL;
}
echo wp_json_encode(array('offset'=>$offset, 'updated'=>$updated), JSON_PRETTY_PRINT) . PHP_EOL;
