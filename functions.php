<?php
/**
 * Aspect Trading child-theme branding defaults.
 */

function aspect_trading_branding_setup() {
	add_theme_support( 'custom-logo', array(
		'height'      => 72,
		'width'       => 288,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'aspect_trading_branding_setup', 20 );

function aspect_trading_branding_assets() {
	$theme_version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style(
		'aspect-trading-branding',
		get_stylesheet_directory_uri() . '/assets/css/branding.css',
		array(),
		$theme_version
	);
	wp_enqueue_style(
		'aspect-trading-marketplace',
		get_stylesheet_directory_uri() . '/assets/css/marketplace.css',
		array( 'aspect-trading-branding' ),
		$theme_version
	);
}
add_action( 'wp_enqueue_scripts', 'aspect_trading_branding_assets', 20 );

require_once get_stylesheet_directory() . '/inc/marketplace.php';

function aspect_trading_elessi_logo_defaults() {
	if ( ! function_exists( 'elessi_logo' ) ) {
		return;
	}

	global $nasa_opt;
	if ( ! is_array( $nasa_opt ) ) {
		$nasa_opt = array();
	}

	$branding_url = get_stylesheet_directory_uri() . '/assets/images/branding/';
	if ( empty( $nasa_opt['site_logo'] ) ) {
		$nasa_opt['site_logo'] = $branding_url . 'aspect-trading-logo.svg';
	}
	if ( empty( $nasa_opt['site_logo_m'] ) ) {
		$nasa_opt['site_logo_m'] = $branding_url . 'aspect-trading-logo-mobile.svg';
	}
}
add_action( 'wp', 'aspect_trading_elessi_logo_defaults', 1 );

function aspect_trading_default_custom_logo( $html, $blog_id ) {
	if ( '' !== $html || is_admin() ) {
		return $html;
	}

	$logo_url = get_stylesheet_directory_uri() . '/assets/images/branding/aspect-trading-logo.svg';
	$site_name = esc_attr( get_bloginfo( 'name' ) ?: 'Aspect Trading' );

	return sprintf(
		'<a href="%1$s" class="custom-logo-link aspect-trading-default-logo" rel="home" aria-label="%2$s"><img src="%3$s" class="custom-logo" alt="%2$s" width="288" height="72" decoding="async"></a>',
		esc_url( home_url( '/' ) ),
		$site_name,
		esc_url( $logo_url )
	);
}
add_filter( 'get_custom_logo', 'aspect_trading_default_custom_logo', 10, 2 );

function aspect_trading_default_site_icon( $url, $size, $blog_id ) {
	if ( '' !== $url ) {
		return $url;
	}

	return get_stylesheet_directory_uri() . '/assets/images/branding/aspect-trading-favicon.svg';
}
add_filter( 'get_site_icon_url', 'aspect_trading_default_site_icon', 10, 3 );

function aspect_trading_disable_fake_viewer_count() {
	if ( function_exists( 'nasa_fake_view' ) ) {
		remove_action( 'woocommerce_single_product_summary', 'nasa_fake_view', 35 );
	}
}
add_action( 'wp', 'aspect_trading_disable_fake_viewer_count', 20 );