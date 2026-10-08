<?php
/**
 * Remove retired development wording from the current catalogue taxonomy.
 *
 * Run with ASPECT_TRADING_WP_ROOT set to the WordPress installation root.
 */

$root = getenv( 'ASPECT_TRADING_WP_ROOT' );
$loader = rtrim( str_replace( '\\', '/', (string) $root ), '/' ) . '/wp-load.php';

if ( ! is_file( $loader ) ) {
	fwrite( STDERR, "WordPress loader was not found.\n" );
	exit( 1 );
}

require $loader;

$updated = array();
$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
if ( is_wp_error( $categories ) ) {
	throw new RuntimeException( $categories->get_error_message() );
}

foreach ( $categories as $category ) {
	if ( false === stripos( $category->description, 'development' ) && false === stripos( $category->description, 'demo' ) ) {
		continue;
	}

	$result = wp_update_term(
		$category->term_id,
		'product_cat',
		array( 'description' => sprintf( 'Shop %s at Aspect Trading.', html_entity_decode( $category->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) )
	);
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}
	$updated[] = $category->slug;
}

$legacy_tag = get_term_by( 'slug', 'development-demo', 'product_tag' );
if ( $legacy_tag ) {
	wp_delete_term( $legacy_tag->term_id, 'product_tag' );
}

echo wp_json_encode( array( 'updated_categories' => $updated, 'legacy_tag_removed' => (bool) $legacy_tag ), JSON_PRETTY_PRINT ) . PHP_EOL;
