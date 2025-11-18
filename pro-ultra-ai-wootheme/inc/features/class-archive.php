<?php
namespace ProUltra\Features;

use WP_Query;

/**
 * AJAX-first product archive and category filtering module.
 */
class Archive_Module {
public static function init() {
add_action( 'wp_ajax_pro_ultra_filter_products', array( __CLASS__, 'ajax_filter' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_filter_products', array( __CLASS__, 'ajax_filter' ) );
add_action( 'wp_enqueue_scripts', array( __CLASS__, 'localize' ) );
add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'render_filters' ), 5 );
add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
}

public static function body_class( $classes ) {
$view      = isset( $_COOKIE['pro_ultra_view'] ) ? sanitize_key( wp_unslash( $_COOKIE['pro_ultra_view'] ) ) : 'grid';
$classes[] = 'pro-ultra-view-' . ( in_array( $view, array( 'grid', 'list' ), true ) ? $view : 'grid' );
return $classes;
}

public static function localize() {
wp_localize_script(
'pro-ultra-main',
'proUltraArchive',
array(
'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
'nonce'     => wp_create_nonce( 'pro-ultra-ai' ),
'view'      => isset( $_COOKIE['pro_ultra_view'] ) ? sanitize_key( wp_unslash( $_COOKIE['pro_ultra_view'] ) ) : 'grid',
'loading'   => __( 'Yükleniyor...', 'pro-ultra-ai' ),
'errorText' => __( 'Ürünler getirilirken hata oluştu.', 'pro-ultra-ai' ),
)
);
}

public static function render_filters() {
if ( ! is_shop() && ! is_product_taxonomy() ) {
return;
}
$categories  = get_terms(
array(
'taxonomy'   => 'product_cat',
'hide_empty' => true,
'number'     => 50,
)
);
$attributes  = wc_get_attribute_taxonomies();
$current_cat = is_product_category() ? get_queried_object_id() : 0;
?>
<div class="pro-ultra-archive__bar">
<form class="pro-ultra-archive__filters" data-archive-form>
<div class="pro-ultra-archive__group">
<label for="pro-ultra-price-min"><?php echo esc_html__( 'Min Fiyat', 'pro-ultra-ai' ); ?></label>
<input type="number" min="0" step="1" id="pro-ultra-price-min" name="price_min" />
</div>
<div class="pro-ultra-archive__group">
<label for="pro-ultra-price-max"><?php echo esc_html__( 'Max Fiyat', 'pro-ultra-ai' ); ?></label>
<input type="number" min="0" step="1" id="pro-ultra-price-max" name="price_max" />
</div>
<div class="pro-ultra-archive__group">
<label for="pro-ultra-category"><?php echo esc_html__( 'Kategori', 'pro-ultra-ai' ); ?></label>
<select id="pro-ultra-category" name="product_cat">
<option value=""><?php esc_html_e( 'Tümü', 'pro-ultra-ai' ); ?></option>
<?php foreach ( $categories as $cat ) : ?>
<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $current_cat, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
<?php endforeach; ?>
</select>
</div>
<?php if ( $attributes ) : ?>
<div class="pro-ultra-archive__group">
<label for="pro-ultra-attribute"><?php echo esc_html__( 'Varyasyon', 'pro-ultra-ai' ); ?></label>
<select id="pro-ultra-attribute" name="attribute_term">
<option value=""><?php esc_html_e( 'Seçiniz', 'pro-ultra-ai' ); ?></option>
<?php foreach ( $attributes as $attribute ) :
$tax  = wc_attribute_taxonomy_name( $attribute->attribute_name );
$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true, 'number' => 50 ) );
foreach ( $terms as $term ) : ?>
<option value="<?php echo esc_attr( $term->term_id ); ?>" data-taxonomy="<?php echo esc_attr( $tax ); ?>"><?php echo esc_html( $term->name ); ?></option>
<?php endforeach; endforeach; ?>
</select>
</div>
<?php endif; ?>
<div class="pro-ultra-archive__group">
<label for="pro-ultra-orderby"><?php esc_html_e( 'Sırala', 'pro-ultra-ai' ); ?></label>
<select id="pro-ultra-orderby" name="orderby">
<option value="popularity"><?php esc_html_e( 'Popülerlik', 'pro-ultra-ai' ); ?></option>
<option value="rating"><?php esc_html_e( 'Puan', 'pro-ultra-ai' ); ?></option>
<option value="price"><?php esc_html_e( 'Fiyat (Artan)', 'pro-ultra-ai' ); ?></option>
<option value="price-desc"><?php esc_html_e( 'Fiyat (Azalan)', 'pro-ultra-ai' ); ?></option>
<option value="date"><?php esc_html_e( 'En Yeni', 'pro-ultra-ai' ); ?></option>
<option value="title"><?php esc_html_e( 'İsme Göre', 'pro-ultra-ai' ); ?></option>
</select>
</div>
<div class="pro-ultra-archive__group pro-ultra-archive__views" role="group" aria-label="<?php esc_attr_e( 'Görünüm', 'pro-ultra-ai' ); ?>">
<button type="button" data-view="grid" class="button is-small is-ghost" aria-pressed="true"><?php esc_html_e( 'Grid', 'pro-ultra-ai' ); ?></button>
<button type="button" data-view="list" class="button is-small is-ghost" aria-pressed="false"><?php esc_html_e( 'Liste', 'pro-ultra-ai' ); ?></button>
</div>
<input type="hidden" name="view" value="<?php echo isset( $_COOKIE['pro_ultra_view'] ) ? esc_attr( sanitize_key( wp_unslash( $_COOKIE['pro_ultra_view'] ) ) ) : 'grid'; ?>" />
<input type="hidden" name="action" value="pro_ultra_filter_products" />
<input type="hidden" name="page" value="1" />
<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'pro-ultra-ai' ) ); ?>" />
</form>
</div>
<div class="pro-ultra-archive__spinner" data-archive-loading aria-hidden="true">
<span class="spinner"></span>
<?php esc_html_e( 'Yükleniyor...', 'pro-ultra-ai' ); ?>
</div>
<?php
}

public static function ajax_filter() {
check_ajax_referer( 'pro-ultra-ai', 'security' );

$price_min      = isset( $_POST['price_min'] ) ? floatval( wp_unslash( $_POST['price_min'] ) ) : 0;
$price_max      = isset( $_POST['price_max'] ) ? floatval( wp_unslash( $_POST['price_max'] ) ) : 0;
$cat            = isset( $_POST['product_cat'] ) ? absint( $_POST['product_cat'] ) : 0;
$order          = isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'popularity';
$page           = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
$view           = isset( $_POST['view'] ) ? sanitize_key( wp_unslash( $_POST['view'] ) ) : 'grid';
$attribute_term = isset( $_POST['attribute_term'] ) ? absint( $_POST['attribute_term'] ) : 0;
$attribute_tax  = isset( $_POST['attribute_tax'] ) ? sanitize_key( wp_unslash( $_POST['attribute_tax'] ) ) : '';

$args = array(
'post_type'      => 'product',
'posts_per_page' => 12,
'paged'          => max( 1, $page ),
'tax_query'      => array(),
'meta_query'     => array(),
);

if ( $cat ) {
$args['tax_query'][] = array(
'taxonomy' => 'product_cat',
'field'    => 'term_id',
'terms'    => array( $cat ),
);
}

if ( $attribute_term && $attribute_tax ) {
$args['tax_query'][] = array(
'taxonomy' => $attribute_tax,
'field'    => 'term_id',
'terms'    => array( $attribute_term ),
);
}

if ( $price_min || $price_max ) {
$range = array();
if ( $price_min ) {
$range['min'] = $price_min;
}
if ( $price_max ) {
$range['max'] = $price_max;
}
$args['meta_query'][] = wc_get_min_max_price_meta_query( $range );
}

switch ( $order ) {
case 'price':
$args['orderby']  = 'meta_value_num';
$args['meta_key'] = '_price';
$args['order']    = 'ASC';
break;
case 'price-desc':
$args['orderby']  = 'meta_value_num';
$args['meta_key'] = '_price';
$args['order']    = 'DESC';
break;
case 'rating':
$args['meta_key'] = '_wc_average_rating';
$args['orderby']  = array(
'meta_value_num' => 'DESC',
'menu_order'     => 'ASC',
);
break;
case 'date':
$args['orderby'] = 'date';
$args['order']   = 'DESC';
break;
case 'title':
$args['orderby'] = 'title';
$args['order']   = 'ASC';
break;
case 'popularity':
default:
$args['meta_key'] = 'total_sales';
$args['orderby']  = 'meta_value_num';
$args['order']    = 'DESC';
break;
}

$query = new WP_Query( $args );
ob_start();
if ( $query->have_posts() ) {
woocommerce_product_loop_start();
while ( $query->have_posts() ) {
$query->the_post();
wc_get_template_part( 'content', 'product' );
}
woocommerce_product_loop_end();
} else {
wc_no_products_found();
}
wp_reset_postdata();
$html = ob_get_clean();

$pagination = paginate_links(
array(
'base'      => '%_%',
'format'    => '',
'current'   => max( 1, $page ),
'total'     => max( 1, $query->max_num_pages ),
'type'      => 'array',
'prev_text' => '&laquo;',
'next_text' => '&raquo;',
)
);

wp_send_json_success(
array(
'html'       => $html,
'pagination' => self::render_pagination( $pagination ),
'view'       => in_array( $view, array( 'grid', 'list' ), true ) ? $view : 'grid',
)
);
}

private static function render_pagination( $links ) {
if ( empty( $links ) || ! is_array( $links ) ) {
return '';
}
$markup = '<ul class="pro-ultra-archive__pagination">';
foreach ( $links as $link ) {
$markup .= '<li>' . $link . '</li>';
}
$markup .= '</ul>';
return $markup;
}
}
