<?php
namespace NoaSoft\AiWoo\Admin;

use NoaSoft\AiWoo\Helpers\Options;
use NoaSoft\AiWoo\Helpers\Language_Helper;

/**
 * Language & translation management controller.
 */
class Language_Page {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_page' ) );
        add_action( 'wp_ajax_noasoft_ai_language_save_locale', array( $this, 'ajax_save_locale' ) );
        add_action( 'wp_ajax_noasoft_ai_language_export_po', array( $this, 'ajax_export_po' ) );
        add_action( 'wp_ajax_noasoft_ai_language_export_json', array( $this, 'ajax_export_json' ) );
        add_action( 'wp_ajax_noasoft_ai_language_import_po', array( $this, 'ajax_import_po' ) );
        add_action( 'wp_ajax_noasoft_ai_language_import_json', array( $this, 'ajax_import_json' ) );
        add_action( 'wp_ajax_noasoft_ai_language_search', array( $this, 'ajax_search' ) );
        add_action( 'wp_ajax_noasoft_ai_language_save_entry', array( $this, 'ajax_save_entry' ) );
    }

    /**
     * Register submenu page.
     *
     * @return void
     */
    public function register_page() {
        add_submenu_page(
            'noasoft-ai-woo',
            __( 'Dil & Çeviri', 'noasoft-ai-woocommerce' ),
            __( 'Dil & Çeviri', 'noasoft-ai-woocommerce' ),
            'manage_woocommerce',
            'noasoft-ai-woo-language',
            array( $this, 'render' )
        );
    }

    /**
     * Render page.
     *
     * @return void
     */
    public function render() {
        $language_view = self::get_view_data();
        ?>
        <div class="wrap noasoft-language-page">
            <h1><?php esc_html_e( 'Dil & Çeviri Yönetimi', 'noasoft-ai-woocommerce' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Eklenti dilini seçin, dil dosyalarını içe/dışa aktarın ve belirli anahtarların çevirisini hızlıca düzenleyin.', 'noasoft-ai-woocommerce' ); ?></p>
            <?php include NOASOFT_AI_WOO_PLUGIN_DIR . 'templates/admin/language-editor.php'; ?>
        </div>
        <?php
    }

    /**
     * Provide data for template reuse (settings tab also uses this).
     *
     * @return array
     */
    public static function get_view_data() {
        $settings        = Options::get_settings();
        $selected_locale = isset( $settings['plugin_locale'] ) ? $settings['plugin_locale'] : 'default';
        $select_options  = array_merge(
            array( 'default' => __( 'Varsayılan (Site Dili)', 'noasoft-ai-woocommerce' ) ),
            Language_Helper::get_locale_choices()
        );

        return array(
            'selected_locale' => $selected_locale,
            'select_options'  => $select_options,
            'locale_panels'   => Language_Helper::get_locale_stats(),
        );
    }

    /**
     * AJAX: Save plugin locale.
     */
    public function ajax_save_locale() {
        $this->verify_request();

        $locale = isset( $_POST['locale'] ) ? sanitize_text_field( wp_unslash( $_POST['locale'] ) ) : 'default';
        if ( 'default' !== $locale && ! Language_Helper::is_supported_locale( $locale ) ) {
            $locale = 'default';
        }

        Options::update_plugin_locale( $locale );
        wp_send_json_success( array( 'locale' => $locale ) );
    }

    /**
     * AJAX: Search strings.
     */
    public function ajax_search() {
        $this->verify_request();

        $locale = $this->get_locale_from_request();
        $query  = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
        $results = Language_Helper::search_catalog( $locale, $query );
        wp_send_json_success( array( 'results' => $results ) );
    }

    /**
     * AJAX: Save inline entry.
     */
    public function ajax_save_entry() {
        $this->verify_request();

        $locale = $this->get_locale_from_request();
        $key    = isset( $_POST['entry_key'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_key'] ) ) : '';
        $text   = isset( $_POST['translation'] ) ? wp_kses_post( wp_unslash( $_POST['translation'] ) ) : '';

        $original = Language_Helper::decode_key( $key );
        if ( '' === $original ) {
            wp_send_json_error( array( 'message' => __( 'Geçersiz anahtar.', 'noasoft-ai-woocommerce' ) ) );
        }

        Language_Helper::save_override( $locale, $original, $text );
        wp_send_json_success( array(
            'message'       => __( 'Çeviri kaydedildi.', 'noasoft-ai-woocommerce' ),
            'translation'   => $text,
            'has_override'  => '' !== trim( wp_strip_all_tags( $text ) ),
        ) );
    }

    /**
     * AJAX: Export PO.
     */
    public function ajax_export_po() {
        $this->verify_request();

        $locale = $this->get_locale_from_request();
        $data   = Language_Helper::generate_po_export( $locale );
        $this->stream_download( 'noasoft-ai-woocommerce-' . $locale . '.po', 'text/plain', $data );
    }

    /**
     * AJAX: Export JSON.
     */
    public function ajax_export_json() {
        $this->verify_request();

        $locale = $this->get_locale_from_request();
        $data   = Language_Helper::generate_json_export( $locale );
        $this->stream_download( 'noasoft-ai-woocommerce-' . $locale . '.json', 'application/json', $data );
    }

    /**
     * AJAX: Import PO.
     */
    public function ajax_import_po() {
        $this->verify_request();
        $locale = $this->get_locale_from_request();
        $count  = $this->handle_uploaded_file( $locale, 'po' );
        wp_send_json_success( array( 'message' => sprintf( __( '%d satır içe aktarıldı.', 'noasoft-ai-woocommerce' ), $count ) ) );
    }

    /**
     * AJAX: Import JSON.
     */
    public function ajax_import_json() {
        $this->verify_request();
        $locale = $this->get_locale_from_request();
        $count  = $this->handle_uploaded_file( $locale, 'json' );
        wp_send_json_success( array( 'message' => sprintf( __( '%d kayıt içe aktarıldı.', 'noasoft-ai-woocommerce' ), $count ) ) );
    }

    /**
     * Verify AJAX capability.
     *
     * @return void
     */
    protected function verify_request() {
        check_ajax_referer( 'noasoft_ai_language', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bu işlem için yetkiniz yok.', 'noasoft-ai-woocommerce' ) ), 403 );
        }
    }

    /**
     * Locale from request.
     *
     * @return string
     */
    protected function get_locale_from_request() {
        $locale = isset( $_REQUEST['locale'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['locale'] ) ) : 'tr_TR';
        if ( ! Language_Helper::is_supported_locale( $locale ) ) {
            wp_send_json_error( array( 'message' => __( 'Desteklenmeyen dil.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        return $locale;
    }

    /**
     * Stream download helper.
     *
     * @param string $filename Filename.
     * @param string $type     Mime type.
     * @param string $data     Contents.
     * @return void
     */
    protected function stream_download( $filename, $type, $data ) {
        nocache_headers();
        header( 'Content-Type: ' . $type . '; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
        echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Handle upload file parsing.
     *
     * @param string $locale Locale.
     * @param string $format Format key.
     * @return int
     */
    protected function handle_uploaded_file( $locale, $format ) {
        if ( empty( $_FILES['file'] ) || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Dosya alınamadı.', 'noasoft-ai-woocommerce' ) ) );
        }

        $contents = file_get_contents( $_FILES['file']['tmp_name'] );
        if ( false === $contents ) {
            wp_send_json_error( array( 'message' => __( 'Dosya okunamadı.', 'noasoft-ai-woocommerce' ) ) );
        }

        if ( 'po' === $format ) {
            return Language_Helper::import_po_content( $locale, $contents );
        }

        return Language_Helper::import_json_content( $locale, $contents );
    }
}
