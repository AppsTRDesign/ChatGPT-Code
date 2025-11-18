<?php
/**
 * WooCommerce Product Archive override with AJAX-first controls.
 *
 * @package Pro_Ultra_AI_WooTheme
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<div class="pro-ultra-archive" data-archive-wrapper>
<?php do_action( 'woocommerce_before_main_content' ); ?>
<header class="woocommerce-products-header">
<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
</header>
<?php do_action( 'woocommerce_before_shop_loop' ); ?>
<div class="pro-ultra-archive__list" data-archive-list>
<?php if ( woocommerce_product_loop() ) : ?>
<?php woocommerce_product_loop_start(); ?>
<?php if ( wc_get_loop_prop( 'total' ) ) : ?>
<?php while ( have_posts() ) : ?>
<?php the_post(); ?>
<?php wc_get_template_part( 'content', 'product' ); ?>
<?php endwhile; ?>
<?php endif; ?>
<?php woocommerce_product_loop_end(); ?>
<?php else : ?>
<?php do_action( 'woocommerce_no_products_found' ); ?>
<?php endif; ?>
</div>
<div class="pro-ultra-archive__pagination-wrap" data-archive-pagination>
<?php woocommerce_pagination(); ?>
</div>
<?php do_action( 'woocommerce_after_shop_loop' ); ?>
<?php do_action( 'woocommerce_after_main_content' ); ?>
</div>
<?php
get_footer( 'shop' );
