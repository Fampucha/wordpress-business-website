<?php
/**
 * Testimonials section.
 *
 * @var $section_heading
 * @var $section_subtitle
 * @var $testimonials
 * @package Business_Website
 */

$section_heading  = $section_heading ?? '';
$section_subtitle = $section_subtitle ?? '';
$testimonials     = $testimonials ?? array();
?>

<section class="testimonials">
    <div class="container">

        <div class="testimonials__heading">
            <h2 class="testimonials__title"><?php echo esc_html( $section_heading ); ?></h2>

            <p class="testimonials__subtitle">
                <?php echo esc_html( $section_subtitle ); ?>
            </p>
        </div>

        <?php if ( ! empty( $testimonials ) && is_array( $testimonials ) ) : ?>
			<div class="testimonials__grid">
				<?php
				foreach ( $testimonials as $testimonial ) :
					get_template_part(
						'template-parts/components/testimonial-card',
						null,
						$testimonial
					);
				endforeach;
				?>
			</div>
		<?php endif; ?>

    </div>
</section>
