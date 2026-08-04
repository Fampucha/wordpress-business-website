<?php
/**
 * WooCommerce template wrapper.
 *
 * @package Eko_Plastics
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
