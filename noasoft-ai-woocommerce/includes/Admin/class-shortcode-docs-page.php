<?php
namespace NoaSoft\AiWoo\Admin;

/**
 * Shortcode docs page.
 */
class Shortcode_Docs_Page {
    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Register submenu page.
     */
    public function register_page() {
        add_submenu_page(
            'noasoft-ai-woo',
            __( 'Kısa Kod & Widget Rehberi', 'noasoft-ai-woocommerce' ),
            __( 'Kısa Kod & Widget Rehberi', 'noasoft-ai-woocommerce' ),
            'manage_woocommerce',
            'noasoft-ai-woo-shortcodes',
            array( $this, 'render' )
        );
    }

    /**
     * Provide structured data for the docs template.
     *
     * @return array
     */
    public static function get_view_data() {
        return array(
            'shortcodes' => array(
                array(
                    'tag'         => '[noasoft_ai_recommender]',
                    'title'       => __( 'AI Ürün Öneri Kartı', 'noasoft-ai-woocommerce' ),
                    'description' => __( 'Ziyaretçi davranışına göre artı/eksi listeleri ve ikna edici metin üretir.', 'noasoft-ai-woocommerce' ),
                    'params'      => array(
                        array(
                            'name' => 'product_id',
                            'desc' => __( 'Belirli bir ürün ID’sine odaklanmak için kullanılır (opsiyonel).', 'noasoft-ai-woocommerce' ),
                        ),
                        array(
                            'name' => 'layout',
                            'desc' => __( 'Kart düzeni: default, split veya compact.', 'noasoft-ai-woocommerce' ),
                        ),
                    ),
                    'example'     => '[noasoft_ai_recommender product_id="99" layout="split"]',
                ),
                array(
                    'tag'         => '[noasoft_ai_chat_assistant]',
                    'title'       => __( 'AI Satış Sohbet Asistanı', 'noasoft-ai-woocommerce' ),
                    'description' => __( 'Sayfa içine gömülü veya yüzen chat widget’ını render eder.', 'noasoft-ai-woocommerce' ),
                    'params'      => array(
                        array(
                            'name' => 'floating',
                            'desc' => __( '1 olarak ayarlanırsa floating versiyonu zorla açılır.', 'noasoft-ai-woocommerce' ),
                        ),
                        array(
                            'name' => 'launcher',
                            'desc' => __( 'true/false ile açılış balonunu göster/gizle.', 'noasoft-ai-woocommerce' ),
                        ),
                    ),
                    'example'     => '[noasoft_ai_chat_assistant floating="0" launcher="false"]',
                ),
                array(
                    'tag'         => '[noasoft_ai_product_compare]',
                    'title'       => __( 'AI Ürün Karşılaştırma Formu', 'noasoft-ai-woocommerce' ),
                    'description' => __( 'Kullanıcıların iki ürünü seçip AI destekli kıyas almasını sağlar.', 'noasoft-ai-woocommerce' ),
                    'params'      => array(
                        array(
                            'name' => 'product1',
                            'desc' => __( 'Varsayılan birinci ürün ID/SKU/isim değeri.', 'noasoft-ai-woocommerce' ),
                        ),
                        array(
                            'name' => 'product2',
                            'desc' => __( 'Varsayılan ikinci ürün ID/SKU/isim değeri.', 'noasoft-ai-woocommerce' ),
                        ),
                    ),
                    'example'     => '[noasoft_ai_product_compare product1="hoodie" product2="tshirt"]',
                ),
                array(
                    'tag'         => '[noasoft_ai_report_button]',
                    'title'       => __( 'AI Rapor Oluşturma Butonu', 'noasoft-ai-woocommerce' ),
                    'description' => __( 'Yetkili kullanıcıların frontend’den rapor tetiklemesine izin verir.', 'noasoft-ai-woocommerce' ),
                    'params'      => array(),
                    'example'     => '[noasoft_ai_report_button]',
                ),
            ),
            'widgets' => array(
                array(
                    'name'        => __( 'NoaSoft AI Öneri Widget', 'noasoft-ai-woocommerce' ),
                    'description' => __( 'Sidebar içinde öneri kartı gösterir. Widget ayarlarından başlık ve ürün ID seçilebilir.', 'noasoft-ai-woocommerce' ),
                ),
                array(
                    'name'        => __( 'NoaSoft AI Sohbet Widget', 'noasoft-ai-woocommerce' ),
                    'description' => __( 'Shortcode gerektirmeden chat asistanını içerik alanlarına ekler.', 'noasoft-ai-woocommerce' ),
                ),
                array(
                    'name'        => __( 'NoaSoft AI Ürün Karşılaştırma Widget', 'noasoft-ai-woocommerce' ),
                    'description' => __( 'Kullanıcıların hızlıca iki ürün kıyaslamasını sağlar.', 'noasoft-ai-woocommerce' ),
                ),
            ),
        );
    }

    /**
     * Render documentation.
     */
    public function render() {
        $shortcode_data = self::get_view_data();
        echo '<div class="wrap"><h1>' . esc_html__( 'Kısa Kod & Widget Rehberi', 'noasoft-ai-woocommerce' ) . '</h1>';
        include NOASOFT_AI_WOO_PLUGIN_DIR . 'templates/admin/shortcode-docs.php';
        echo '</div>';
    }
}
