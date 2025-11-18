<?php get_header(); ?>
<main class="ai-container">
<section class="ai-hero">
<h1><?php bloginfo( 'name' ); ?> – <span class="ai-highlight"><?php esc_html_e( 'AI destekli alışveriş deneyimi', 'ai-commerce-pro' ); ?></span></h1>
<p><?php bloginfo( 'description' ); ?></p>
<div class="ai-flex" style="justify-content:center;">
<a class="ai-btn" href="#ai-assistant"><?php esc_html_e( 'AI Asistanını Deneyin', 'ai-commerce-pro' ); ?></a>
<a class="ai-btn secondary" href="#highlights"><?php esc_html_e( 'Öne Çıkanlar', 'ai-commerce-pro' ); ?></a>
</div>
</section>

<section id="highlights">
<h2 class="ai-section-title"><?php esc_html_e( 'AI önerileri', 'ai-commerce-pro' ); ?></h2>
<div class="ai-grid columns-3">
<?php
$products = wc_get_products( [ 'status' => 'publish', 'limit' => 6, 'orderby' => 'popularity' ] );
foreach ( $products as $product ) :
$img = wp_get_attachment_image_src( $product->get_image_id(), 'medium' );
?>
<article class="ai-card ai-product-card">
<?php if ( $img ) : ?>
<img src="<?php echo esc_url( $img[0] ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" />
<?php endif; ?>
<span class="ai-badge"><?php esc_html_e( 'AI Önerisi', 'ai-commerce-pro' ); ?></span>
<h3><?php echo esc_html( $product->get_name() ); ?></h3>
<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
<a class="ai-btn ai-ajax-cart" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"><?php esc_html_e( 'Sepete Ekle', 'ai-commerce-pro' ); ?></a>
</article>
<?php endforeach; ?>
</div>
</section>

<section id="popular">
<h2 class="ai-section-title"><?php esc_html_e( 'Popüler Kartlar', 'ai-commerce-pro' ); ?></h2>
<div class="ai-grid columns-2">
<div class="ai-card">
<h3><?php esc_html_e( 'En Çok Beğenilen', 'ai-commerce-pro' ); ?></h3>
<?php echo do_shortcode( '[aicart_popular type="likes" limit="4"]' ); ?>
</div>
<div class="ai-card">
<h3><?php esc_html_e( 'En Çok Favoriye Eklenen', 'ai-commerce-pro' ); ?></h3>
<?php echo do_shortcode( '[aicart_popular type="favorites" limit="4"]' ); ?>
</div>
<div class="ai-card">
<h3><?php esc_html_e( 'En Çok Ziyaret Edilen', 'ai-commerce-pro' ); ?></h3>
<?php echo do_shortcode( '[aicart_popular type="visits" limit="4"]' ); ?>
</div>
<div class="ai-card">
<h3><?php esc_html_e( 'AI Önerileri', 'ai-commerce-pro' ); ?></h3>
<?php echo do_shortcode( '[aicart_popular type="likes" limit="4"]' ); ?>
</div>
</div>
</section>

<section id="ai-assistant">
<h2 class="ai-section-title"><?php esc_html_e( 'Satış Asistanı', 'ai-commerce-pro' ); ?></h2>
<p><?php esc_html_e( 'İsteklerinizi yazın, ürünleri sizin için kıyaslasın, önerilerde bulunsun.', 'ai-commerce-pro' ); ?></p>
<form class="ai-card" id="ai-chat-form">
<label for="ai-chat-prompt" style="display:block;margin-bottom:6px;">Prompt</label>
<textarea id="ai-chat-prompt" name="prompt" rows="4" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);"></textarea>
<button class="ai-btn" type="submit" style="margin-top:10px;"><?php esc_html_e( 'Gönder', 'ai-commerce-pro' ); ?></button>
</form>
</section>
</main>
<?php get_footer(); ?>
