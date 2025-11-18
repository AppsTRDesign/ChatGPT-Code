<?php
namespace ProUltra\Features;

/**
 * Demo content helper placeholder.
 */
class Demo_Content {
public static function init() {
add_action( 'after_switch_theme', array( __CLASS__, 'maybe_create_homepage' ) );
}

public static function maybe_create_homepage() {
if ( get_option( 'pro_ultra_homepage_created' ) ) {
return;
}
$page_id = wp_insert_post( array(
'post_title'   => __( 'Pro Ultra Home', 'pro-ultra-ai' ),
'post_type'    => 'page',
'post_status'  => 'publish',
'post_content' => '[pro_ultra_favorites]'
) );
if ( $page_id && ! is_wp_error( $page_id ) ) {
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_id );
update_option( 'pro_ultra_homepage_created', 1 );
}
}
}
