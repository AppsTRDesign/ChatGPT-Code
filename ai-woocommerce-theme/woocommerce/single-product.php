<?php
defined( 'ABSPATH' ) || exit;
get_header( 'shop' );
?>
<main class="ai-container ai-single">
<?php while ( have_posts() ) : ?>
<?php the_post(); ?>
<?php global $product; ?>
<div class="ai-grid columns-2 ai-single-grid">
<div class="ai-gallery">
<?php
$ids = $product->get_gallery_image_ids();
if ( $ids ) {
echo '<div class="ai-gallery-thumbs">';
foreach ( $ids as $id ) {
echo wp_get_attachment_image( $id, 'large' );
}
echo '</div>';
} else {
echo wp_get_attachment_image( $product->get_image_id(), 'large' );
}
?>
</div>
<div class="ai-single-summary ai-card">
<h1><?php the_title(); ?></h1>
<p class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
<div class="ai-meta-row">
<button class="ai-btn ai-ajax-cart" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"><?php esc_html_e( 'Sepete Ekle', 'ai-commerce-pro' ); ?></button>
<button class="ai-fav-toggle" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"><?php esc_html_e( 'Favori', 'ai-commerce-pro' ); ?></button>
<button class="ai-fav-toggle wishlist" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"><?php esc_html_e( 'Wishlist', 'ai-commerce-pro' ); ?></button>
<button class="ai-like-toggle" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">👍</button>
</div>
<div class="ai-tabs">
<h3><?php esc_html_e( 'Ürün Açıklaması', 'ai-commerce-pro' ); ?></h3>
<div class="ai-content"><?php the_content(); ?></div>
<h3><?php esc_html_e( 'Özellikler', 'ai-commerce-pro' ); ?></h3>
<?php wc_display_product_attributes( $product ); ?>
<?php woocommerce_template_single_meta(); ?>
<h3><?php esc_html_e( 'Varyasyon Tercihleri', 'ai-commerce-pro' ); ?></h3>
<?php woocommerce_template_single_add_to_cart(); ?>
</div>
</div>
</div>

<section class="ai-card">
<h2><?php esc_html_e( 'AI Önerileri', 'ai-commerce-pro' ); ?></h2>
<?php
$meta   = get_post_meta( get_the_ID() );
$bullets = $meta['_ai_bullets'][0] ?? '';
$keywords = $meta['_ai_keywords'][0] ?? '';
if ( $bullets ) {
echo '<ul class="ai-bullets">';
foreach ( (array) $bullets as $bullet ) {
echo '<li>' . esc_html( $bullet ) . '</li>';
}
echo '</ul>';
}
if ( $keywords ) {
echo '<p class="ai-keywords">' . esc_html( $keywords ) . '</p>';
}
?>
<div class="ai-assistant-inline">
<form id="ai-inline-compare" class="ai-flex" style="gap:10px;flex-wrap:wrap;">
<input type="text" name="product_ids[]" placeholder="SKU/ID 1" />
<input type="text" name="product_ids[]" placeholder="SKU/ID 2" />
<button type="submit" class="ai-btn secondary"><?php esc_html_e( 'AI Karşılaştır', 'ai-commerce-pro' ); ?></button>
</form>
<div id="ai-compare-result" class="ai-compare-result"></div>

<form id="ai-order-lookup" class="ai-flex" style="gap:10px;flex-wrap:wrap;margin-top:12px;">
<input type="text" name="order_id" placeholder="<?php esc_attr_e( 'Sipariş No', 'ai-commerce-pro' ); ?>" />
<input type="text" name="sku" placeholder="<?php esc_attr_e( 'SKU', 'ai-commerce-pro' ); ?>" />
<button type="submit" class="ai-btn"><?php esc_html_e( 'Kargo/SKU Durumu', 'ai-commerce-pro' ); ?></button>
</form>
<div id="ai-order-result" class="ai-order-result"></div>
</div>
</section>

<section class="ai-card">
<h2><?php esc_html_e( 'Benzer Ürünler', 'ai-commerce-pro' ); ?></h2>
<?php
$related = wc_get_related_products( $product->get_id(), 4 );
echo do_shortcode( '[aicart_popular type="likes" limit="4" ids="' . implode( ',', $related ) . '"]' );
?>
</section>

<section class="ai-card">
<h2><?php esc_html_e( 'Değerlendirmeler', 'ai-commerce-pro' ); ?></h2>
<?php comments_template(); ?>
</section>
<?php endwhile; ?>
</main>
<?php
get_footer( 'shop' );
