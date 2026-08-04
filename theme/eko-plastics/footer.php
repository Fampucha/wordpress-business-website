<?php
/**
 * Site footer.
 *
 * @package Eko_Plastics
 */
?>

<footer class="site-footer">
    <div class="container">
        <div class="site-footer__inner">

            <div class="site-footer__brand">
                <a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <?php bloginfo( 'name' ); ?>
                </a>

                <?php
                $socials = function_exists( 'get_field' ) ? get_field( 'socials', 'option' ) : array();
                ?>

                <?php if ( ! empty( $socials ) && is_array( $socials ) ) : ?>
                    <ul class="site-footer__socials">
                        <?php foreach ( $socials as $social ) : ?>
                            <?php
                            $avatar     = $social['avatar'] ?? '';
                            $link       = $social['link'] ?? '';
                            $avatar_url = '';
                            $avatar_alt = '';

                            if ( is_array( $avatar ) ) {
                                $avatar_url = $avatar['url'] ?? '';
                                $avatar_alt = $avatar['alt'] ?? '';
                            } elseif ( is_numeric( $avatar ) ) {
                                $avatar_url = wp_get_attachment_image_url( $avatar, 'full' );
                                $avatar_alt = get_post_meta( $avatar, '_wp_attachment_image_alt', true );
                            } elseif ( is_string( $avatar ) ) {
                                $avatar_url = $avatar;
                            }
                            ?>

                            <?php if ( ! empty( $link ) && ! empty( $avatar_url ) ) : ?>
                                <li class="site-footer__social">
                                    <a
                                        class="site-footer__social-link"
                                        href="<?php echo esc_url( $link ); ?>"
                                        aria-label="<?php echo esc_attr( $avatar_alt ? $avatar_alt : __( 'Social link', 'eko-plastics' ) ); ?>"
                                    >
                                        <img
                                            class="site-footer__social-icon"
                                            src="<?php echo esc_url( $avatar_url ); ?>"
                                            alt="<?php echo esc_attr( $avatar_alt ); ?>"
                                        >
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <nav class="site-navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'eko-plastics' ); ?>">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'footer-menu',
                        'container'      => false,
                        'menu_class'     => 'site-footer__navigation',
                        'fallback_cb'    => false,
                    )
                );
                ?>
            </nav>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
