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
	<section class="aspect-hero" aria-label="<?php esc_attr_e( 'Marketplace highlights', 'aspect-trading' ); ?>">
		<div class="aspect-hero-slider" role="region" aria-label="<?php esc_attr_e( 'Featured campaigns', 'aspect-trading' ); ?>">
			<div id="aspect-hero-slides" class="aspect-hero-slides" aria-live="polite">
				<article class="aspect-hero-slide is-active" data-slide="1" aria-hidden="false">
					<div class="aspect-hero-slide__media" aria-hidden="true">
						<?php
						$hero_image_1_id = get_theme_mod( 'aspect_hero_image_1' );
						if ( $hero_image_1_id ) {
							echo wp_get_attachment_image( $hero_image_1_id, 'full', false, array( 'class' => 'aspect-hero-slide__image', 'loading' => 'eager', 'fetchpriority' => 'high' ) );
						} else {
							echo '<div class="aspect-hero-slide__placeholder" aria-hidden="true"><span class="aspect-hero-slide__pattern"></span></div>';
						}
						?>
					</div>
					<div class="aspect-hero-slide__content">
						<p class="aspect-eyebrow"><?php esc_html_e( 'Everything you need, all in one place', 'aspect-trading' ); ?></p>
						<h1><?php esc_html_e( 'Shop across technology, home, dining, appliances & everyday essentials.', 'aspect-trading' ); ?></h1>
						<a href="<?php echo esc_url( $shop_url ); ?>" class="aspect-button aspect-button--primary"><?php esc_html_e( 'Shop now', 'aspect-trading' ); ?><span aria-hidden="true">&#8594;</span></a>
					</div>
				</article>
				<article class="aspect-hero-slide" data-slide="2" aria-hidden="true">
					<div class="aspect-hero-slide__media" aria-hidden="true">
						<?php
						$hero_image_2_id = get_theme_mod( 'aspect_hero_image_2' );
						if ( $hero_image_2_id ) {
							echo wp_get_attachment_image( $hero_image_2_id, 'full', false, array( 'class' => 'aspect-hero-slide__image', 'loading' => 'lazy' ) );
						} else {
							echo '<div class="aspect-hero-slide__placeholder" aria-hidden="true"><span class="aspect-hero-slide__pattern"></span></div>';
						}
						?>
					</div>
					<div class="aspect-hero-slide__content">
						<p class="aspect-eyebrow"><?php esc_html_e( 'More choice. Better finds.', 'aspect-trading' ); ?></p>
						<h2><?php esc_html_e( 'Discover great products and offers across your favourite categories.', 'aspect-trading' ); ?></h2>
						<a href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', $shop_url ) ); ?>" class="aspect-button aspect-button--outline"><?php esc_html_e( 'Explore deals', 'aspect-trading' ); ?><span aria-hidden="true">&#8594;</span></a>
					</div>
				</article>
				<article class="aspect-hero-slide" data-slide="3" aria-hidden="true">
					<div class="aspect-hero-slide__media" aria-hidden="true">
						<?php
						$hero_image_3_id = get_theme_mod( 'aspect_hero_image_3' );
						if ( $hero_image_3_id ) {
							echo wp_get_attachment_image( $hero_image_3_id, 'full', false, array( 'class' => 'aspect-hero-slide__image', 'loading' => 'lazy' ) );
						} else {
							echo '<div class="aspect-hero-slide__placeholder" aria-hidden="true"><span class="aspect-hero-slide__pattern"></span></div>';
						}
						?>
					</div>
					<div class="aspect-hero-slide__content">
						<p class="aspect-eyebrow"><?php esc_html_e( 'Quality you trust. Prices you love.', 'aspect-trading' ); ?></p>
						<h2><?php esc_html_e( 'Premium brands, straightforward prices, delivered to your door.', 'aspect-trading' ); ?></h2>
						<a href="<?php echo esc_url( add_query_arg( 'orderby', 'date', $shop_url ) ); ?>" class="aspect-button aspect-button--primary"><?php esc_html_e( 'See what is new', 'aspect-trading' ); ?><span aria-hidden="true">&#8594;</span></a>
					</div>
				</article>
			</div>
			<div class="aspect-hero-slider__controls" aria-label="<?php esc_attr_e( 'Slide navigation', 'aspect-trading' ); ?>">
				<button type="button" class="aspect-hero-slider__btn aspect-hero-slider__btn--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'aspect-trading' ); ?>" aria-controls="aspect-hero-slides"><span aria-hidden="true">&#8592;</span></button>
				<div class="aspect-hero-slider__indicators" role="tablist" aria-label="<?php esc_attr_e( 'Select slide', 'aspect-trading' ); ?>">
					<button type="button" class="aspect-hero-slider__indicator is-active" role="tab" aria-selected="true" aria-label="<?php esc_attr_e( 'Slide 1: Everything you need', 'aspect-trading' ); ?>" data-slide="1"></button>
					<button type="button" class="aspect-hero-slider__indicator" role="tab" aria-selected="false" aria-label="<?php esc_attr_e( 'Slide 2: More choice', 'aspect-trading' ); ?>" data-slide="2"></button>
					<button type="button" class="aspect-hero-slider__indicator" role="tab" aria-selected="false" aria-label="<?php esc_attr_e( 'Slide 3: Quality you trust', 'aspect-trading' ); ?>" data-slide="3"></button>
				</div>
				<button type="button" class="aspect-hero-slider__btn aspect-hero-slider__btn--next" aria-label="<?php esc_attr_e( 'Next slide', 'aspect-trading' ); ?>" aria-controls="aspect-hero-slides"><span aria-hidden="true">&#8594;</span></button>
			</div>
		</div>
		<?php if ( $categories ) : ?>
			<div class="aspect-hero-categories-rail" aria-label="<?php esc_attr_e( 'Shop by category', 'aspect-trading' ); ?>">
				<div class="aspect-hero-categories-rail__inner">
					<ul class="aspect-category-rail" role="list">
						<?php foreach ( array_slice( $categories, 0, 12 ) as $category ) : ?>
							<?php $term_link = get_term_link( $category ); if ( is_wp_error( $term_link ) ) { continue; } $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true ); ?>
							<li>
								<a class="aspect-category-rail__item" href="<?php echo esc_url( $term_link ); ?>">
									<span class="aspect-category-rail__media<?php echo $thumbnail_id ? '' : ' aspect-category-rail__media--empty'; ?>">
										<?php if ( $thumbnail_id ) { echo wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail' ); } else { ?><span class="aspect-category-rail__icon" aria-hidden="true"><?php echo esc_html( mb_substr( $category->name, 0, 1 ) ); ?></span><?php } ?>
									</span>
									<span class="aspect-category-rail__name"><?php echo esc_html( $category->name ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
						<li>
							<a class="aspect-category-rail__item aspect-category-rail__item--view-all" href="<?php echo esc_url( $shop_url ); ?>">
								<span class="aspect-category-rail__media" aria-hidden="true"><span class="aspect-category-rail__icon">&#8594;</span></span>
								<span class="aspect-category-rail__name"><?php esc_html_e( 'View all', 'aspect-trading' ); ?></span>
							</a>
						</li>
					</ul>
				</div>
			</div>
		<?php endif; ?>
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
		}
		?>

		<?php foreach ( array_slice( $categories, 0, 4 ) as $category ) : ?>
			<?php
			$category_products = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => 4, 'category' => array( $category->slug ), 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
			$category_url = get_term_link( $category );
			aspect_trading_render_product_section( $category->name, $category_products, is_wp_error( $category_url ) ? '' : $category_url, __( 'From this category', 'aspect-trading' ) );
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
