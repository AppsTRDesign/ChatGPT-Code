<?php
namespace ProUltra\Features;

use WC_Customer;
use WP_Query;

/**
 * Extends WooCommerce My Account with advanced profile endpoints and summaries.
 */
class Advanced_Profile {
  public static function init() {
    add_action( 'init', array( __CLASS__, 'add_endpoints' ) );
    add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_items' ) );
    add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'register_query_vars' ) );
    add_action( 'woocommerce_account_favorites_endpoint', array( __CLASS__, 'render_favorites' ) );
    add_action( 'woocommerce_account_wishlist_endpoint', array( __CLASS__, 'render_wishlist' ) );
    add_action( 'woocommerce_account_likes_endpoint', array( __CLASS__, 'render_likes' ) );
    add_action( 'woocommerce_account_dashboard', array( __CLASS__, 'render_dashboard_cards' ), 25 );
    add_action( 'after_switch_theme', array( __CLASS__, 'flush_rewrite_rules' ) );
  }

  public static function add_endpoints() {
    add_rewrite_endpoint( 'favorites', EP_ROOT | EP_PAGES );
    add_rewrite_endpoint( 'wishlist', EP_ROOT | EP_PAGES );
    add_rewrite_endpoint( 'likes', EP_ROOT | EP_PAGES );
  }

  public static function register_query_vars( $vars ) {
    $vars['favorites'] = 'favorites';
    $vars['wishlist']  = 'wishlist';
    $vars['likes']     = 'likes';
    return $vars;
  }

  public static function flush_rewrite_rules() {
    flush_rewrite_rules();
  }

  public static function add_menu_items( $items ) {
    $new = array();
    foreach ( $items as $endpoint => $label ) {
      $new[ $endpoint ] = $label;
      if ( 'orders' === $endpoint ) {
        $new['favorites'] = __( 'Favorites', 'pro-ultra-ai' );
        $new['wishlist']  = __( 'Wishlist', 'pro-ultra-ai' );
        $new['likes']     = __( 'Likes', 'pro-ultra-ai' );
      }
    }
    return $new;
  }

  public static function render_favorites() {
    echo self::render_interaction_grid( 'favorite', __( 'Your Favorites', 'pro-ultra-ai' ) );
  }

  public static function render_wishlist() {
    echo self::render_interaction_grid( 'wishlist', __( 'Your Wishlist', 'pro-ultra-ai' ) );
  }

  public static function render_likes() {
    echo self::render_interaction_grid( 'like', __( 'Your Likes', 'pro-ultra-ai' ) );
  }

  private static function render_interaction_grid( $type, $title ) {
    if ( ! is_user_logged_in() ) {
      echo '<div class="pro-ultra-card"><p>' . esc_html__( 'Please log in to view this section.', 'pro-ultra-ai' ) . '</p></div>';
      return;
    }

    $ids = User_Interactions::get_user_products( get_current_user_id(), $type );
    if ( empty( $ids ) ) {
      echo '<div class="pro-ultra-card"><p>' . esc_html__( 'No products yet.', 'pro-ultra-ai' ) . '</p></div>';
      return;
    }

    $query = new WP_Query(
      array(
        'post_type'      => 'product',
        'post__in'       => $ids,
        'posts_per_page' => 12,
        'orderby'        => 'post__in',
      )
    );

    if ( ! $query->have_posts() ) {
      echo '<div class="pro-ultra-card"><p>' . esc_html__( 'No products yet.', 'pro-ultra-ai' ) . '</p></div>';
      return;
    }

    include PRO_ULTRA_AI_PATH . 'template-parts/account/interaction-grid.php';
    wp_reset_postdata();
  }

  public static function render_dashboard_cards() {
    if ( ! is_user_logged_in() ) {
      return;
    }

    $customer   = new WC_Customer( get_current_user_id() );
    $last_order = function_exists( 'wc_get_customer_last_order' ) ? wc_get_customer_last_order( $customer->get_id() ) : null;
    $fav_count  = count( User_Interactions::get_user_products( $customer->get_id(), 'favorite' ) );
    $wish_count = count( User_Interactions::get_user_products( $customer->get_id(), 'wishlist' ) );
    $like_count = count( User_Interactions::get_user_products( $customer->get_id(), 'like' ) );

    include PRO_ULTRA_AI_PATH . 'template-parts/account/dashboard-cards.php';
  }
}
