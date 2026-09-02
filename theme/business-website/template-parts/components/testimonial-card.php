<?php
/**
 * Testimonial card.
 *
 * @package Business_Website
 */

$review      = $args['review'] ?? '';
$avatar      = $args['avatar'] ?? '';
$name        = $args['name'] ?? '';
$description = $args['description'] ?? '';


$avatar_url = '';
$avatar_alt = '';

if ( is_array( $avatar ) ) {
	$avatar_url = $avatar['url'] ?? '';
	$avatar_alt = $avatar['alt'] ?? '';
} elseif ( is_string( $avatar ) ) {
	$avatar_url = $avatar;
}
?>

<article class="testimonial-card">

	<?php if ( $review ) : ?>
		<blockquote class="testimonial-card__quote">
			&ldquo;<?php echo esc_html( $review ); ?>&rdquo;
		</blockquote>
	<?php endif; ?>

	<?php if ( $avatar_url || $name || $description ) : ?>
		<div class="testimonial-card__author">

			<?php if ( $avatar_url ) : ?>
				<img
					class="testimonial-card__avatar"
					src="<?php echo esc_url( $avatar_url ); ?>"
					alt="<?php echo esc_attr( $avatar_alt ); ?>"
				>
			<?php endif; ?>

			<?php if ( $name || $description ) : ?>
				<div class="testimonial-card__author-info">

					<?php if ( $name ) : ?>
						<p class="testimonial-card__name">
							<?php echo esc_html( $name ); ?>
						</p>
					<?php endif; ?>

					<?php if ( $description ) : ?>
						<p class="testimonial-card__description">
							<?php echo esc_html( $description ); ?>
						</p>
					<?php endif; ?>

				</div>
			<?php endif; ?>

		</div>
	<?php endif; ?>

</article>