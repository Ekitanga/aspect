<?php
/**
 * Aspect Trading customer-service footer.
 *
 * @package Aspect_Trading
 */
?>
	<footer class="aspect-footer">
		<div class="aspect-container aspect-footer__grid">
			<div class="aspect-footer__brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( aspect_trading_brand_logo_url() ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ?: 'Aspect Trading' ); ?>" width="288" height="72" loading="lazy"></a>
				<p><?php esc_html_e( 'A thoughtful destination for discovering everyday essentials and more.', 'aspect-trading' ); ?></p>
			</div>
			<?php
			$footer_groups = array(
				__( 'Customer care', 'aspect-trading' ) => array( 'help-centre', 'contact-us', 'track-order', 'faqs' ),
				__( 'Shopping with us', 'aspect-trading' ) => array( 'delivery-information', 'returns-policy', 'shop' ),
				__( 'About Aspect', 'aspect-trading' ) => array( 'about-us', 'privacy-policy', 'terms-and-conditions' ),
			);
			foreach ( $footer_groups as $heading => $slugs ) :
				?>
				<div class="aspect-footer__column">
					<h2><?php echo esc_html( $heading ); ?></h2>
					<ul>
						<?php foreach ( $slugs as $slug ) : ?>
							<?php $page = get_page_by_path( $slug ); $url = $page ? get_permalink( $page ) : ( 'shop' === $slug && function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : false ); $label = $page ? get_the_title( $page ) : ucwords( str_replace( '-', ' ', $slug ) ); ?>
							<?php if ( $url ) : ?><li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li><?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="aspect-footer__bottom">
			<div class="aspect-container"><span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ?: 'Aspect Trading' ); ?></span><span><?php esc_html_e( 'Thoughtful shopping, made straightforward.', 'aspect-trading' ); ?></span></div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>