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
	$file_path = get_stylesheet_directory() . '/assets/images/branding/' . $filename;
	$logo_url = get_stylesheet_directory_uri() . '/assets/images/branding/' . $filename;

	return is_file( $file_path ) ? add_query_arg( 'ver', (string) filemtime( $file_path ), $logo_url ) : $logo_url;
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

function aspect_trading_apply_catalog_filters( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$meta_query = (array) $query->get( 'meta_query' );
	$stock = isset( $_GET['aspect_stock'] ) ? sanitize_key( wp_unslash( $_GET['aspect_stock'] ) ) : '';
	if ( in_array( $stock, array( 'instock', 'outofstock' ), true ) ) {
		$meta_query[] = array(
			'key'   => '_stock_status',
			'value' => $stock,
		);
	}
	if ( count( $meta_query ) > 1 ) {
		$meta_query['relation'] = 'AND';
	}
	if ( $meta_query ) {
		$query->set( 'meta_query', $meta_query );
	}

	$category_id = isset( $_GET['aspect_category'] ) ? absint( $_GET['aspect_category'] ) : 0;
	if ( $category_id && term_exists( $category_id, 'product_cat' ) ) {
		$tax_query = (array) $query->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => array( $category_id ),
			'include_children' => true,
		);
		$query->set( 'tax_query', $tax_query );
	}

	$brand_id = isset( $_GET['aspect_brand'] ) ? absint( $_GET['aspect_brand'] ) : 0;
	if ( $brand_id && taxonomy_exists( 'product_brand' ) && term_exists( $brand_id, 'product_brand' ) ) {
		$tax_query = (array) $query->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy' => 'product_brand',
			'field'    => 'term_id',
			'terms'    => array( $brand_id ),
		);
		$query->set( 'tax_query', $tax_query );
	}
}
add_action( 'woocommerce_product_query', 'aspect_trading_apply_catalog_filters', 20 );

function aspect_trading_render_catalog_filters() {
	$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 0, 'orderby' => 'name' ) );
	$brands = taxonomy_exists( 'product_brand' ) ? get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => true, 'number' => 0, 'orderby' => 'name' ) ) : array();
	if ( is_wp_error( $categories ) ) {
		$categories = array();
	}
	if ( is_wp_error( $brands ) ) {
		$brands = array();
	}
	$current_category = isset( $_GET['aspect_category'] ) ? absint( $_GET['aspect_category'] ) : ( is_product_category() ? get_queried_object_id() : 0 );
	$current_brand = isset( $_GET['aspect_brand'] ) ? absint( $_GET['aspect_brand'] ) : ( is_tax( 'product_brand' ) ? get_queried_object_id() : 0 );
	$current_minimum = isset( $_GET['min_price'] ) ? wc_format_decimal( wp_unslash( $_GET['min_price'] ) ) : '';
	$current_maximum = isset( $_GET['max_price'] ) ? wc_format_decimal( wp_unslash( $_GET['max_price'] ) ) : '';
	$current_stock = isset( $_GET['aspect_stock'] ) ? sanitize_key( wp_unslash( $_GET['aspect_stock'] ) ) : '';
	$current_rating = isset( $_GET['rating_filter'] ) ? absint( wp_unslash( $_GET['rating_filter'] ) ) : 0;
	$action = wc_get_page_permalink( 'shop' );
	$clear_url = is_product_taxonomy() ? get_term_link( get_queried_object() ) : $action;
	if ( is_wp_error( $clear_url ) ) {
		$clear_url = $action;
	}
	?>
	<details class="aspect-catalog-filters" <?php echo ( $current_category || $current_brand || '' !== $current_minimum || '' !== $current_maximum || $current_stock || $current_rating ) ? 'open' : ''; ?>>
		<summary><?php esc_html_e( 'Filter products', 'aspect-trading' ); ?></summary>
		<form class="aspect-catalog-filters__form" method="get" action="<?php echo esc_url( $action ); ?>">
			<?php if ( is_search() ) : ?><input type="hidden" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"><input type="hidden" name="post_type" value="product"><?php endif; ?>
			<?php if ( isset( $_GET['orderby'] ) ) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr( sanitize_key( wp_unslash( $_GET['orderby'] ) ) ); ?>"><?php endif; ?>
			<label><?php esc_html_e( 'Category', 'aspect-trading' ); ?>
				<select name="aspect_category"><option value=""><?php esc_html_e( 'All categories', 'aspect-trading' ); ?></option><?php foreach ( $categories as $category ) : ?><option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $current_category, (int) $category->term_id ); ?>><?php echo esc_html( $category->name ); ?></option><?php endforeach; ?></select>
			</label>
			<fieldset class="aspect-catalog-filters__price"><legend><?php esc_html_e( 'Price range', 'aspect-trading' ); ?></legend><label><span class="screen-reader-text"><?php esc_html_e( 'Minimum price', 'aspect-trading' ); ?></span><input type="number" min="0" step="0.01" name="min_price" value="<?php echo esc_attr( $current_minimum ); ?>" placeholder="<?php esc_attr_e( 'Min', 'aspect-trading' ); ?>"></label><label><span class="screen-reader-text"><?php esc_html_e( 'Maximum price', 'aspect-trading' ); ?></span><input type="number" min="0" step="0.01" name="max_price" value="<?php echo esc_attr( $current_maximum ); ?>" placeholder="<?php esc_attr_e( 'Max', 'aspect-trading' ); ?>"></label></fieldset>
			<label><?php esc_html_e( 'Availability', 'aspect-trading' ); ?><select name="aspect_stock"><option value=""><?php esc_html_e( 'Any stock status', 'aspect-trading' ); ?></option><option value="instock" <?php selected( $current_stock, 'instock' ); ?>><?php esc_html_e( 'In stock', 'aspect-trading' ); ?></option><option value="outofstock" <?php selected( $current_stock, 'outofstock' ); ?>><?php esc_html_e( 'Out of stock', 'aspect-trading' ); ?></option></select></label>
			<label><?php esc_html_e( 'Minimum rating', 'aspect-trading' ); ?><select name="rating_filter"><option value="0"><?php esc_html_e( 'Any rating', 'aspect-trading' ); ?></option><?php for ( $stars = 5; $stars >= 1; --$stars ) : ?><option value="<?php echo esc_attr( $stars ); ?>" <?php selected( $current_rating, $stars ); ?>><?php echo esc_html( sprintf( __( '%d stars and up', 'aspect-trading' ), $stars ) ); ?></option><?php endfor; ?></select></label>
			<?php if ( $brands ) : ?><label><?php esc_html_e( 'Brand', 'aspect-trading' ); ?><select name="aspect_brand"><option value=""><?php esc_html_e( 'All brands', 'aspect-trading' ); ?></option><?php foreach ( $brands as $brand ) : ?><option value="<?php echo esc_attr( $brand->term_id ); ?>" <?php selected( $current_brand, (int) $brand->term_id ); ?>><?php echo esc_html( $brand->name ); ?></option><?php endforeach; ?></select></label><?php endif; ?>
			<div class="aspect-catalog-filters__actions"><button type="submit"><?php esc_html_e( 'Apply filters', 'aspect-trading' ); ?></button><a href="<?php echo esc_url( $clear_url ); ?>"><?php esc_html_e( 'Clear', 'aspect-trading' ); ?></a></div>
		</form>
	</details>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'aspect_trading_render_catalog_filters', 5 );

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
