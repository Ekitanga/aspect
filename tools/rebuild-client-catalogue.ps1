param(
    [string] $WordPressRoot = 'C:\xampp\htdocs\aspect-trading',
    [string] $PhpPath = 'C:\xampp\php\php.exe',
    [switch] $ValidateOnly
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

if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not available.\n");
    exit(1);
}

$seed_version = 'client-categories-v1';
$categories = array(
    array('name' => 'Kitchenware', 'slug' => 'kitchenware', 'image' => 'appliances.jpg'),
    array('name' => 'Dinnerware', 'slug' => 'dinnerware', 'image' => 'appliances.jpg'),
    array('name' => 'Home Appliances', 'slug' => 'home-appliances', 'image' => 'appliances.jpg'),
    array('name' => 'TV & Audio', 'slug' => 'tv-audio', 'image' => 'audio.jpg'),
    array('name' => 'Beddings', 'slug' => 'beddings', 'image' => 'fashion.jpg'),
    array('name' => 'Gadgets & Accessories', 'slug' => 'gadgets-accessories', 'image' => 'computing.jpg'),
    array('name' => 'Phone & Tablets', 'slug' => 'phone-tablets', 'image' => 'phones.jpg'),
    array('name' => 'Furnitures', 'slug' => 'furnitures', 'image' => 'home-office.jpg'),
    array('name' => 'Decor & Organization', 'slug' => 'decor-organization', 'image' => 'home-office.jpg'),
);

$products = array(
    array('category' => 'kitchenware', 'name' => 'Non-Stick Frying Pan Set', 'slug' => 'non-stick-frying-pan-set', 'regular' => '89', 'sale' => '79'),
    array('category' => 'kitchenware', 'name' => 'Bamboo Cutting Board with Juice Groove', 'slug' => 'bamboo-cutting-board-with-juice-groove', 'regular' => '24'),
    array('category' => 'kitchenware', 'name' => 'Stainless Steel Cookware Set, 8 Piece', 'slug' => 'stainless-steel-cookware-set-8-piece', 'regular' => '149', 'sale' => '129', 'gallery' => array('home-office.jpg')),
    array('category' => 'dinnerware', 'name' => 'Porcelain Dinner Set, 24 Piece', 'slug' => 'porcelain-dinner-set-24-piece', 'regular' => '95', 'sale' => '82'),
    array('category' => 'dinnerware', 'name' => 'Clear Glass Tumbler Set, 6 Piece', 'slug' => 'clear-glass-tumbler-set-6-piece', 'regular' => '28'),
    array('category' => 'dinnerware', 'name' => 'Stainless Steel Cutlery Set, 24 Piece', 'slug' => 'stainless-steel-cutlery-set-24-piece', 'regular' => '45'),
    array('category' => 'home-appliances', 'name' => 'Digital Air Fryer, 6 L', 'slug' => 'digital-air-fryer-6-l', 'regular' => '139', 'sale' => '119'),
    array('category' => 'home-appliances', 'name' => 'Countertop Blender with Glass Jar', 'slug' => 'countertop-blender-with-glass-jar', 'regular' => '79'),
    array('category' => 'home-appliances', 'name' => 'Steam Iron with Ceramic Soleplate', 'slug' => 'steam-iron-with-ceramic-soleplate', 'regular' => '49', 'sale' => '42'),
    array('category' => 'tv-audio', 'name' => '43-inch 4K Smart TV', 'slug' => '43-inch-4k-smart-tv', 'regular' => '429', 'sale' => '389', 'image' => 'phones.jpg'),
    array('category' => 'tv-audio', 'name' => 'Compact Bluetooth Soundbar', 'slug' => 'compact-bluetooth-soundbar', 'regular' => '129'),
    array('category' => 'tv-audio', 'name' => 'Wireless Over-Ear Headphones', 'slug' => 'wireless-over-ear-headphones', 'regular' => '99', 'sale' => '84', 'gallery' => array('audio-detail.jpg')),
    array('category' => 'beddings', 'name' => 'Cotton Duvet Cover Set', 'slug' => 'cotton-duvet-cover-set', 'regular' => '68', 'type' => 'variable', 'options' => array('Single', 'Double', 'King')),
    array('category' => 'beddings', 'name' => 'Fitted Bedsheet Set', 'slug' => 'fitted-bedsheet-set', 'regular' => '42', 'sale' => '36', 'type' => 'variable', 'options' => array('Single', 'Double', 'King')),
    array('category' => 'beddings', 'name' => 'Hypoallergenic Pillow Pair', 'slug' => 'hypoallergenic-pillow-pair', 'regular' => '34'),
    array('category' => 'gadgets-accessories', 'name' => '20,000 mAh Fast-Charge Power Bank', 'slug' => '20000-mah-fast-charge-power-bank', 'regular' => '65', 'sale' => '55'),
    array('category' => 'gadgets-accessories', 'name' => 'Multi-Port USB-C Hub with HDMI, Ethernet, Card Reader and 100 W Power Delivery', 'slug' => 'multi-port-usb-c-hub-hdmi-ethernet-card-reader-100w-power-delivery', 'regular' => '89'),
    array('category' => 'gadgets-accessories', 'name' => 'Wireless Keyboard and Mouse Combo', 'slug' => 'wireless-keyboard-and-mouse-combo', 'regular' => '58', 'sale' => '49'),
    array('category' => 'phone-tablets', 'name' => '5G Smartphone, 256 GB', 'slug' => '5g-smartphone-256-gb', 'regular' => '499', 'sale' => '459'),
    array('category' => 'phone-tablets', 'name' => '10-inch Android Tablet, 128 GB', 'slug' => '10-inch-android-tablet-128-gb', 'regular' => '279'),
    array('category' => 'phone-tablets', 'name' => 'Rugged Tablet and Phone Stand', 'slug' => 'rugged-tablet-and-phone-stand', 'regular' => '35', 'sale' => '29'),
    array('category' => 'furnitures', 'name' => '4-Seater Dining Table Set', 'slug' => '4-seater-dining-table-set', 'regular' => '499', 'sale' => '449'),
    array('category' => 'furnitures', 'name' => 'Compact TV Stand with Storage', 'slug' => 'compact-tv-stand-with-storage', 'regular' => '189'),
    array('category' => 'furnitures', 'name' => 'Upholstered Accent Chair', 'slug' => 'upholstered-accent-chair', 'regular' => '229', 'sale' => '199'),
    array('category' => 'decor-organization', 'name' => 'Framed Wall Mirror', 'slug' => 'framed-wall-mirror', 'regular' => '119'),
    array('category' => 'decor-organization', 'name' => 'Woven Storage Basket Set', 'slug' => 'woven-storage-basket-set', 'regular' => '54', 'sale' => '46', 'stock' => 'outofstock'),
    array('category' => 'decor-organization', 'name' => 'Floating Wall Shelf Set, 3 Piece', 'slug' => 'floating-wall-shelf-set-3-piece', 'regular' => '62'),
);

function aspect_seed_throw_on_error($value) {
    if (is_wp_error($value)) {
        throw new RuntimeException($value->get_error_message());
    }
    return $value;
}

function aspect_seed_attachment_map() {
    $map = array();
    $ids = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    foreach ($ids as $id) {
        $file = get_post_meta($id, '_wp_attached_file', true);
        if ($file) {
            $map[basename($file)] = (int) $id;
        }
    }
    return $map;
}

global $wpdb;
$wpdb->query('START TRANSACTION');

try {
    $attachment_map = aspect_seed_attachment_map();
    $category_ids = array();
    $desired_term_ids = array();

    foreach ($categories as $position => $category) {
        $existing = term_exists($category['slug'], 'product_cat');
        if ($existing) {
            $term_id = (int) (is_array($existing) ? $existing['term_id'] : $existing);
            aspect_seed_throw_on_error(wp_update_term($term_id, 'product_cat', array(
                'name' => $category['name'],
                'slug' => $category['slug'],
                'parent' => 0,
                'description' => 'Shop ' . $category['name'] . ' at Aspect Trading.',
            )));
        } else {
            $created = aspect_seed_throw_on_error(wp_insert_term($category['name'], 'product_cat', array(
                'slug' => $category['slug'],
                'description' => 'Shop ' . $category['name'] . ' at Aspect Trading.',
            )));
            $term_id = (int) $created['term_id'];
        }

        $category_ids[$category['slug']] = $term_id;
        $desired_term_ids[] = $term_id;
        update_term_meta($term_id, 'order', $position);
        if (isset($attachment_map[$category['image']])) {
            update_term_meta($term_id, 'thumbnail_id', $attachment_map[$category['image']]);
        }
    }

    update_option('default_product_cat', $category_ids['kitchenware']);

    foreach (get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false)) as $term) {
        if (!in_array((int) $term->term_id, $desired_term_ids, true)) {
            aspect_seed_throw_on_error(wp_delete_term($term->term_id, 'product_cat'));
        }
    }

    $tag = term_exists('development-demo', 'product_tag');
    $tag_id = $tag ? (int) (is_array($tag) ? $tag['term_id'] : $tag) : 0;

    $old_meta_ids = get_posts(array(
        'post_type' => 'product',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => '_aspect_demo_catalogue',
        'meta_value' => '1',
    ));
    $old_tag_ids = $tag_id ? get_posts(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => array(array('taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => array($tag_id))),
    )) : array();
    $archived = 0;
    $archived_variations = 0;
    foreach (array_unique(array_merge($old_meta_ids, $old_tag_ids)) as $product_id) {
        if ($seed_version !== get_post_meta($product_id, '_aspect_client_catalogue_seed', true)) {
            if ('draft' !== get_post_status($product_id)) {
                aspect_seed_throw_on_error(wp_update_post(array('ID' => $product_id, 'post_status' => 'draft'), true));
                ++$archived;
            }
            foreach (get_posts(array('post_type' => 'product_variation', 'post_status' => array('publish', 'draft', 'private'), 'post_parent' => $product_id, 'posts_per_page' => -1, 'fields' => 'ids')) as $variation_id) {
                if ('draft' !== get_post_status($variation_id)) {
                    aspect_seed_throw_on_error(wp_update_post(array('ID' => $variation_id, 'post_status' => 'draft'), true));
                    ++$archived_variations;
                }
            }
        }
    }

    $brand_ids = array();
    if (taxonomy_exists('product_brand')) {
        foreach (array('Demo Brand 01', 'Demo Brand 02', 'Demo Brand 03', 'Demo Brand 04') as $brand_name) {
            $brand = term_exists(sanitize_title($brand_name), 'product_brand');
            if (!$brand) {
                $brand = aspect_seed_throw_on_error(wp_insert_term($brand_name, 'product_brand', array('slug' => sanitize_title($brand_name))));
            }
            $brand_ids[] = (int) (is_array($brand) ? $brand['term_id'] : $brand);
        }
    }

    $created_count = 0;
    $updated_count = 0;
    $deleted_duplicate_variations = 0;
    $variation_check = array();
    foreach ($products as $index => $spec) {
        $type = isset($spec['type']) ? $spec['type'] : 'simple';
        $existing_post = get_page_by_path($spec['slug'], OBJECT, 'product');
        $product = $existing_post ? wc_get_product($existing_post->ID) : false;

        if ($product && !$product->is_type($type)) {
            throw new RuntimeException('Existing product has an incompatible type: ' . $spec['slug']);
        }
        if (!$product) {
            $product = 'variable' === $type ? new WC_Product_Variable() : new WC_Product_Simple();
            ++$created_count;
        } else {
            ++$updated_count;
        }

        $category = null;
        foreach ($categories as $candidate) {
            if ($candidate['slug'] === $spec['category']) {
                $category = $candidate;
                break;
            }
        }
        if (!$category) {
            throw new RuntimeException('Product category was not defined: ' . $spec['category']);
        }

        $image_file = isset($spec['image']) ? $spec['image'] : $category['image'];
        $stock_status = isset($spec['stock']) ? $spec['stock'] : 'instock';
        $product->set_name($spec['name']);
        $product->set_slug($spec['slug']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_description('Explore the ' . $spec['name'] . ' in Aspect Trading\'s ' . $category['name'] . ' collection. Product specifications, warranty terms and delivery details must be confirmed before launch.');
        $product->set_short_description('Explore the ' . $spec['name'] . ' in Aspect Trading\'s ' . $category['name'] . ' collection.');
        $product->set_sku('AT-CLIENT-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT));
        $product->set_category_ids(array($category_ids[$spec['category']]));
        $product->set_tag_ids(array());
        $product->set_featured(0 === $index % 4);
        $product->set_stock_status($stock_status);
        if (isset($attachment_map[$image_file])) {
            $product->set_image_id($attachment_map[$image_file]);
        }
        $gallery_ids = array();
        foreach (isset($spec['gallery']) ? $spec['gallery'] : array() as $gallery_file) {
            if (isset($attachment_map[$gallery_file])) {
                $gallery_ids[] = $attachment_map[$gallery_file];
            }
        }
        $product->set_gallery_image_ids($gallery_ids);

        if ('variable' === $type) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name('Size');
            $attribute->set_options($spec['options']);
            $attribute->set_position(0);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $product->set_attributes(array($attribute));
            $product->set_manage_stock(false);
        } else {
            $product->set_regular_price($spec['regular']);
            $product->set_sale_price(isset($spec['sale']) ? $spec['sale'] : '');
            $product->set_manage_stock(true);
            $product->set_stock_quantity('outofstock' === $stock_status ? 0 : 25);
        }

        $product_id = $product->save();
        delete_post_meta($product_id, '_aspect_demo_catalogue');
        update_post_meta($product_id, '_aspect_client_catalogue_seed', $seed_version);
        wp_set_object_terms($product_id, $type, 'product_type', false);
        if ($brand_ids) {
            wp_set_object_terms($product_id, array($brand_ids[$index % count($brand_ids)]), 'product_brand', false);
        }

        if ('variable' === $type) {
            $child_ids = get_posts(array(
                'post_type' => 'product_variation',
                'post_status' => array('publish', 'draft', 'private'),
                'post_parent' => $product_id,
                'posts_per_page' => -1,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
            ));
            $kept_variation_ids = array();
            $remaining_child_ids = $child_ids;
            foreach ($spec['options'] as $option_index => $option) {
                $option_value = sanitize_title($option);
                $matched_variation_id = 0;
                foreach ($remaining_child_ids as $candidate_id) {
                    $stored_option = (string) get_post_meta($candidate_id, '_aspect_client_variation_option', true);
                    $stored_attribute = (string) get_post_meta($candidate_id, 'attribute_size', true);
                    if (0 === strcasecmp($stored_option, $option) || $option_value === sanitize_title($stored_attribute)) {
                        $matched_variation_id = (int) $candidate_id;
                        break;
                    }
                }
                $variation = $matched_variation_id ? new WC_Product_Variation($matched_variation_id) : new WC_Product_Variation();
                $variation->set_parent_id($product_id);
                $variation->set_status('publish');
                $variation->set_menu_order($option_index);
                $variation->set_attributes(array('size' => $option_value));
                $variation->set_regular_price($spec['regular']);
                $variation->set_sale_price(isset($spec['sale']) ? $spec['sale'] : '');
                $variation->set_manage_stock(true);
                $variation->set_stock_quantity(12);
                $variation->set_stock_status('instock');
                $variation_id = $variation->save();
                update_post_meta($variation_id, 'attribute_size', $option_value);
                update_post_meta($variation_id, '_aspect_client_variation_option', $option);
                clean_post_cache($variation_id);
                $kept_variation_ids[] = $variation_id;
                $remaining_child_ids = array_values(array_diff($remaining_child_ids, array($variation_id)));
            }
            foreach ($remaining_child_ids as $extra_variation_id) {
                if (!wp_delete_post($extra_variation_id, true)) {
                    throw new RuntimeException('Could not remove a duplicate demo variation: ' . $extra_variation_id);
                }
                ++$deleted_duplicate_variations;
            }
            WC_Product_Variable::sync($product_id);
            foreach ($kept_variation_ids as $option_index => $variation_id) {
                $wpdb->delete(
                    $wpdb->postmeta,
                    array('post_id' => $variation_id, 'meta_key' => 'attribute_size'),
                    array('%d', '%s')
                );
                $attribute_insert = $wpdb->insert(
                    $wpdb->postmeta,
                    array('post_id' => $variation_id, 'meta_key' => 'attribute_size', 'meta_value' => $spec['options'][$option_index]),
                    array('%d', '%s', '%s')
                );
                if (false === $attribute_insert) {
                    throw new RuntimeException('Could not save variation size: ' . $wpdb->last_error);
                }
                wp_cache_delete($variation_id, 'post_meta');
                clean_post_cache($variation_id);
                $variation_check[$variation_id] = array(
                    'intended' => $spec['options'][$option_index],
                    'stored' => get_post_meta($variation_id, 'attribute_size', true),
                    'raw' => $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'attribute_size' LIMIT 1", $variation_id)),
                );
            }
        }
    }

    $wpdb->query('COMMIT');
    clean_term_cache($desired_term_ids, 'product_cat');
    wc_delete_product_transients();
    flush_rewrite_rules(false);

    $result_categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'menu_order', 'order' => 'ASC'));
    $result_products = wc_get_products(array('status' => 'publish', 'limit' => -1, 'return' => 'ids'));
    echo wp_json_encode(array(
        'seed' => $seed_version,
        'archived_products' => $archived,
        'archived_variations' => $archived_variations,
        'created_products' => $created_count,
        'updated_products' => $updated_count,
        'deleted_duplicate_variations' => $deleted_duplicate_variations,
        'published_products' => count($result_products),
        'published_variations' => (int) wp_count_posts('product_variation')->publish,
        'variation_check' => $variation_check,
        'categories' => wp_list_pluck($result_categories, 'name'),
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $error) {
    $wpdb->query('ROLLBACK');
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
'@

$previousRoot = $env:ASPECT_TRADING_WP_ROOT
$env:ASPECT_TRADING_WP_ROOT = $WordPressRoot
$tempRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$tempScript = Join-Path $tempRoot ("aspect-trading-catalogue-{0}.php" -f [guid]::NewGuid().ToString('N'))

if (-not $tempScript.StartsWith($tempRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'The temporary catalogue script path resolved outside the system temp directory.'
}

try {
    [System.IO.File]::WriteAllText($tempScript, "<?php`n$php", [System.Text.UTF8Encoding]::new($false))
    if ($ValidateOnly) {
        & $PhpPath -l $tempScript
        if ($LASTEXITCODE -ne 0) {
            throw "Catalogue script validation failed with exit code $LASTEXITCODE"
        }
        return
    }
    & $PhpPath $tempScript
    if ($LASTEXITCODE -ne 0) {
        throw "Catalogue rebuild failed with exit code $LASTEXITCODE"
    }
} finally {
    $env:ASPECT_TRADING_WP_ROOT = $previousRoot
    if (Test-Path -LiteralPath $tempScript -PathType Leaf) {
        Remove-Item -LiteralPath $tempScript -Force
    }
}
