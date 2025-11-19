<?php use ProUltra\Core\SVG_Icons; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<header class="pro-ultra-header pro-ultra-card">
<div class="container">
<div class="pro-ultra-header__inner" style="display:flex;align-items:center;justify-content:space-between;gap:20px;">
<div class="pro-ultra-branding">
<?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?>
<a href="<?php echo esc_url( home_url('/') ); ?>" class="pro-ultra-brand"><?php bloginfo( 'name' ); ?></a>
<?php } ?>
<p class="pro-ultra-tagline" style="margin:0;color:#6b7280;"><?php bloginfo( 'description' ); ?></p>
</div>
<nav class="pro-ultra-nav" aria-label="Main Menu">
<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'fallback_cb' => '__return_false' ) ); ?>
</nav>
<div class="pro-ultra-header__actions">
<?php
    $account_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
    $wishlist_page = get_page_by_path( 'wishlist' );
    $wishlist_url  = $wishlist_page ? get_permalink( $wishlist_page ) : home_url( '/wishlist' );
?>
<a class="pro-ultra-header__icon" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'Hesabım', 'pro-ultra-ai' ); ?>"><?php echo SVG_Icons::get_icon( 'ui-support', 'pro-ultra-icon' ); ?></a>
<a class="pro-ultra-header__icon" href="<?php echo esc_url( $wishlist_url ); ?>" aria-label="<?php esc_attr_e( 'Favoriler', 'pro-ultra-ai' ); ?>"><?php echo SVG_Icons::get_icon( 'ui-wishlist', 'pro-ultra-icon' ); ?></a>
<button class="pro-ultra-cart-toggle" data-cart-toggle aria-expanded="false" aria-label="<?php esc_attr_e( 'Mini sepeti aç', 'pro-ultra-ai' ); ?>">
<span class="pro-ultra-cart-toggle__icon"><?php echo SVG_Icons::get_icon( 'ui-cart', 'pro-ultra-icon' ); ?></span>
<span class="pro-ultra-cart-toggle__count" data-cart-count><?php echo function_exists( 'WC' ) && WC()->cart ? esc_html( WC()->cart->get_cart_contents_count() ) : '0'; ?></span>
</button>
</div>
</div>
</div>
</header>
<div id="pro-ultra-toast" class="pro-ultra-toast" role="status"></div>
<?php do_action( 'pro_ultra_ai_chatbox' ); ?>
<main class="pro-ultra-main container">
