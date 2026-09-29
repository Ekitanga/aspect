<?php
/**
 * Dynamic marketplace homepage.
 *
 * @package Aspect_Trading
 */

get_header();
$categories = aspect_trading_product_categories();
$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>
<main id="main-content" class="aspect-home">
	<section class="aspect-hero aspect-container" aria-label="<?php esc_attr_e( 'Marketplace highlights', 'aspect-trading' ); ?>">
		<aside class="aspect-hero-categories">
			<div class="aspect-hero-categories__heading"><span class="aspect-eyebrow"><?php esc_html_e( 'Find your next favourite', 'aspect-trading' ); ?></span><h1><?php esc_html_e( 'Shop by category', 'aspect-trading' ); ?></h1></div>
			<ul>
				<?php foreach ( array_slice( $categories, 0, 8 ) as $category ) : ?>
					<?php $term_link = get_term_link( $category ); if ( is_wp_error( $term_link ) ) { continue; } ?>
					<li><a href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $category->name ); ?><span aria-hidden="true">&#8594;</span></a></li>
				<?php endforeach; ?>
				<?php if ( ! $categories ) : ?><li><a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Explore the shop', 'aspect-trading' ); ?><span aria-hidden="true">&#8594;</span></a></li><?php endif; ?>
			</ul>
		</aside>
		<div class="aspect-hero-feature">
			<div class="aspect-hero-feature__copy">
				<p class="aspect-eyebrow"><?php esc_html_e( 'Discover more, choose well', 'aspect-trading' ); ?></p>
				<h2><?php esc_html_e( 'Good finds. Everyday.', 'aspect-trading' ); ?></h2>
				<p><?php esc_html_e( 'Explore a growing collection of products selected for the way you live.', 'aspect-trading' ); ?></p>
				<a href="<?php echo esc_url( $shop_url ); ?>" class="aspect-button"><?php esc_html_e( 'Explore the shop', 'aspect-trading' ); ?><span aria-hidden="true">&#8594;</span></a>
			</div>
			<div class="aspect-hero-feature__art" aria-hidden="true"><span class="aspect-hero-feature__monogram">A</span><span class="aspect-hero-feature__orbit"></span></div>
			<div class="aspect-hero-feature__index"><span>01</span><span><?php esc_html_e( 'The Aspect edit', 'aspect-trading' ); ?></span></div>
		</div>
		<aside class="aspect-hero-services" aria-label="<?php esc_attr_e( 'Shopping information', 'aspect-trading' ); ?>">
			<div class="aspect-service-note"><span class="aspect-service-note__index">01</span><div><h2><?php esc_html_e( 'A growing collection', 'aspect-trading' ); ?></h2><p><?php esc_html_e( 'Browse products across the categories you care about.', 'aspect-trading' ); ?></p></div></div>
			<div class="aspect-service-note"><span class="aspect-service-note__index">02</span><div><h2><?php esc_html_e( 'Clear product details', 'aspect-trading' ); ?></h2><p><?php esc_html_e( 'Compare the information that helps you choose.', 'aspect-trading' ); ?></p></div></div>
			<a class="aspect-hero-services__link" href="<?php echo esc_url( home_url( '/help-centre/' ) ); ?>"><?php esc_html_e( 'Visit the Help Centre', 'aspect-trading' ); ?> <span aria-hidden="true">&#8594;</span></a>
		</aside>
	</section>

	<section class="aspect-promise-strip" aria-label="<?php esc_attr_e( 'Shopping at Aspect Trading', 'aspect-trading' ); ?>">
		<div class="aspect-container aspect-promise-strip__inner">
			<div><span class="aspect-promise-icon" aria-hidden="true">&#9670;</span><span><?php esc_html_e( 'A considered range', 'aspect-trading' ); ?></span></div>
			<div><span class="aspect-promise-icon" aria-hidden="true">&#9633;</span><span><?php esc_html_e( 'Straightforward checkout', 'aspect-trading' ); ?></span></div>
			<div><span class="aspect-promise-icon" aria-hidden="true">&#8594;</span><span><?php esc_html_e( 'Order information', 'aspect-trading' ); ?></span></div>
			<div><span class="aspect-promise-icon" aria-hidden="true">&#9675;</span><span><?php esc_html_e( 'Here to help', 'aspect-trading' ); ?></span></div>
		</div>
	</section>

	<div class="aspect-container aspect-home__content">
		<?php if ( $categories ) : ?>
			<section class="aspect-section aspect-category-section" aria-label="<?php esc_attr_e( 'Shop by category', 'aspect-trading' ); ?>">
				<div class="aspect-section__heading"><div><p class="aspect-eyebrow"><?php esc_html_e( 'Start exploring', 'aspect-trading' ); ?></p><h2><?php esc_html_e( 'Shop by category', 'aspect-trading' ); ?></h2></div><a class="aspect-text-link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'All departments', 'aspect-trading' ); ?> <span aria-hidden="true">&#8594;</span></a></div>
				<div class="aspect-category-grid">
					<?php foreach ( array_slice( $categories, 0, 8 ) as $index => $category ) : ?>
						<?php $term_link = get_term_link( $category ); if ( is_wp_error( $term_link ) ) { continue; } $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true ); ?>
						<a class="aspect-category-card" href="<?php echo esc_url( $term_link ); ?>">
							<span class="aspect-category-card__media<?php echo $thumbnail_id ? '' : ' aspect-category-card__media--empty'; ?>">
								<?php if ( $thumbnail_id ) { echo wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy' ) ); } else { ?><span aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span><?php } ?>
							</span>
							<span class="aspect-category-card__name"><?php echo esc_html( $category->name ); ?><span aria-hidden="true">&#8594;</span></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php
		if ( function_exists( 'wc_get_product_ids_on_sale' ) ) {
			$sale_ids = array_slice( wc_get_product_ids_on_sale(), 0, 8 );
			$sale_products = $sale_ids ? wc_get_products( array( 'include' => $sale_ids, 'limit' => 8, 'status' => 'publish' ) ) : array();
			aspect_trading_render_product_section( __( 'Offers worth a look', 'aspect-trading' ), $sale_products, $shop_url, __( 'Live offers', 'aspect-trading' ) );
		}
		?>

		<?php
		if ( function_exists( 'wc_get_products' ) ) {
			$popular_products = wc_get_products( array( 'status' => 'publish', 'limit' => 8, 'orderby' => 'popularity', 'order' => 'DESC' ) );
			aspect_trading_render_product_section( __( 'Popular right now', 'aspect-trading' ), $popular_products, $shop_url, __( 'A place to begin', 'aspect-trading' ) );

			$featured_products = wc_get_products( array( 'status' => 'publish', 'featured' => true, 'limit' => 8, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
			aspect_trading_render_product_section( __( 'Selected for you', 'aspect-trading' ), $featured_products, $shop_url, __( 'The Aspect selection', 'aspect-trading' ) );
		}
		?>

		<?php foreach ( array_slice( $categories, 0, 4 ) as $category ) : ?>
			<?php
			$category_products = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => 4, 'category' => array( $category->slug ), 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
			$category_url = get_term_link( $category );
			aspect_trading_render_product_section( $category->name, $category_products, is_wp_error( $category_url ) ? '' : $category_url, __( 'From this department', 'aspect-trading' ) );
			?>
		<?php endforeach; ?>

		<?php if ( taxonomy_exists( 'product_brand' ) ) : $brands = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => true, 'number' => 8 ) ); ?>
			<?php if ( ! is_wp_error( $brands ) && $brands ) : ?>
				<section class="aspect-section aspect-brand-section"><div class="aspect-section__heading"><div><p class="aspect-eyebrow"><?php esc_html_e( 'Names to know', 'aspect-trading' ); ?></p><h2><?php esc_html_e( 'Explore brands', 'aspect-trading' ); ?></h2></div></div><div class="aspect-brand-list"><?php foreach ( $brands as $brand ) : $brand_url = get_term_link( $brand ); if ( ! is_wp_error( $brand_url ) ) : ?><a href="<?php echo esc_url( $brand_url ); ?>"><?php echo esc_html( $brand->name ); ?></a><?php endif; endforeach; ?></div></section>
			<?php endif; ?>
		<?php endif; ?>

		<section class="aspect-about-band"><div><p class="aspect-eyebrow"><?php esc_html_e( 'Aspect Trading', 'aspect-trading' ); ?></p><h2><?php esc_html_e( 'More choice, made easier to navigate.', 'aspect-trading' ); ?></h2><?php $about = get_page_by_path( 'about-us' ); if ( $about ) : ?><div class="aspect-about-band__content"><?php echo wp_kses_post( wp_trim_words( $about->post_content, 38 ) ); ?></div><?php endif; ?></div><?php if ( $about ) : ?><a class="aspect-text-link" href="<?php echo esc_url( get_permalink( $about ) ); ?>"><?php esc_html_e( 'About us', 'aspect-trading' ); ?> <span aria-hidden="true">&#8594;</span></a><?php endif; ?></section>
	</div>
</main>
<?php get_footer(); ?>