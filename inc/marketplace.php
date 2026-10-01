<?php
/**
 * Dynamic marketplace helpers for the Aspect Trading child theme.
 */

function aspect_trading_brand_logo_url( $mobile = false ) {
	$custom_logo_id = get_theme_mod( 'custom_logo' );
	if ( $custom_logo_id ) {
		$custom_logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
		if ( $custom_logo_url ) {
			return $custom_logo_url;
		}
	}

	global $nasa_opt;
	$nasa_logo_key = $mobile ? 'site_logo_m' : 'site_logo';
	if ( ! empty( $nasa_opt[ $nasa_logo_key ] ) ) {
		return $nasa_opt[ $nasa_logo_key ];
	}

	$filename = $mobile ? 'aspect-trading-logo-mobile.svg' : 'aspect-trading-logo.svg';
	return get_stylesheet_directory_uri() . '/assets/images/branding/' . $filename;
}

function aspect_trading_product_categories( $parent = 0 ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => $parent,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);

	return is_wp_error( $categories ) ? array() : $categories;
}

function aspect_trading_render_category_tree( $categories, $depth = 0 ) {
	if ( empty( $categories ) || $depth > 3 ) {
		return;
	}

	foreach ( $categories as $category ) {
		$children = aspect_trading_product_categories( $category->term_id );
		$term_link = get_term_link( $category );
		if ( is_wp_error( $term_link ) ) {
			continue;
		}
		?>
		<li class="aspect-category-tree__item">
			<a href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $category->name ); ?></a>
			<?php if ( $children ) : ?>
				<details class="aspect-category-tree__children">
					<summary><?php echo esc_html( sprintf( __( 'Browse %s', 'aspect-trading' ), $category->name ) ); ?></summary>
					<ul><?php aspect_trading_render_category_tree( $children, $depth + 1 ); ?></ul>
				</details>
			<?php endif; ?>
		</li>
		<?php
	}
}

function aspect_trading_render_product_card( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$product_link = get_permalink( $product->get_id() );
	$image_id = $product->get_image_id();
	$brand_terms = taxonomy_exists( 'product_brand' ) ? get_the_terms( $product->get_id(), 'product_brand' ) : false;
	$brand = is_array( $brand_terms ) ? reset( $brand_terms ) : false;
	$regular_price = (float) $product->get_regular_price();
	$current_price = (float) $product->get_price();
	$discount = $product->is_on_sale() && $regular_price > 0 ? (int) round( ( 1 - ( $current_price / $regular_price ) ) * 100 ) : 0;
	?>
	<article class="aspect-product">
		<a class="aspect-product__image" href="<?php echo esc_url( $product_link ); ?>">
			<?php
			if ( $image_id ) {
				echo wp_get_attachment_image( $image_id, 'woocommerce_thumbnail' );
			} else {
				echo wp_kses_post( wc_placeholder_img( 'woocommerce_thumbnail' ) );
			}
			?>
			<?php if ( ! $product->is_in_stock() ) : ?>
				<span class="aspect-product__badge aspect-product__badge--muted"><?php esc_html_e( 'Out of stock', 'aspect-trading' ); ?></span>
			<?php elseif ( $discount > 0 ) : ?>
				<span class="aspect-product__badge"><?php echo esc_html( sprintf( __( '%d%% off', 'aspect-trading' ), $discount ) ); ?></span>
			<?php endif; ?>
		</a>
		<div class="aspect-product__body">
			<?php if ( $brand && ! is_wp_error( $brand ) ) : ?><p class="aspect-product__brand"><?php echo esc_html( $brand->name ); ?></p><?php endif; ?>
			<?php if ( wc_review_ratings_enabled() && $product->get_rating_count() ) : ?>
				<div class="aspect-product__rating" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %1$s out of 5 from %2$s reviews', 'aspect-trading' ), $product->get_average_rating(), $product->get_rating_count() ) ); ?>">
					<span aria-hidden="true">★</span> <?php echo esc_html( $product->get_average_rating() ); ?>
					<span class="aspect-product__review-count">(<?php echo esc_html( $product->get_rating_count() ); ?>)</span>
				</div>
			<?php endif; ?>
			<h3 class="aspect-product__title"><a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
			<div class="aspect-product__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			<?php if ( $product->is_purchasable() && $product->is_in_stock() && $product->is_type( 'simple' ) ) : ?>
				<a class="aspect-product__add add_to_cart_button ajax_add_to_cart product_type_simple" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>" rel="nofollow"><?php esc_html_e( 'Add to cart', 'aspect-trading' ); ?></a>
			<?php else : ?>
				<a class="aspect-product__add" href="<?php echo esc_url( $product_link ); ?>"><?php esc_html_e( 'View details', 'aspect-trading' ); ?></a>
			<?php endif; ?>
			<?php if ( shortcode_exists( 'yith_wcwl_add_to_wishlist' ) ) : ?>
				<div class="aspect-product__wishlist"><?php echo do_shortcode( '[yith_wcwl_add_to_wishlist product_id="' . absint( $product->get_id() ) . '"]' ); ?></div>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

function aspect_trading_render_product_section( $title, $products, $link = '', $eyebrow = '' ) {
	if ( empty( $products ) ) {
		return;
	}
	?>
	<section class="aspect-section" aria-label="<?php echo esc_attr( $title ); ?>">
		<div class="aspect-section__heading">
			<div>
				<?php if ( $eyebrow ) : ?><p class="aspect-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<h2><?php echo esc_html( $title ); ?></h2>
			</div>
			<?php if ( $link ) : ?><a class="aspect-text-link" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'View all', 'aspect-trading' ); ?> <span aria-hidden="true">&#8594;</span></a><?php endif; ?>
		</div>
		<div class="aspect-product-grid">
			<?php foreach ( $products as $product ) : ?>
				<?php aspect_trading_render_product_card( $product ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}
