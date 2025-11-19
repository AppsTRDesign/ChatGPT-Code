<?php
/**
 * The Template for displaying all single products
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
exit; // Exit if accessed directly.
}

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

while ( have_posts() ) :
the_post();
wc_get_template_part( 'content', 'single-product' );
endwhile;

do_action( 'woocommerce_after_main_content' );
do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );
