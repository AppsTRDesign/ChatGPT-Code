<?php
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
$attributes = wc_get_attribute_taxonomies();
?>
<main class="ai-container">
<header class="ai-archive-header">
<h1 class="ai-section-title"><?php woocommerce_page_title(); ?></h1>
<form id="ai-filter-form" class="ai-card ai-filter" method="get">
<label>
<?php esc_html_e( 'Sırala', 'ai-commerce-pro' ); ?>
<select name="orderby">
<?php foreach ( wc_get_catalog_ordering_args() as $key => $value ) : ?>
<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_GET['orderby'] ?? '', $key ); ?>><?php echo esc_html( ucfirst( $key ) ); ?></option>
<?php endforeach; ?>
</select>
</label>
<label>
<?php esc_html_e( 'Min Fiyat', 'ai-commerce-pro' ); ?>
<input type="number" name="min_price" value="<?php echo esc_attr( $_GET['min_price'] ?? '' ); ?>" />
</label>
<label>
<?php esc_html_e( 'Max Fiyat', 'ai-commerce-pro' ); ?>
<input type="number" name="max_price" value="<?php echo esc_attr( $_GET['max_price'] ?? '' ); ?>" />
</label>
<?php foreach ( $attributes as $attribute ) : $taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name ); ?>
<label>
<?php echo esc_html( $attribute->attribute_label ); ?>
<?php wp_dropdown_categories( [
'taxonomy'        => $taxonomy,
'hide_empty'      => true,
'name'            => 'filter_' . esc_attr( $attribute->attribute_name ),
'show_option_all' => __( 'Tümü', 'ai-commerce-pro' ),
'value_field'     => 'slug',
'selected'        => $_GET[ 'filter_' . $attribute->attribute_name ] ?? '',
] ); ?>
</label>
<?php endforeach; ?>
<button class="ai-btn" type="submit"><?php esc_html_e( 'Uygula', 'ai-commerce-pro' ); ?></button>
</form>
</header>

<?php if ( woocommerce_product_loop() ) : ?>
<div class="ai-grid columns-3 ai-product-grid">
<?php while ( have_posts() ) : ?>
<?php the_post(); ?>
<?php wc_get_template_part( 'content', 'product' ); ?>
<?php endwhile; ?>
</div>
<?php woocommerce_pagination(); ?>
<?php else : ?>
<p class="ai-muted"><?php esc_html_e( 'Ürün bulunamadı.', 'ai-commerce-pro' ); ?></p>
<?php endif; ?>
</main>
<?php
get_footer( 'shop' );
