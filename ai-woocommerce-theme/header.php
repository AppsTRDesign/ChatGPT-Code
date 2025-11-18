<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<header class="ai-container" style="padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;">
<div class="logo-area">
<?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?><strong><?php bloginfo( 'name' ); ?></strong><?php } ?>
<p style="margin:0;font-size:0.9rem;color:#6c757d;">AI Commerce Pro</p>
</div>
<nav class="main-nav">
<?php wp_nav_menu( [ 'theme_location' => 'primary', 'container' => false ] ); ?>
</nav>
<div class="ai-language-switch">
<label for="ai-lang-select" class="screen-reader-text"><?php esc_html_e( 'Language', 'ai-commerce-pro' ); ?></label>
<select id="ai-lang-select">
<option value="tr">TR</option>
<option value="en">EN</option>
</select>
</div>
</header>
