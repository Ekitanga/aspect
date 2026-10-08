<?php
/**
 * Aspect Trading responsive global header.
 *
 * @package Aspect_Trading
 */

$site_name = get_bloginfo( 'name' ) ?: 'Aspect Trading';
$logo_url = aspect_trading_brand_logo_url();
$mobile_logo_url = aspect_trading_brand_logo_url( true );
$categories = aspect_trading_product_categories();
$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$cart_count = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$wishlist_url = function_exists( 'YITH_WCWL' ) ? YITH_WCWL()->get_wishlist_url() : '';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="aspect-skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'aspect-trading' ); ?></a>
<div class="aspect-site-shell">
	<div class="aspect-utility-bar">
		<div class="aspect-container aspect-utility-bar__inner">
			<p><?php esc_html_e( 'A considered way to shop, every day.', 'aspect-trading' ); ?></p>
			<nav aria-label="<?php esc_attr_e( 'Customer links', 'aspect-trading' ); ?>">
				<?php foreach ( array( 'help-centre' => __( 'Help Centre', 'aspect-trading' ), 'track-order' => __( 'Track Order', 'aspect-trading' ) ) as $slug => $label ) : ?>
					<?php $page = get_page_by_path( $slug ); ?>
					<?php if ( $page ) : ?><a href="<?php echo esc_url( get_permalink( $page ) ); ?>"><?php echo esc_html( $label ); ?></a><?php endif; ?>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact', 'aspect-trading' ); ?></a>
			</nav>
		</div>
	</div>
	<header class="aspect-header">
		<div class="aspect-container aspect-header__main">
			<details class="aspect-mobile-menu">
				<summary aria-label="<?php esc_attr_e( 'Open categories menu', 'aspect-trading' ); ?>"><span class="aspect-menu-icon" aria-hidden="true"></span></summary>
				<div class="aspect-mobile-menu__panel">
					<div class="aspect-mobile-menu__title"><strong><?php esc_html_e( 'Shop categories', 'aspect-trading' ); ?></strong><span><?php esc_html_e( 'Choose a category to explore', 'aspect-trading' ); ?></span></div>
					<ul><?php aspect_trading_render_category_tree( $categories ); ?></ul>
					<a class="aspect-mobile-menu__shop" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop all products', 'aspect-trading' ); ?> <span aria-hidden="true">&#8594;</span></a>
				</div>
			</details>
			<a class="aspect-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $site_name ); ?>">
				<picture>
					<source media="(max-width: 767px)" srcset="<?php echo esc_url( $mobile_logo_url ); ?>">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" width="288" height="72" loading="eager" fetchpriority="high">
				</picture>
			</a>
			<form class="aspect-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="aspect-search-input"><?php esc_html_e( 'Search products', 'aspect-trading' ); ?></label>
				<input id="aspect-search-input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search products, brands and categories', 'aspect-trading' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
				<input type="hidden" name="post_type" value="product">
				<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'aspect-trading' ); ?>"><span aria-hidden="true"></span></button>
			</form>
			<nav class="aspect-header-actions" aria-label="<?php esc_attr_e( 'Your account, wishlist and cart', 'aspect-trading' ); ?>">
				<a class="aspect-header-action aspect-account-action" href="<?php echo esc_url( $account_url ); ?>"><span class="aspect-action-icon aspect-action-icon--account" aria-hidden="true"></span><span><?php esc_html_e( 'Account', 'aspect-trading' ); ?></span></a>
				<?php if ( $wishlist_url ) : ?>
					<a class="aspect-header-action aspect-wishlist-action" href="<?php echo esc_url( $wishlist_url ); ?>"><span class="aspect-action-icon aspect-action-icon--wishlist" aria-hidden="true"></span><span><?php esc_html_e( 'Wishlist', 'aspect-trading' ); ?></span></a>
				<?php endif; ?>
				<a class="aspect-header-action aspect-cart-action" href="<?php echo esc_url( $cart_url ); ?>"><span class="aspect-action-icon aspect-action-icon--cart" aria-hidden="true"></span><span><?php esc_html_e( 'Cart', 'aspect-trading' ); ?></span><span class="aspect-cart-count" aria-label="<?php echo esc_attr( sprintf( __( '%d items in cart', 'aspect-trading' ), $cart_count ) ); ?>"><?php echo esc_html( $cart_count ); ?></span></a>
			</nav>
		</div>
		<form class="aspect-search aspect-search--mobile" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="aspect-mobile-search-input"><?php esc_html_e( 'Search products', 'aspect-trading' ); ?></label>
			<input id="aspect-mobile-search-input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search products, brands and categories', 'aspect-trading' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
			<input type="hidden" name="post_type" value="product">
			<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'aspect-trading' ); ?>"><span aria-hidden="true"></span></button>
		</form>
	</header>
	<nav class="aspect-category-nav" aria-label="<?php esc_attr_e( 'Shop categories', 'aspect-trading' ); ?>">
		<div class="aspect-container aspect-category-nav__inner">
			<a class="aspect-all-categories" href="<?php echo esc_url( $shop_url ); ?>"><span aria-hidden="true">&#9776;</span><?php esc_html_e( 'All categories', 'aspect-trading' ); ?></a>
			<ul class="aspect-category-list">
				<?php foreach ( array_slice( $categories, 0, 7 ) as $category ) : ?>
					<?php $term_link = get_term_link( $category ); if ( is_wp_error( $term_link ) ) { continue; } $children = aspect_trading_product_categories( $category->term_id ); ?>
					<li class="aspect-category-item"><a href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $category->name ); ?></a>
						<?php if ( $children ) : ?>
							<details class="aspect-mega-menu"><summary aria-label="<?php echo esc_attr( sprintf( __( 'Browse %s subcategories', 'aspect-trading' ), $category->name ) ); ?>"><span aria-hidden="true">&#8964;</span></summary>
								<div class="aspect-mega-menu__panel"><a class="aspect-mega-menu__all" href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( sprintf( __( 'Shop all %s', 'aspect-trading' ), $category->name ) ); ?> <span aria-hidden="true">&#8594;</span></a><ul><?php foreach ( $children as $child ) : $child_link = get_term_link( $child ); if ( ! is_wp_error( $child_link ) ) : ?><li><a href="<?php echo esc_url( $child_link ); ?>"><?php echo esc_html( $child->name ); ?></a></li><?php endif; endforeach; ?></ul></div>
							</details>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<a class="aspect-sell-link" href="<?php echo esc_url( home_url( '/sell-with-us/' ) ); ?>"><?php esc_html_e( 'Sell with us', 'aspect-trading' ); ?></a>
		</div>
	</nav>
