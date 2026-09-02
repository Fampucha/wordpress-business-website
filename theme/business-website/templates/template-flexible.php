<?php
/**
 * Template name: Flexible Template
 *
 * @package Business_Website
 */

get_header();
?>

<main id="primary" class="site-main">
	<?php
	$flexible_sections = get_field( 'flexible' );
	$render_flexible_section = static function ( $template, $section ) {
		extract( $section, EXTR_SKIP );

		include $template; 
	};
	?>

	<?php if ( ! empty( $flexible_sections ) && is_array( $flexible_sections ) ) : ?>
		<?php
		foreach ( $flexible_sections as $section ) :
			$layout = isset( $section['acf_fc_layout'] ) ? sanitize_file_name( $section['acf_fc_layout'] ) : '';

			if ( ! empty( $layout ) ) {
				$template = locate_template( "template-parts/flexible/flexible-{$layout}.php" );

				if ( ! empty( $template ) ) {
					$render_flexible_section( $template, $section );
				}
			}
		endforeach;
		?>
	<?php endif; ?>
</main>

<?php
get_footer();
