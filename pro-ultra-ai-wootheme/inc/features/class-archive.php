<?php
namespace ProUltra\Features;

use WP_Query;

/**
 * AJAX-first product archive and category filtering module with premium filter UI.
 */
class Archive_Module {
public static function init() {
add_action( 'wp_ajax_pro_ultra_filter_products', array( __CLASS__, 'ajax_filter' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_filter_products', array( __CLASS__, 'ajax_filter' ) );
add_action( 'wp_enqueue_scripts', array( __CLASS__, 'localize' ) );
add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
add_action( 'pro_ultra_archive_filters', array( __CLASS__, 'render_filters' ) );
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
if ( ! is_shop() && ! is_product_taxonomy() && ! self::is_product_search() ) {
return;
}
$categories   = get_terms(
array(
'taxonomy'   => 'product_cat',
'hide_empty' => true,
'number'     => 200,
)
);
$current_cat  = is_product_category() ? get_queried_object_id() : 0;
$filter_attrs    = array(
'pa_model',
'pa_renk',
'pa_ebat',
'pa_boyut',
'pa_materyal',
'pa_marka',
);
$available_terms = self::get_available_attribute_terms( $filter_attrs );
?>
<div class="pro-ultra-archive__sidebar" data-archive-form-wrapper>
<form class="pro-ultra-archive__filters" data-archive-form>
<div class="pro-ultra-filter__search">
<label for="pro-ultra-category-search" class="screen-reader-text"><?php esc_html_e( 'Kategori ara', 'pro-ultra-ai' ); ?></label>
<input type="search" id="pro-ultra-category-search" placeholder="<?php esc_attr_e( 'Kategori Ara', 'pro-ultra-ai' ); ?>" data-filter-search="category" />
</div>

<div class="pro-ultra-filter__section is-open" data-filter-section>
<button type="button" class="pro-ultra-filter__toggle" aria-expanded="true">
<span><?php esc_html_e( 'Kategori', 'pro-ultra-ai' ); ?></span>
<span class="pro-ultra-filter__chevron">▾</span>
</button>
<div class="pro-ultra-filter__content" data-filter-content>
<?php foreach ( $categories as $cat ) : ?>
<label class="pro-ultra-filter__item" data-filter-item="category" data-label="<?php echo esc_attr( strtolower( $cat->name ) ); ?>">
<input type="radio" name="product_cat" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( $current_cat, $cat->term_id ); ?> />
<span><?php echo esc_html( $cat->name ); ?></span>
</label>
<?php endforeach; ?>
<label class="pro-ultra-filter__item">
<input type="radio" name="product_cat" value="" <?php checked( 0, $current_cat ); ?> />
<span><?php esc_html_e( 'Tümü', 'pro-ultra-ai' ); ?></span>
</label>
</div>
</div>

<div class="pro-ultra-filter__section" data-filter-section>
<button type="button" class="pro-ultra-filter__toggle" aria-expanded="false">
<span><?php esc_html_e( 'Fiyat', 'pro-ultra-ai' ); ?></span>
<span class="pro-ultra-filter__chevron">▾</span>
</button>
<div class="pro-ultra-filter__content" data-filter-content>
<div class="pro-ultra-filter__row">
<label for="pro-ultra-price-min"><?php esc_html_e( 'Min', 'pro-ultra-ai' ); ?></label>
<input type="number" min="0" step="1" id="pro-ultra-price-min" name="price_min" />
</div>
<div class="pro-ultra-filter__row">
<label for="pro-ultra-price-max"><?php esc_html_e( 'Max', 'pro-ultra-ai' ); ?></label>
<input type="number" min="0" step="1" id="pro-ultra-price-max" name="price_max" />
</div>
</div>
</div>

<?php foreach ( $filter_attrs as $tax ) :
if ( ! taxonomy_exists( $tax ) ) {
continue;
}
$term_args = array(
'taxonomy'   => $tax,
'hide_empty' => true,
'number'     => 200,
);
if ( ! empty( $available_terms[ $tax ] ) ) {
$term_args['include'] = $available_terms[ $tax ];
}
$terms = get_terms( $term_args );
if ( empty( $terms ) || is_wp_error( $terms ) ) {
continue;
}
?>
<div class="pro-ultra-filter__section" data-filter-section>
<button type="button" class="pro-ultra-filter__toggle" aria-expanded="false">
<span><?php echo esc_html( wc_attribute_label( $tax ) ); ?></span>
<span class="pro-ultra-filter__chevron">▾</span>
</button>
<div class="pro-ultra-filter__content" data-filter-content>
<div class="pro-ultra-filter__search">
<input type="search" placeholder="<?php esc_attr_e( 'Ara', 'pro-ultra-ai' ); ?>" data-filter-search="<?php echo esc_attr( $tax ); ?>" />
</div>
<?php foreach ( $terms as $term ) : ?>
<label class="pro-ultra-filter__item" data-filter-item="<?php echo esc_attr( $tax ); ?>" data-label="<?php echo esc_attr( strtolower( $term->name ) ); ?>">
<input type="checkbox" name="attributes[<?php echo esc_attr( $tax ); ?>][]" value="<?php echo esc_attr( $term->term_id ); ?>" />
<span><?php echo esc_html( $term->name ); ?></span>
</label>
<?php endforeach; ?>
</div>
</div>
<?php endforeach; ?>

<div class="pro-ultra-filter__section" data-filter-section>
<button type="button" class="pro-ultra-filter__toggle" aria-expanded="false">
<span><?php esc_html_e( 'Öne Çıkanlar', 'pro-ultra-ai' ); ?></span>
<span class="pro-ultra-filter__chevron">▾</span>
</button>
<div class="pro-ultra-filter__content" data-filter-content>
<label class="pro-ultra-filter__switch">
<input type="checkbox" name="toggles[bulk]" value="1" />
<span><?php esc_html_e( 'Çok Al Az Öde', 'pro-ultra-ai' ); ?></span>
</label>
<label class="pro-ultra-filter__switch">
<input type="checkbox" name="toggles[influencer]" value="1" />
<span><?php esc_html_e( 'Fenomenlerin Seçtikleri', 'pro-ultra-ai' ); ?></span>
</label>
<label class="pro-ultra-filter__switch">
<input type="checkbox" name="toggles[coupon]" value="1" />
<span><?php esc_html_e( 'Kuponlu Ürünler', 'pro-ultra-ai' ); ?></span>
</label>
<label class="pro-ultra-filter__switch">
<input type="checkbox" name="toggles[corporate]" value="1" />
<span><?php esc_html_e( 'Kurumsal Faturaya Uygun', 'pro-ultra-ai' ); ?></span>
</label>
<label class="pro-ultra-filter__switch">
<input type="checkbox" name="toggles[price_history]" value="1" />
<span><?php esc_html_e( 'Fiyat Geçmişi', 'pro-ultra-ai' ); ?></span>
</label>
</div>
</div>

<div class="pro-ultra-filter__section" data-filter-section>
<button type="button" class="pro-ultra-filter__toggle" aria-expanded="false">
<span><?php esc_html_e( 'Sıralama & Görünüm', 'pro-ultra-ai' ); ?></span>
<span class="pro-ultra-filter__chevron">▾</span>
</button>
<div class="pro-ultra-filter__content" data-filter-content>
<label for="pro-ultra-orderby" class="pro-ultra-filter__label"><?php esc_html_e( 'Sırala', 'pro-ultra-ai' ); ?></label>
<select id="pro-ultra-orderby" name="orderby">
<option value="popularity"><?php esc_html_e( 'Popülerlik', 'pro-ultra-ai' ); ?></option>
<option value="rating"><?php esc_html_e( 'Puan', 'pro-ultra-ai' ); ?></option>
<option value="price"><?php esc_html_e( 'Fiyat (Artan)', 'pro-ultra-ai' ); ?></option>
<option value="price-desc"><?php esc_html_e( 'Fiyat (Azalan)', 'pro-ultra-ai' ); ?></option>
<option value="date"><?php esc_html_e( 'En Yeni', 'pro-ultra-ai' ); ?></option>
<option value="title"><?php esc_html_e( 'İsme Göre', 'pro-ultra-ai' ); ?></option>
</select>
<div class="pro-ultra-archive__views" role="group" aria-label="<?php esc_attr_e( 'Görünüm', 'pro-ultra-ai' ); ?>">
<button type="button" data-view="grid" class="button is-small is-ghost" aria-pressed="true"><?php esc_html_e( 'Grid', 'pro-ultra-ai' ); ?></button>
<button type="button" data-view="list" class="button is-small is-ghost" aria-pressed="false"><?php esc_html_e( 'Liste', 'pro-ultra-ai' ); ?></button>
</div>
</div>
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

$price_min  = isset( $_POST['price_min'] ) ? floatval( wp_unslash( $_POST['price_min'] ) ) : 0;
$price_max  = isset( $_POST['price_max'] ) ? floatval( wp_unslash( $_POST['price_max'] ) ) : 0;
$cat        = isset( $_POST['product_cat'] ) ? absint( $_POST['product_cat'] ) : 0;
$order      = isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'popularity';
$page       = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
$view       = isset( $_POST['view'] ) ? sanitize_key( wp_unslash( $_POST['view'] ) ) : 'grid';
$attributes = isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ? wp_unslash( $_POST['attributes'] ) : array();
$toggles    = isset( $_POST['toggles'] ) && is_array( $_POST['toggles'] ) ? wp_unslash( $_POST['toggles'] ) : array();

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

if ( ! empty( $attributes ) ) {
foreach ( $attributes as $tax => $terms ) {
$tax_clean = sanitize_key( $tax );
if ( ! taxonomy_exists( $tax_clean ) ) {
continue;
}
$term_ids = array_map( 'absint', (array) $terms );
if ( empty( $term_ids ) ) {
continue;
}
$args['tax_query'][] = array(
'taxonomy' => $tax_clean,
'field'    => 'term_id',
'terms'    => $term_ids,
);
}
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

if ( ! empty( $toggles ) ) {
if ( isset( $toggles['bulk'] ) ) {
$args['meta_query'][] = array(
'key'     => '_pro_ultra_bulk_discount',
'compare' => 'EXISTS',
);
}
if ( isset( $toggles['influencer'] ) ) {
$args['meta_query'][] = array(
'key'     => '_pro_ultra_influencer_pick',
'compare' => 'EXISTS',
);
}
if ( isset( $toggles['coupon'] ) ) {
$args['meta_query'][] = array(
'key'     => '_pro_ultra_coupon',
'compare' => 'EXISTS',
);
}
if ( isset( $toggles['corporate'] ) ) {
$args['meta_query'][] = array(
'key'     => '_pro_ultra_corporate_invoice',
'compare' => 'EXISTS',
);
}
if ( isset( $toggles['price_history'] ) ) {
$args['meta_query'][] = array(
'key'     => '_pro_ultra_price_history',
'compare' => 'EXISTS',
);
}
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

private static function is_product_search() {
return is_search() && ( 'product' === get_query_var( 'post_type' ) || ( isset( $_GET['post_type'] ) && 'product' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) );
}

private static function get_available_attribute_terms( $taxonomies ) {
if ( empty( $taxonomies ) || ! is_array( $taxonomies ) || ! function_exists( 'wc_get_product' ) ) {
return array();
}
global $wp_query;
if ( ! isset( $wp_query->posts ) || empty( $wp_query->posts ) ) {
return array();
}
$available = array();
foreach ( $taxonomies as $tax ) {
$available[ $tax ] = array();
}
foreach ( $wp_query->posts as $post ) {
$product = wc_get_product( $post->ID );
if ( ! $product ) {
continue;
}
foreach ( $taxonomies as $tax ) {
if ( ! taxonomy_exists( $tax ) ) {
continue;
}
$term_ids = wp_get_post_terms( $product->get_id(), $tax, array( 'fields' => 'ids' ) );
if ( ! empty( $term_ids ) && ! is_wp_error( $term_ids ) ) {
$available[ $tax ] = array_merge( $available[ $tax ], $term_ids );
}
if ( $product->is_type( 'variation' ) && $product->get_parent_id() ) {
$parent_terms = wp_get_post_terms( $product->get_parent_id(), $tax, array( 'fields' => 'ids' ) );
if ( ! empty( $parent_terms ) && ! is_wp_error( $parent_terms ) ) {
$available[ $tax ] = array_merge( $available[ $tax ], $parent_terms );
}
}
}
}
foreach ( $available as $tax => $ids ) {
$available[ $tax ] = array_unique( array_map( 'absint', $ids ) );
}
return $available;
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
