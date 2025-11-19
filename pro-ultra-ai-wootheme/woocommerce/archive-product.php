<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<div class="pro-ultra-archive" data-archive-wrapper>
<?php do_action( 'woocommerce_before_main_content' ); ?>
<header class="woocommerce-products-header">
<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
</header>
<div class="pro-ultra-archive__grid">
<aside class="pro-ultra-archive__aside" data-archive-aside>
<button class="pro-ultra-filter__mobile-close" type="button" data-filter-close aria-label="<?php esc_attr_e( 'Filtreyi kapat', 'pro-ultra-ai' ); ?>">&times;</button>
<?php do_action( 'pro_ultra_archive_filters' ); ?>
</aside>
<section class="pro-ultra-archive__results">
<div class="pro-ultra-archive__toolbar">
<button class="button is-ghost" type="button" data-filter-open><?php esc_html_e( 'Filtreleri Göster', 'pro-ultra-ai' ); ?></button>
<?php do_action( 'woocommerce_before_shop_loop' ); ?>
</div>
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
</section>
</div>
<?php do_action( 'woocommerce_after_shop_loop' ); ?>
<?php do_action( 'woocommerce_after_main_content' ); ?>
</div>
<?php
get_footer( 'shop' );
