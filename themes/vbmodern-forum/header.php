<?php
/**
 * The header for our theme
 *
 * @package VBModern_Forum
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
  <header class="header" role="banner">
    <div class="header__inner">
      <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
        <span class="logo__badge">VB</span>
        <span><?php bloginfo( 'name' ); ?></span>
      </a>

      <button class="nav__toggle" aria-expanded="false" aria-controls="primary-menu">
        <span class="visually-hidden"><?php esc_html_e( 'Toggle navigation', 'vbmodern-forum' ); ?></span>
        &#9776;
      </button>

      <nav class="nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'vbmodern-forum' ); ?>">
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'menu_class'     => 'nav__menu',
                'container'      => false,
                'fallback_cb'    => false,
            ]
        );
        ?>
        <div class="nav__actions">
          <?php if ( is_user_logged_in() ) : ?>
            <button class="button button--ghost nav__cta" type="button" data-modal-trigger="#vb-modal-topic"><?php esc_html_e( 'Yeni Konu Aç', 'vbmodern-forum' ); ?></button>
            <a class="button nav__cta" href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>"><?php esc_html_e( 'Profilim', 'vbmodern-forum' ); ?></a>
          <?php else : ?>
            <button class="button nav__cta" type="button" data-modal-trigger="#vb-modal-register"><?php esc_html_e( 'Join the Community', 'vbmodern-forum' ); ?></button>
          <?php endif; ?>
        </div>
      </nav>
    </div>
  </header>

  <main id="primary" class="site-main" role="main">
