<?php
/**
 * WooCommerce template wrapper.
 *
 * @package Business_Website
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<?php woocommerce_content(); ?>
	</div>
</main>

<?php
get_footer();
