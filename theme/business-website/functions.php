<?php
/**
 * Business Website functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Business_Website
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function business-website_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on Business Website, use a find and replace
		* to change 'business-website' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'business-website', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'woocommerce' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'header-menu' => esc_html__( 'Header Menu', 'business-website' ),
			'footer-menu' => esc_html__( 'Footer Menu', 'business-website' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'business-website_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'business-website_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function business-website_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'business-website_content_width', 640 );
}
add_action( 'after_setup_theme', 'business-website_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function business-website_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'business-website' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'business-website' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'business-website_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function business-website_scripts() {
	wp_enqueue_style(
		'business-website-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap',
		array(),
		null
	);

	wp_enqueue_style( 'business-website-style', get_template_directory_uri() . '/assets/css/style.css', array( 'business-website-fonts' ), _S_VERSION );

	wp_enqueue_script( 'business-website-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_page_template( 'templates/template-validation-form.php' ) ) {
		wp_enqueue_script(
			'business-website-validation-form',
			get_template_directory_uri() . '/assets/js/validation-form.js',
			array(),
			_S_VERSION,
			true
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'business-website_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Custom ACF fields.
 */
require get_template_directory() . '/inc/acf-fields.php';

/**
 * CRM integrations.
 */
require get_template_directory() . '/inc/crm-integrations.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Adds SVG support to WordPress media uploads.
 *
 * @param array $mimes Allowed mime types.
 * @return array
 */
function business-website_allow_svg_uploads( $mimes ) {
	$mimes['svg'] = 'image/svg+xml';

	return $mimes;
}
add_filter( 'upload_mimes', 'business-website_allow_svg_uploads' );

/**
 * Header menu walker.
 */
class Business_Website_Header_Menu_Walker extends Walker_Nav_Menu {

	/**
	 * Starts the element output.
	 *
	 * @param string  $output            Used to append additional content.
	 * @param WP_Post $data_object       Menu item data object.
	 * @param int     $depth             Depth of menu item.
	 * @param object  $args              An object of wp_nav_menu() arguments.
	 * @param int     $current_object_id Optional. ID of the current menu item.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		add_filter( 'nav_menu_link_attributes', array( $this, 'add_button_class' ), 10, 4 );

		parent::start_el( $output, $data_object, $depth, $args, $current_object_id );

		remove_filter( 'nav_menu_link_attributes', array( $this, 'add_button_class' ), 10 );
	}

	/**
	 * Adds button class to menu item links marked in ACF.
	 *
	 * @param array   $atts      Link attributes.
	 * @param WP_Post $menu_item Menu item data object.
	 * @param object  $args      An object of wp_nav_menu() arguments.
	 * @param int     $depth     Depth of menu item.
	 * @return array
	 */
	public function add_button_class( $atts, $menu_item, $args, $depth ) {
		if ( ! function_exists( 'get_field' ) || ! get_field( 'button', $menu_item ) ) {
			return $atts;
		}

		$classes        = empty( $atts['class'] ) ? array() : explode( ' ', $atts['class'] );
		$classes[]      = 'button';
		$atts['class']  = implode( ' ', array_unique( array_filter( $classes ) ) );

		return $atts;
	}
}

/**
 * Returns feedback saved after validation form redirect.
 *
 * @return array
 */
function business-website_get_validation_form_feedback() {
	$key = isset( $_GET['validation_form_feedback'] ) ? sanitize_key( wp_unslash( $_GET['validation_form_feedback'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( empty( $key ) ) {
		return array(
			'status' => '',
			'errors' => array(),
			'values' => array(),
		);
	}

	$feedback = get_transient( 'business-website_validation_form_' . $key );
	delete_transient( 'business-website_validation_form_' . $key );

	if ( ! is_array( $feedback ) ) {
		return array(
			'status' => '',
			'errors' => array(),
			'values' => array(),
		);
	}

	return wp_parse_args(
		$feedback,
		array(
			'status' => '',
			'errors' => array(),
			'values' => array(),
		)
	);
}

/**
 * Checks if name contains only letters, spaces, apostrophes, or hyphens.
 *
 * @param string $name Name.
 * @return bool
 */
function business-website_is_valid_contact_name( $name ) {
	return (bool) preg_match( "/^[\p{L}\s'-]{2,60}$/u", $name );
}

/**
 * Normalizes Ukrainian phone number to +380XXXXXXXXX.
 *
 * @param string $phone Phone.
 * @return string
 */
function business-website_normalize_ukrainian_phone( $phone ) {
	$digits = preg_replace( '/\D+/', '', $phone );

	if ( 12 === strlen( $digits ) && '380' === substr( $digits, 0, 3 ) ) {
		return '+' . $digits;
	}

	if ( 10 === strlen( $digits ) && '0' === substr( $digits, 0, 1 ) ) {
		return '+38' . $digits;
	}

	return '';
}

/**
 * Checks Ukrainian mobile operator phone mask.
 *
 * @param string $phone Phone.
 * @return bool
 */
function business-website_is_valid_ukrainian_phone( $phone ) {
	$normalized_phone = business-website_normalize_ukrainian_phone( $phone );

	if ( empty( $normalized_phone ) ) {
		return false;
	}

	return (bool) preg_match( '/^\+380(39|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\d{7}$/', $normalized_phone );
}

/**
 * Gets client IP for lightweight rate limiting.
 *
 * @return string
 */
function business-website_get_validation_form_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return preg_replace( '/[^0-9a-fA-F:\.]/', '', $ip );
}

/**
 * Redirects back to the form with temporary feedback.
 *
 * @param string $status Status.
 * @param array  $errors Errors.
 * @param array  $values Submitted values.
 */
function business-website_redirect_validation_form( $status, $errors = array(), $values = array() ) {
	$key = wp_generate_uuid4();

	set_transient(
		'business-website_validation_form_' . $key,
		array(
			'status' => $status,
			'errors' => $errors,
			'values' => $values,
		),
		10 * MINUTE_IN_SECONDS
	);

	$redirect_url = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$redirect_url = remove_query_arg( 'validation_form_feedback', $redirect_url );
	$redirect_url = add_query_arg( 'validation_form_feedback', $key, $redirect_url );

	wp_safe_redirect( $redirect_url );
	exit;
}

/**
 * Handles validation form submission.
 */
function business-website_handle_validation_form_submission() {
	$values = array(
		'name'  => isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '',
		'phone' => isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '',
	);
	$errors = array();

	if ( ! isset( $_POST['business-website_validation_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['business-website_validation_form_nonce'] ) ), 'business-website_validation_form' ) ) {
		$errors[] = __( 'Security check failed. Please reload the page and try again.', 'business-website' );
	}

	$honeypot = isset( $_POST['contact_website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['contact_website'] ) ) ) : '';
	if ( '' !== $honeypot ) {
		$errors[] = __( 'Spam protection blocked the submission.', 'business-website' );
	}

	$started_at = isset( $_POST['form_started_at'] ) ? absint( $_POST['form_started_at'] ) : 0;
	if ( $started_at <= 0 || time() - $started_at < 3 || time() - $started_at > 2 * HOUR_IN_SECONDS ) {
		$errors[] = __( 'Please submit the form again.', 'business-website' );
	}

	$rate_limit_key = 'business-website_validation_form_rate_' . md5( business-website_get_validation_form_ip() );
	if ( get_transient( $rate_limit_key ) ) {
		$errors[] = __( 'Please wait a minute before sending the form again.', 'business-website' );
	}

	if ( ! business-website_is_valid_contact_name( $values['name'] ) ) {
		$errors[] = __( 'Name can contain only letters, spaces, apostrophes, or hyphens.', 'business-website' );
	}

	if ( ! business-website_is_valid_ukrainian_phone( $values['phone'] ) ) {
		$errors[] = __( 'Enter a valid Ukrainian mobile phone number.', 'business-website' );
	}

	if ( ! empty( $errors ) ) {
		business-website_redirect_validation_form( 'error', $errors, $values );
	}

	$normalized_phone = business-website_normalize_ukrainian_phone( $values['phone'] );
	$page_url         = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

	if ( empty( $page_url ) ) {
		$page_url = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	}

	$payload          = array(
		'name'  => $values['name'],
		'phone' => $normalized_phone,
		'page'  => $page_url,
	);

	set_transient( $rate_limit_key, 1, MINUTE_IN_SECONDS );

	$salesdrive_result = business-website_send_salesdrive_lead( $payload );

	if ( is_wp_error( $salesdrive_result ) ) {
		$errors[] = $salesdrive_result->get_error_message();
		business-website_redirect_validation_form( 'error', $errors, $values );
	}

	do_action( 'business-website_salesdrive_lead_created', $payload, $salesdrive_result );

	$crm_options = business-website_get_crm_integration_options();

	if ( 'after_form_submit' === $crm_options['dilovod_trigger'] ) {
		$dilovod_result = business-website_send_dilovod_client( $payload, $salesdrive_result );

		if ( is_wp_error( $dilovod_result ) ) {
			$errors[] = $dilovod_result->get_error_message();
			business-website_redirect_validation_form( 'error', $errors, $values );
		}
	} else {
		business-website_track_salesdrive_webhook_wait( $payload, $salesdrive_result );
		business-website_crm_log( 'Dilovod waits for SalesDrive webhook.', array( 'trigger' => $crm_options['dilovod_trigger'] ) );
	}

	business-website_redirect_validation_form( 'success' );
}
add_action( 'admin_post_business-website_validation_form', 'business-website_handle_validation_form_submission' );
add_action( 'admin_post_nopriv_business-website_validation_form', 'business-website_handle_validation_form_submission' );