<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Eko_Plastics
 */

get_header();
?>

	<main id="primary" class="site-main">
		<div class="container">
			<?php
			while ( have_posts() ) :
				the_post();

				the_content();

				// If comments are open or we have at least one comment, load up the comment template.
				if ( ! is_cart() && ! is_checkout() && ! is_account_page() && ( comments_open() || get_comments_number() ) ) :
					comments_template();
				endif;

			endwhile; // End of the loop.
			?>
		</div>
		

	</main><!-- #main -->

<?php
if ( ! is_cart() && ! is_checkout() && ! is_account_page() ) {
	get_sidebar();
}
get_footer();
