<?php
/**
 * Read-only local project audit.
 *
 * Run with XAMPP PHP. This reports public WordPress metadata and content
 * counts without exposing credentials from wp-config.php.
 */

declare(strict_types=1);

$wordpress_root = getenv('ASPECT_TRADING_WP_ROOT') ?: 'C:/xampp/htdocs/aspect-trading';
$loader         = rtrim(str_replace('\\', '/', $wordpress_root), '/') . '/wp-load.php';

if (!is_file($loader)) {
    fwrite(STDERR, "WordPress was not found at the configured path.\n");
    exit(1);
}

require $loader;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$theme  = wp_get_theme();
$themes = array();

foreach (wp_get_themes() as $slug => $installed_theme) {
    $errors   = $installed_theme->errors();
    $themes[] = array(
        'slug'     => $slug,
        'name'     => $installed_theme->get('Name'),
        'version'  => $installed_theme->get('Version'),
        'template' => $installed_theme->get('Template'),
        'errors'   => $errors ? $errors->get_error_messages() : array(),
    );
}

$plugins = array();

foreach (get_plugins() as $file => $plugin) {
    $plugins[] = array(
        'file'    => $file,
        'name'    => $plugin['Name'],
        'version' => $plugin['Version'],
        'active'  => is_plugin_active($file),
    );
}

$pages = array();

foreach (get_pages(array('post_status' => array('publish', 'draft', 'private'))) as $page) {
    $pages[] = array(
        'id'     => $page->ID,
        'title'  => $page->post_title,
        'status' => $page->post_status,
        'slug'   => $page->post_name,
    );
}

$woocommerce_pages = array();

foreach (array('shop', 'cart', 'checkout', 'myaccount', 'terms') as $key) {
    $page_id                 = (int) get_option('woocommerce_' . $key . '_page_id');
    $woocommerce_pages[$key] = array(
        'id'     => $page_id,
        'title'  => $page_id ? get_the_title($page_id) : '',
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

$output = array(
    'db_connected' => (bool) $GLOBALS['wpdb']->check_connection(false),
    'wp_version'   => get_bloginfo('version'),
    'site_url'     => site_url(),
    'home_url'     => home_url(),
    'timezone'     => wp_timezone_string(),
    'front_page'   => array(
        'show_on_front' => get_option('show_on_front'),
        'page_on_front' => (int) get_option('page_on_front'),
        'title'         => get_the_title((int) get_option('page_on_front')),
    ),
    'active_theme' => array(
        'stylesheet' => $theme->get_stylesheet(),
        'template'   => $theme->get_template(),
        'name'       => $theme->get('Name'),
        'version'    => $theme->get('Version'),
    ),
    'themes'                    => $themes,
    'plugins'                   => $plugins,
    'pages'                     => $pages,
    'woocommerce_pages'         => $woocommerce_pages,
    'counts'                    => array(
        'products'           => wp_count_posts('product'),
        'variations'         => wp_count_posts('product_variation'),
        'product_categories' => (int) wp_count_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false)),
        'media'              => wp_count_posts('attachment'),
    ),
    'brands'                    => $brands,
    'woocommerce_coming_soon'   => get_option('woocommerce_coming_soon'),
    'debug'                     => array(
        'WP_DEBUG'         => defined('WP_DEBUG') && WP_DEBUG,
        'WP_DEBUG_LOG'     => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
        'WP_DEBUG_DISPLAY' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
    ),
);

echo wp_json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
