<?php
/**
 * Customizer entegrasyonları.
 */

add_action( 'customize_register', 'cinematic_pro_customize_register' );

/**
 * Customizer alanları.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function cinematic_pro_customize_register( $wp_customize ) {
$wp_customize->add_section(
'cinematic_pro_appearance',
[
'title'       => __( 'Cinematic Pro Ayarları', 'cinematic-pro' ),
'priority'    => 30,
'description' => __( 'Tema görünümü ve kahraman alanı ayarları.', 'cinematic-pro' ),
]
);

$wp_customize->add_setting(
'cinematic_pro_options[accent_color]',
[
'default'           => '#ff3d71',
'type'              => 'option',
'capability'        => 'manage_options',
'transport'         => 'postMessage',
'sanitize_callback' => 'sanitize_hex_color',
]
);

$wp_customize->add_control(
new WP_Customize_Color_Control(
$wp_customize,
'cinematic_pro_accent_color',
[
'label'    => __( 'Tema Vurgu Rengi', 'cinematic-pro' ),
'section'  => 'cinematic_pro_appearance',
'settings' => 'cinematic_pro_options[accent_color]',
]
)
);

$wp_customize->add_setting(
'cinematic_pro_options[hero_title]',
[
'default'           => __( 'Cinematic Pro ile keşfet', 'cinematic-pro' ),
'type'              => 'option',
'capability'        => 'manage_options',
'transport'         => 'postMessage',
'sanitize_callback' => 'sanitize_text_field',
]
);

$wp_customize->add_control(
'cinematic_pro_hero_title',
[
'label'    => __( 'Hero Başlığı', 'cinematic-pro' ),
'section'  => 'cinematic_pro_appearance',
'settings' => 'cinematic_pro_options[hero_title]',
'type'     => 'text',
]
);

$wp_customize->add_setting(
'cinematic_pro_options[hero_subtitle]',
[
'default'           => __( 'En yeni film ve diziler burada.', 'cinematic-pro' ),
'type'              => 'option',
'capability'        => 'manage_options',
'transport'         => 'postMessage',
'sanitize_callback' => 'sanitize_text_field',
]
);

$wp_customize->add_control(
'cinematic_pro_hero_subtitle',
[
'label'    => __( 'Hero Alt Başlığı', 'cinematic-pro' ),
'section'  => 'cinematic_pro_appearance',
'settings' => 'cinematic_pro_options[hero_subtitle]',
'type'     => 'text',
]
);
}
