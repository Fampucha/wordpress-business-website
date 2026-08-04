<?php
/**
 * Template Name: Validation Form
 *
 * @package Eko_Plastics
 */

$feedback = eko_plastics_get_validation_form_feedback();
$values   = isset( $feedback['values'] ) && is_array( $feedback['values'] ) ? $feedback['values'] : array();
$name     = isset( $values['name'] ) ? $values['name'] : '';
$phone    = isset( $values['phone'] ) ? $values['phone'] : '';

get_header();
?>

<main id="primary" class="site-main">
	<section class="validation-form-page">
		<div class="container">
			<div class="validation-form-page__inner">
				<div class="validation-form-page__heading">
					<h1 class="validation-form-page__title"><?php the_title(); ?></h1>
					<p class="validation-form-page__subtitle">
						<?php esc_html_e( 'Leave your name and Ukrainian mobile phone number. The form validates the data and sends it to the connected CRM services.', 'eko-plastics' ); ?>
					</p>
				</div>

				<?php if ( 'success' === $feedback['status'] ) : ?>
					<div class="validation-form-page__message validation-form-page__message--success" role="status">
						<?php esc_html_e( 'Thank you. Your data was validated and the CRM flow was started successfully.', 'eko-plastics' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( 'error' === $feedback['status'] && ! empty( $feedback['errors'] ) ) : ?>
					<div class="validation-form-page__message validation-form-page__message--error" role="alert">
						<ul>
							<?php foreach ( $feedback['errors'] as $error ) : ?>
								<li><?php echo esc_html( $error ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<form class="validation-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate>
					<input type="hidden" name="action" value="eko_plastics_validation_form">
					<input type="hidden" name="page_url" value="<?php echo esc_url( get_permalink() ); ?>">
					<input type="hidden" name="form_started_at" value="<?php echo esc_attr( time() ); ?>">
					<?php wp_nonce_field( 'eko_plastics_validation_form', 'eko_plastics_validation_form_nonce' ); ?>

					<div class="validation-form__field validation-form__field--honeypot" aria-hidden="true">
						<label for="contact_website"><?php esc_html_e( 'Website', 'eko-plastics' ); ?></label>
						<input id="contact_website" type="text" name="contact_website" value="" tabindex="-1" autocomplete="off">
					</div>

					<div class="validation-form__field">
						<label for="contact_name"><?php esc_html_e( 'Name', 'eko-plastics' ); ?></label>
						<input
							id="contact_name"
							type="text"
							name="contact_name"
							value="<?php echo esc_attr( $name ); ?>"
							placeholder="<?php esc_attr_e( 'Olena', 'eko-plastics' ); ?>"
							autocomplete="name"
							required
						>
					</div>

					<div class="validation-form__field">
						<label for="contact_phone"><?php esc_html_e( 'Phone', 'eko-plastics' ); ?></label>
						<input
							id="contact_phone"
							type="tel"
							name="contact_phone"
							value="<?php echo esc_attr( $phone ); ?>"
							placeholder="+380 (67) 123-45-67"
							autocomplete="tel"
							inputmode="tel"
							required
							pattern="^\+380 \((39|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\) [0-9]{3}-[0-9]{2}-[0-9]{2}$"
						>
					</div>

					<button class="validation-form__submit button" type="submit">
						<?php esc_html_e( 'Send', 'eko-plastics' ); ?>
					</button>
				</form>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
