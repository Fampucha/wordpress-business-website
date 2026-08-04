<?php
/**
 * Hero section.
 *
 * @var $hero_title
 * @var $hero_subtitle
 * @var $button_label
 * @var $hero_image
 * @package Eko_Plastics
 */

$hero_title    = $hero_title ?? '';
$hero_subtitle = $hero_subtitle ?? '';
$button_label  = $button_label ?? '';
$hero_image    = $hero_image ?? '';
?>

<section class="hero">
    <div class="container">
        <div class="hero__content">
            <h1 class="hero__title"><?php echo esc_html( $hero_title ); ?></h1>

            <p class="hero__subtitle">
                <?php echo esc_html( $hero_subtitle ); ?>
            </p>

            <a class="button button--small" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
                <?php echo esc_html( $button_label ); ?>
            </a>
        </div>

        <div class="hero__media">
            <img
                class="hero__image"
                src="<?php echo esc_url( $hero_image ); ?>"
                alt="Table with food"
            >
        </div>

    </div>
</section>
