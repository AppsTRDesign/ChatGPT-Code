<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
<div class="container site-header__inner">
<div class="site-branding">
<?php if ( has_custom_logo() ) : ?>
<?php the_custom_logo(); ?>
<?php else : ?>
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title"><?php bloginfo( 'name' ); ?></a>
<p class="site-description"><?php bloginfo( 'description' ); ?></p>
<?php endif; ?>
</div>

<button class="nav-toggle" aria-expanded="false" aria-controls="primary-menu">
<span class="nav-toggle__line"></span>
<span class="nav-toggle__line"></span>
<span class="nav-toggle__line"></span>
<span class="screen-reader-text"><?php esc_html_e( 'Menüyü aç/kapa', 'cinematic-pro' ); ?></span>
</button>

<nav id="primary-menu" class="site-navigation">
<?php
wp_nav_menu(
[
'theme_location' => 'primary',
'menu_class'     => 'menu',
'container'      => false,
]
);
?>
</nav>
</div>
</header>
