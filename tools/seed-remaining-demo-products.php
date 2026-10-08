<?php

if ( ! defined( 'ASPECT_TRADING_ALLOW_LEGACY_DEMO_SEED' ) || true !== ASPECT_TRADING_ALLOW_LEGACY_DEMO_SEED ) {
	throw new RuntimeException( 'This retired demo seeder is locked. Use rebuild-client-catalogue.ps1 for the current client taxonomy.' );
}

$category_map = array();
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $category ) {
	$category_map[ $category->slug ] = (int) $category->term_id;
}

$demo_tag = term_exists( 'development-demo', 'product_tag' );
if ( ! $demo_tag ) {
	$demo_tag = wp_insert_term( 'Development Demo', 'product_tag', array( 'slug' => 'development-demo' ) );
}
if ( is_wp_error( $demo_tag ) ) {
	throw new RuntimeException( $demo_tag->get_error_message() );
}
$demo_tag_id = (int) ( is_array( $demo_tag ) ? $demo_tag['term_id'] : $demo_tag );

$products = array(
	array( 'Folding Laptop Stand', 'computing', '32.00', '27.00', 'simple' ),
	array( 'Smartwatch Charging Cable', 'phones-tablets', '14.00', '', 'simple' ),
	array( 'Dual-Band Wi-Fi Router', 'computing', '89.00', '74.00', 'simple' ),
	array( 'Ceramic Tableware Set', 'kitchen-dining', '64.00', '', 'simple' ),
	array( 'Softshell Travel Organizer', 'fashion', '23.00', '19.00', 'simple' ),
	array( 'Rechargeable Desk Fan', 'small-appliances', '37.00', '', 'simple' ),
	array( 'Compact USB Microphone', 'audio', '58.00', '49.00', 'simple' ),
	array( 'All-Weather Vehicle Organizer', 'car-accessories', '29.00', '', 'simple' ),
	array( 'Everyday Cotton Polo Shirt', 'men', '28.00', '', 'variable' ),
	array( 'Lightweight Training Jacket', 'sports-fitness', '86.00', '74.00', 'variable' ),
);

$created = 0;
foreach ( $products as $index => $data ) {
	list( $name, $category_slug, $regular_price, $sale_price, $type ) = $data;
	$slug = sanitize_title( $name );
	$existing = get_page_by_path( $slug, OBJECT, 'product' );
	if ( $existing ) {
		continue;
	}

	$product = 'variable' === $type ? new WC_Product_Variable() : new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_description( 'Development demo catalogue item for interface testing. Review all details and replace this copy before launch.' );
	$product->set_short_description( 'Development demo item. Replace before launch.' );
	$product->set_sku( 'AT-DEMO-' . str_pad( (string) ( 100 + $index ), 3, '0', STR_PAD_LEFT ) );
	$product->set_regular_price( $regular_price );
	if ( '' !== $sale_price ) {
		$product->set_sale_price( $sale_price );
	}
	if ( isset( $category_map[ $category_slug ] ) ) {
		$product->set_category_ids( array( $category_map[ $category_slug ] ) );
	}
	$product->set_tag_ids( array( $demo_tag_id ) );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( 30 );
	$product->set_stock_status( 'instock' );

	if ( 'variable' === $type ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M', 'L' ) );
		$attribute->set_position( 0 );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$product->set_attributes( array( $attribute ) );
	}

	$product_id = $product->save();
	update_post_meta( $product_id, '_aspect_demo_catalogue', '1' );

	if ( 'variable' === $type ) {
		wp_set_object_terms( $product_id, 'variable', 'product_type' );
		foreach ( array( 'S', 'M', 'L' ) as $size ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_attributes( array( 'Size' => $size ) );
			$variation->set_regular_price( $regular_price );
			$variation->set_stock_status( 'instock' );
			$variation->save();
		}
	}

	++$created;
}

echo 'new_demo_products=' . $created . "\n";
echo 'all_product_count=' . wp_count_posts( 'product' )->publish . "\n";
echo 'demo_product_count=' . count( get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'meta_key' => '_aspect_demo_catalogue', 'meta_value' => '1', 'fields' => 'ids' ) ) ) . "\n";
