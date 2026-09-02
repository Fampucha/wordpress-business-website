<?php
/**
 * Contact form section.
 * @var $contact_title
 * @var $contact_subtitle
 * @var $contact_image
 * @var $form
 * 
 * @package Business_Website
 */

$contact_title       = $contact_title ?? '';
$contact_subtitle    = $contact_subtitle ?? '';
$contact_image       = $contact_image ?? '';
$form                = $form ?? '';
?>

<section class="contact-section">
    <div class="container">
        <div class="contact-section__grid">

            <div class="contact-section__content">
                <div class="contact-section__heading">
                    <h1 class="contact-section__title"><?php echo esc_html( $contact_title ); ?></h1>

                    <p class="contact-section__subtitle">
                        <?php echo esc_html( $contact_subtitle ); ?>
                    </p>
                </div>

                <?php gravity_form( $form, false, false, false, null, true ); ?>
            </div>

            <div class="contact-section__media">
                <img
                    class="contact-section__image"
                    src="<?php echo esc_url( $contact_image ); ?>"
                    alt="Portrait"
                >
            </div>

        </div>
    </div>
</section>