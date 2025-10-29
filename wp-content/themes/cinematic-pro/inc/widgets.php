<?php
/**
 * Widget alanları.
 */

add_action( 'widgets_init', 'cinematic_pro_widgets_init' );

/**
 * Widget alanlarını kaydeder.
 */
function cinematic_pro_widgets_init() {
register_sidebar(
[
'name'          => __( 'Ana Sidebar', 'cinematic-pro' ),
'id'            => 'sidebar-1',
'description'   => __( 'Blog yazıları için sidebar alanı.', 'cinematic-pro' ),
'before_widget' => '<section id="%1$s" class="widget %2$s">',
'after_widget'  => '</section>',
'before_title'  => '<h3 class="widget-title">',
'after_title'   => '</h3>',
]
);
}
