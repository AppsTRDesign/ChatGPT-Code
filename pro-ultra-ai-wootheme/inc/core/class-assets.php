<?php
namespace ProUltra\Core;

/**
 * Handles asset registration and enqueue.
 */
class Assets {
public static function register() {
wp_register_style( 'pro-ultra-main', PRO_ULTRA_AI_URI . 'style.css', array(), PRO_ULTRA_AI_VERSION );
wp_register_script( 'pro-ultra-main', PRO_ULTRA_AI_URI . 'assets/js/main.js', array( 'jquery' ), PRO_ULTRA_AI_VERSION, true );
}

public static function enqueue_frontend() {
wp_enqueue_style( 'pro-ultra-main' );
wp_enqueue_script( 'pro-ultra-main' );
wp_localize_script( 'pro-ultra-main', 'proUltraAI', array(
'ajaxUrl' => admin_url( 'admin-ajax.php' ),
'nonce'   => wp_create_nonce( 'pro-ultra-ai' ),
) );
}

public static function enqueue_admin() {
wp_enqueue_style( 'pro-ultra-main' );
wp_enqueue_script( 'pro-ultra-main' );
wp_localize_script( 'pro-ultra-main', 'proUltraAI', array(
'ajaxUrl' => admin_url( 'admin-ajax.php' ),
'nonce'   => wp_create_nonce( 'pro-ultra-ai' ),
) );
}
}
