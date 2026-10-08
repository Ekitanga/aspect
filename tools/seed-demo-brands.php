<?php

if ( ! defined( 'ASPECT_TRADING_ALLOW_LEGACY_DEMO_SEED' ) || true !== ASPECT_TRADING_ALLOW_LEGACY_DEMO_SEED ) {
	throw new RuntimeException( 'This retired demo brand seeder is locked. Use the current catalogue rebuild workflow instead.' );
}

if ( ! taxonomy_exists( 'product_brand' ) ) {
	echo "product_brand taxonomy is unavailable; no brand terms were created.\n";
	return;
}

$brand_names = array( 'Demo Brand 01', 'Demo Brand 02', 'Demo Brand 03', 'Demo Brand 04' );
$brand_ids = array();
foreach ( $brand_names as $brand_name ) {
	$brand_slug = sanitize_title( $brand_name );
	$brand = term_exists( $brand_slug, 'product_brand' );
	if ( ! $brand ) {
		$brand = wp_insert_term( $brand_name, 'product_brand', array( 'slug' => $brand_slug ) );
	}
	if ( is_wp_error( $brand ) ) {
		throw new RuntimeException( $brand->get_error_message() );
	}
	$brand_ids[] = (int) ( is_array( $brand ) ? $brand['term_id'] : $brand );
}

$product_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_aspect_demo_catalogue',
		'meta_value'     => '1',
	)
);

foreach ( $product_ids as $index => $product_id ) {
	wp_set_object_terms( $product_id, array( $brand_ids[ $index % count( $brand_ids ) ] ), 'product_brand', false );
	if ( 0 === $index % 3 ) {
		$product = wc_get_product( $product_id );
		if ( $product && ! $product->is_type( 'variable' ) ) {
			$product->set_featured( true );
			$product->save();
		}
	}
}

echo 'development_brands=' . count( $brand_ids ) . "\n";
echo 'demo_products_with_brand=' . count( $product_ids ) . "\n";
