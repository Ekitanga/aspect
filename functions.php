<?php
/**
 * Aspect Trading child-theme branding defaults.
 */

function aspect_trading_branding_setup() {
	add_theme_support( 'title-tag' );
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
	$branding_path = get_stylesheet_directory() . '/assets/css/branding.css';
	$marketplace_path = get_stylesheet_directory() . '/assets/css/marketplace.css';
	wp_enqueue_style(
		'aspect-trading-branding',
		get_stylesheet_directory_uri() . '/assets/css/branding.css',
		array(),
		is_file( $branding_path ) ? (string) filemtime( $branding_path ) : $theme_version
	);
	wp_enqueue_style(
		'aspect-trading-marketplace',
		get_stylesheet_directory_uri() . '/assets/css/marketplace.css',
		array( 'aspect-trading-branding' ),
		is_file( $marketplace_path ) ? (string) filemtime( $marketplace_path ) : $theme_version
	);

	if ( is_front_page() ) {
		$slider_path = get_stylesheet_directory() . '/assets/js/hero-slider.js';
		wp_enqueue_script(
			'aspect-trading-hero-slider',
			get_stylesheet_directory_uri() . '/assets/js/hero-slider.js',
			array(),
			is_file( $slider_path ) ? (string) filemtime( $slider_path ) : $theme_version,
			true
		);
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		$gallery_path = get_stylesheet_directory() . '/assets/js/product-gallery.js';
		wp_enqueue_script(
			'aspect-trading-product-gallery',
			get_stylesheet_directory_uri() . '/assets/js/product-gallery.js',
			array( 'wc-single-product' ),
			is_file( $gallery_path ) ? (string) filemtime( $gallery_path ) : $theme_version,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'aspect_trading_branding_assets', 20 );

require_once get_stylesheet_directory() . '/inc/marketplace.php';

/**
 * Add a clear route back to the catalogue in the Cart block's empty state.
 * WooCommerce's saved cart template on this site predates the built-in CTA.
 */
function aspect_trading_empty_cart_cta( $block_content ) {
	if ( false !== strpos( $block_content, 'aspect-empty-cart__actions' ) ) {
		return $block_content;
	}

	$title_position = strpos( $block_content, 'wc-block-cart__empty-cart__title' );
	if ( false === $title_position ) {
		return $block_content;
	}

	$title_end = strpos( $block_content, '</h2>', $title_position );
	if ( false === $title_end ) {
		return $block_content;
	}

	$title_end += strlen( '</h2>' );
	$actions = sprintf(
		'<p class="aspect-empty-cart__actions"><a class="aspect-empty-cart__cta" href="%1$s">%2$s</a></p>',
		esc_url( wc_get_page_permalink( 'shop' ) ),
		esc_html__( 'Continue shopping', 'aspect-trading' )
	);

	return substr_replace( $block_content, $actions, $title_end, 0 );
}
add_filter( 'render_block_woocommerce/empty-cart-block', 'aspect_trading_empty_cart_cta', 20 );

/**
 * Enable catalogue wishlist controls when YITH is installed, without replacing
 * settings that a store administrator has already saved.
 */
function aspect_trading_wishlist_defaults() {
	if ( ! function_exists( 'YITH_WCWL' ) ) {
		return;
	}

	$missing = '__aspect_trading_missing_option__';
	if ( $missing === get_option( 'yith_wcwl_show_on_loop', $missing ) ) {
		add_option( 'yith_wcwl_show_on_loop', 'yes' );
	}
	if ( $missing === get_option( 'yith_wcwl_loop_position', $missing ) ) {
		add_option( 'yith_wcwl_loop_position', 'after_add_to_cart' );
	}
}
add_action( 'init', 'aspect_trading_wishlist_defaults', 30 );

/**
 * Start YITH's functional guest session before headers are sent. Version 4.18
 * hydrates its wishlist controls through the lists REST endpoint, which only
 * accepts signed-in customers or guests with this plugin-owned session.
 */
function aspect_trading_start_guest_wishlist_session() {
	if ( is_admin() || is_user_logged_in() || wp_doing_ajax() || ! function_exists( 'YITH_WCWL_Session' ) ) {
		return;
	}

	$session = YITH_WCWL_Session();
	if ( $session && ! $session->has_session() ) {
		$session->get_session_id();
	}
}
add_action( 'template_redirect', 'aspect_trading_start_guest_wishlist_session', 1 );

function aspect_trading_elessi_logo_defaults() {
	if ( ! function_exists( 'elessi_logo' ) ) {
		return;
	}

	global $nasa_opt;
	if ( ! is_array( $nasa_opt ) ) {
		$nasa_opt = array();
	}

	$branding_url = get_stylesheet_directory_uri() . '/assets/images/branding/';
	$desktop_logo_path = get_stylesheet_directory() . '/assets/images/branding/aspect-trading-logo.svg';
	$mobile_logo_path = get_stylesheet_directory() . '/assets/images/branding/aspect-trading-logo-mobile.svg';
	if ( empty( $nasa_opt['site_logo'] ) ) {
		$nasa_opt['site_logo'] = add_query_arg( 'ver', is_file( $desktop_logo_path ) ? (string) filemtime( $desktop_logo_path ) : wp_get_theme()->get( 'Version' ), $branding_url . 'aspect-trading-logo.svg' );
	}
	if ( empty( $nasa_opt['site_logo_m'] ) ) {
		$nasa_opt['site_logo_m'] = add_query_arg( 'ver', is_file( $mobile_logo_path ) ? (string) filemtime( $mobile_logo_path ) : wp_get_theme()->get( 'Version' ), $branding_url . 'aspect-trading-logo-mobile.svg' );
	}
}
add_action( 'wp', 'aspect_trading_elessi_logo_defaults', 1 );

function aspect_trading_default_custom_logo( $html, $blog_id ) {
	if ( '' !== $html || is_admin() ) {
		return $html;
	}

	$logo_path = get_stylesheet_directory() . '/assets/images/branding/aspect-trading-logo.svg';
	$logo_url = get_stylesheet_directory_uri() . '/assets/images/branding/aspect-trading-logo.svg';
	if ( is_file( $logo_path ) ) {
		$logo_url = add_query_arg( 'ver', (string) filemtime( $logo_path ), $logo_url );
	}
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

	$icon_path = get_stylesheet_directory() . '/assets/images/branding/aspect-trading-favicon.svg';
	$icon_url = get_stylesheet_directory_uri() . '/assets/images/branding/aspect-trading-favicon.svg';

	return is_file( $icon_path ) ? add_query_arg( 'ver', (string) filemtime( $icon_path ), $icon_url ) : $icon_url;
}
add_filter( 'get_site_icon_url', 'aspect_trading_default_site_icon', 10, 3 );

function aspect_trading_disable_fake_viewer_count() {
	if ( function_exists( 'nasa_fake_view' ) ) {
		remove_action( 'woocommerce_single_product_summary', 'nasa_fake_view', 35 );
	}
}
add_action( 'wp', 'aspect_trading_disable_fake_viewer_count', 20 );

/**
 * Keep Click & Collect visible while removing the paid delivery rate whenever
 * a customer's basket qualifies for the configured free-delivery threshold.
 */
function aspect_trading_prefer_free_shipping( $rates ) {
	$has_free_shipping = false;
	foreach ( $rates as $rate ) {
		if ( 'free_shipping' === $rate->method_id ) {
			$has_free_shipping = true;
			break;
		}
	}

	if ( ! $has_free_shipping ) {
		return $rates;
	}

	foreach ( $rates as $rate_id => $rate ) {
		if ( 'flat_rate' === $rate->method_id ) {
			unset( $rates[ $rate_id ] );
		}
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'aspect_trading_prefer_free_shipping', 20 );

/**
 * Collect the delivery details Kenyan couriers need and use locally familiar
 * field labels in both the Checkout block and classic WooCommerce checkout.
 */
function aspect_trading_kenya_address_locale( $locale ) {
	if ( empty( $locale['KE'] ) ) {
		$locale['KE'] = array();
	}

	$locale['KE']['state'] = array_merge(
		$locale['KE']['state'] ?? array(),
		array(
			'label'    => __( 'County', 'aspect-trading' ),
			'required' => true,
		)
	);
	$locale['KE']['postcode'] = array_merge(
		$locale['KE']['postcode'] ?? array(),
		array(
			'label'    => __( 'Postal code', 'aspect-trading' ),
			'required' => true,
		)
	);
	$locale['KE']['phone'] = array_merge(
		$locale['KE']['phone'] ?? array(),
		array(
			'label'    => __( 'Phone number', 'aspect-trading' ),
			'required' => true,
		)
	);

	return $locale;
}
add_filter( 'woocommerce_get_country_locale', 'aspect_trading_kenya_address_locale', 20 );

function aspect_trading_checkout_fields( $fields ) {
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['label'] = __( 'Phone number', 'aspect-trading' );
		$fields['billing']['billing_phone']['required'] = true;
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'aspect_trading_checkout_fields', 20 );
