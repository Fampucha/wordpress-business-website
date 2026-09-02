<?php
/**
 * Site header.
 *
 * @package Business_Website
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="container site-header__inner">

        <a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <?php bloginfo( 'name' ); ?>
        </a>

        <nav class="site-navigation" aria-label="<?php esc_attr_e( 'Header navigation', 'business-website' ); ?>">
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'header-menu',
                    'container'      => false,
                    'menu_class'     => 'site-navigation__list',
                    'fallback_cb'    => false,
                    'walker'         => new Business_Website_Header_Menu_Walker(),
                )
            );
            ?>
        </nav>

        <details
            class="menu-toggle"
            aria-label="<?php esc_attr_e( 'Open menu', 'business-website' ); ?>"
        >
            <summary class="menu-toggle__summary">
                <svg class="menu-toggle__icon" width="54" height="32" viewBox="0 0 54 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M20.6743 16C20.6743 14.3431 21.4213 13 22.3429 13H52.0058C52.9273 13 53.6743 14.3431 53.6743 16C53.6743 17.6569 52.9273 19 52.0058 19H22.3429C21.4213 19 20.6743 17.6569 20.6743 16Z" fill="black"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3 29C3 27.3431 4.1545 26 5.57865 26H51.4213C52.8455 26 54 27.3431 54 29C54 30.6569 52.8455 32 51.4213 32H5.57865C4.1545 32 3 30.6569 3 29Z" fill="black"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2.67432 3.5C2.67432 1.567 3.82882 0 5.25297 0H51.0957C52.5198 0 53.6743 1.567 53.6743 3.5C53.6743 5.433 52.5198 7 51.0957 7H5.25297C3.82882 7 2.67432 5.433 2.67432 3.5Z" fill="black"/>
                </svg>
                <svg class="menu-toggle__close" width="40" height="42" viewBox="0 0 40 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.00684 2.02828C4.22763 0.908091 5.99784 0.850656 6.96069 1.89999L37.9546 35.6777C38.9175 36.727 38.7084 38.4857 37.4876 39.6059C36.2668 40.7261 34.4966 40.7835 33.5337 39.7342L2.53979 5.95655C1.57694 4.90721 1.78604 3.14846 3.00684 2.02828Z" fill="black"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M37.7962 2.76535C38.9809 3.92356 39.1344 5.68803 38.1388 6.70642L6.09298 39.4878C5.09744 40.5062 3.32994 40.3928 2.14515 39.2346C0.960365 38.0764 0.806944 36.3119 1.80248 35.2935L33.8483 2.51218C34.8439 1.4938 36.6114 1.60714 37.7962 2.76535Z" fill="black"/>
                </svg>
            </summary>
        </details>

    </div>
    <nav class="navigation-mobile" aria-label="<?php esc_attr_e( 'Mobile navigation', 'business-website' ); ?>">
        <?php
        wp_nav_menu(
            array(
                'theme_location' => 'header-menu',
                'container'      => false,
                'menu_class'     => 'navigation-mobile__list',
                'fallback_cb'    => false,
                'walker'         => new Business_Website_Header_Menu_Walker(),
            )
        );
        ?>
    </nav>
</header>
