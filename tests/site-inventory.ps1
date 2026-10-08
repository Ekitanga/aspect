param(
    [string] $WordPressRoot = 'C:\xampp\htdocs\aspect-trading',
    [string] $PhpPath = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'

foreach ($path in @($WordPressRoot, $PhpPath)) {
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Required path was not found: $path"
    }
}

$php = @'
$root = getenv('ASPECT_TRADING_WP_ROOT');
$loader = rtrim(str_replace('\\', '/', $root), '/') . '/wp-load.php';
if (!is_file($loader)) {
    fwrite(STDERR, "WordPress loader was not found.\n");
    exit(1);
}
require $loader;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$theme = wp_get_theme();
$themes = array();
foreach (wp_get_themes() as $slug => $installed) {
    $errors = $installed->errors();
    $themes[] = array(
        'slug' => $slug,
        'name' => $installed->get('Name'),
        'version' => $installed->get('Version'),
        'declared_template' => $installed->get('Template'),
        'errors' => $errors ? $errors->get_error_messages() : array(),
    );
}

$plugins = array();
foreach (get_plugins() as $file => $plugin) {
    $plugins[] = array(
        'file' => $file,
        'name' => $plugin['Name'],
        'version' => $plugin['Version'],
        'active' => is_plugin_active($file),
    );
}

$pages = array();
foreach (get_pages(array('post_status' => array('publish', 'draft', 'private'))) as $page) {
    $pages[] = array(
        'id' => $page->ID,
        'title' => $page->post_title,
        'status' => $page->post_status,
        'slug' => $page->post_name,
    );
}

$woocommerce_pages = array();
foreach (array('shop', 'cart', 'checkout', 'myaccount', 'terms') as $key) {
    $page_id = (int) get_option('woocommerce_' . $key . '_page_id');
    $woocommerce_pages[$key] = array(
        'id' => $page_id,
        'title' => $page_id ? get_the_title($page_id) : '',
        'status' => $page_id ? get_post_status($page_id) : '',
    );
}

$brands = array();
foreach (get_object_taxonomies('product', 'objects') as $name => $taxonomy) {
    if (false !== stripos($name, 'brand') || false !== stripos($taxonomy->label, 'brand')) {
        $brands[$name] = array(
            'label' => $taxonomy->label,
            'count' => (int) wp_count_terms(array('taxonomy' => $name, 'hide_empty' => false)),
        );
    }
}

$term_inventory = array(
    'categories' => array(),
    'brands' => array(),
);
foreach (get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name')) as $term) {
    $term_inventory['categories'][] = array(
        'id' => (int) $term->term_id,
        'name' => $term->name,
        'slug' => $term->slug,
        'count' => (int) $term->count,
        'description' => $term->description,
        'has_thumbnail' => (bool) get_term_meta($term->term_id, 'thumbnail_id', true),
    );
}
if (taxonomy_exists('product_brand')) {
    foreach (get_terms(array('taxonomy' => 'product_brand', 'hide_empty' => false, 'orderby' => 'name')) as $term) {
        $term_inventory['brands'][] = array(
            'id' => (int) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'count' => (int) $term->count,
            'description' => $term->description,
        );
    }
}

$catalogue = array();
$loaded_product_ids = array();
if (function_exists('wc_get_products')) {
    foreach (wc_get_products(array('status' => 'publish', 'limit' => -1, 'orderby' => 'ID', 'order' => 'ASC')) as $product) {
        $loaded_product_ids[] = $product->get_id();
        $tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'slugs'));
        $catalogue[] = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'type' => $product->get_type(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'stock_status' => $product->get_stock_status(),
            'image_id' => $product->get_image_id(),
            'has_image' => (bool) $product->get_image_id(),
            'gallery_images' => count($product->get_gallery_image_ids()),
            'has_weight' => '' !== $product->get_weight(),
            'has_dimensions' => '' !== $product->get_length() && '' !== $product->get_width() && '' !== $product->get_height(),
            'short_description_length' => strlen(wp_strip_all_tags($product->get_short_description())),
            'description_length' => strlen(wp_strip_all_tags($product->get_description())),
            'rating_count' => $product->get_rating_count(),
            'demo_tagged' => !is_wp_error($tags) && in_array('development-demo', $tags, true),
            'categories' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')),
            'brands' => taxonomy_exists('product_brand') ? wp_get_post_terms($product->get_id(), 'product_brand', array('fields' => 'names')) : array(),
            'raw_product_attributes' => get_post_meta($product->get_id(), '_product_attributes', true),
            'url' => get_permalink($product->get_id()),
        );
    }
}

$raw_product_ids = get_posts(array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'orderby' => 'ID',
    'order' => 'ASC',
));
$unloadable_products = array();
foreach (array_diff($raw_product_ids, $loaded_product_ids) as $product_id) {
    $unloadable_products[] = array(
        'id' => $product_id,
        'title' => get_the_title($product_id),
        'slug' => get_post_field('post_name', $product_id),
    );
}

$media = array();
foreach (get_posts(array('post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC')) as $attachment) {
    $media[] = array(
        'id' => $attachment->ID,
        'title' => $attachment->post_title,
        'file' => get_post_meta($attachment->ID, '_wp_attached_file', true),
        'url' => wp_get_attachment_url($attachment->ID),
    );
}

$variations = array();
foreach (get_posts(array('post_type' => 'product_variation', 'post_status' => array('publish', 'draft', 'private'), 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids')) as $variation_id) {
    $raw_size_before = $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare("SELECT meta_value FROM {$GLOBALS['wpdb']->postmeta} WHERE post_id = %d AND meta_key = 'attribute_size' LIMIT 1", $variation_id));
    $variation = wc_get_product($variation_id);
    $variations[] = array(
        'id' => $variation_id,
        'parent_id' => (int) wp_get_post_parent_id($variation_id),
        'parent_name' => get_the_title(wp_get_post_parent_id($variation_id)),
        'status' => get_post_status($variation_id),
        'attributes' => $variation ? $variation->get_attributes() : array(),
        'client_option' => get_post_meta($variation_id, '_aspect_client_variation_option', true),
        'raw_size' => get_post_meta($variation_id, 'attribute_size', true),
        'raw_size_before' => $raw_size_before,
        'raw_size_after' => $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare("SELECT meta_value FROM {$GLOBALS['wpdb']->postmeta} WHERE post_id = %d AND meta_key = 'attribute_size' LIMIT 1", $variation_id)),
    );
}

$output = array(
    'db_connected' => (bool) $GLOBALS['wpdb']->check_connection(false),
    'wp_version' => get_bloginfo('version'),
    'site_url' => site_url(),
    'home_url' => home_url(),
    'timezone' => wp_timezone_string(),
    'front_page' => array(
        'show_on_front' => get_option('show_on_front'),
        'page_on_front' => (int) get_option('page_on_front'),
        'title' => get_the_title((int) get_option('page_on_front')),
    ),
    'active_theme' => array(
        'stylesheet' => get_stylesheet(),
        'runtime_template' => get_template(),
        'declared_template' => $theme->get('Template'),
        'name' => $theme->get('Name'),
        'version' => $theme->get('Version'),
    ),
    'themes' => $themes,
    'plugins' => $plugins,
    'pages' => $pages,
    'woocommerce_pages' => $woocommerce_pages,
    'counts' => array(
        'published_product_posts' => (int) wp_count_posts('product')->publish,
        'published_variations' => (int) wp_count_posts('product_variation')->publish,
        'product_categories' => (int) wp_count_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false)),
        'media' => (int) wp_count_posts('attachment')->inherit,
    ),
    'brands' => $brands,
    'term_inventory' => $term_inventory,
    'catalogue' => $catalogue,
    'unloadable_products' => $unloadable_products,
    'media' => $media,
    'variations' => $variations,
    'woocommerce_coming_soon' => get_option('woocommerce_coming_soon'),
    'debug' => array(
        'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG,
        'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
        'WP_DEBUG_DISPLAY' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
    ),
);

echo wp_json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
'@

$previousRoot = $env:ASPECT_TRADING_WP_ROOT
$env:ASPECT_TRADING_WP_ROOT = $WordPressRoot
$tempRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$tempScript = Join-Path $tempRoot ("aspect-trading-inventory-{0}.php" -f [guid]::NewGuid().ToString('N'))

if (-not $tempScript.StartsWith($tempRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'The temporary inventory script path resolved outside the system temp directory.'
}

try {
    [System.IO.File]::WriteAllText($tempScript, "<?php`n$php", [System.Text.UTF8Encoding]::new($false))
    & $PhpPath $tempScript
    if ($LASTEXITCODE -ne 0) {
        throw "PHP inventory failed with exit code $LASTEXITCODE"
    }
} finally {
    $env:ASPECT_TRADING_WP_ROOT = $previousRoot
    if (Test-Path -LiteralPath $tempScript -PathType Leaf) {
        Remove-Item -LiteralPath $tempScript -Force
    }
}
