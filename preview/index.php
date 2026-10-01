<?php
/**
 * Generic fallback template for the standalone local preview theme.
 *
 * @package Aspect_Trading
 */

get_header();
?>
<main id="main-content" class="aspect-container aspect-content-page">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No content was found.', 'aspect-trading' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
