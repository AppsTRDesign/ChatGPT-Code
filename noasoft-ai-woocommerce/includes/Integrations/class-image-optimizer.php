<?php
namespace NoaSoft\AiWoo\Integrations;

use NoaSoft\AiWoo\Helpers\Options;
use WP_Error;

/**
 * Image optimizer module.
 */
class Image_Optimizer {
    /**
     * Module enabled flag.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Media settings.
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Constructor.
     */
    public function __construct() {
        $this->settings = Options::get_media_settings();
        $this->enabled  = Options::is_module_enabled( 'image_optimizer' );

        add_action( 'wp_ajax_noasoft_ai_optimize_image', array( $this, 'ajax_optimize_image' ) );

        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        }
    }

    /**
     * Enqueue admin assets on product edit screens.
     *
     * @param string $hook Current hook.
     * @return void
     */
    public function enqueue_assets( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( empty( $screen ) || 'product' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'noasoft-ai-image-optimizer',
            NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/admin-image-optimizer.js',
            array( 'jquery' ),
            NOASOFT_AI_WOO_VERSION,
            true
        );

        wp_localize_script(
            'noasoft-ai-image-optimizer',
            'NoaSoftImageOptimizer',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'noasoft_ai_admin' ),
                'enabled'  => (bool) $this->enabled,
                'strings'  => array(
                    'button'   => __( 'AI ile Arka Planı Kaldır + WebP', 'noasoft-ai-woocommerce' ),
                    'missing'  => __( 'Lütfen önce ürün için bir öne çıkan görsel seçin.', 'noasoft-ai-woocommerce' ),
                    'disabled' => __( 'Görsel modülü devre dışı. Ayarlardan etkinleştirin.', 'noasoft-ai-woocommerce' ),
                    'success'  => __( 'Görsel başarıyla optimize edildi.', 'noasoft-ai-woocommerce' ),
                    'error'    => __( 'Görsel işlenemedi. Lütfen tekrar deneyin.', 'noasoft-ai-woocommerce' ),
                ),
            )
        );
    }

    /**
     * AJAX handler for image optimization.
     *
     * @return void
     */
    public function ajax_optimize_image() {
        check_ajax_referer( 'noasoft_ai_admin', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bu işlem için yetkiniz yok.', 'noasoft-ai-woocommerce' ) ), 403 );
        }

        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Modül devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        $post_id       = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $attachment_id ) {
            wp_send_json_error( array( 'message' => __( 'Geçerli bir görsel bulunamadı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $new_attachment_id = $this->process_attachment( $attachment_id );
        if ( is_wp_error( $new_attachment_id ) ) {
            wp_send_json_error( array( 'message' => $new_attachment_id->get_error_message() ), 500 );
        }

        if ( $post_id ) {
            set_post_thumbnail( $post_id, $new_attachment_id );
        }

        wp_send_json_success(
            array(
                'attachment_id' => $new_attachment_id,
                'url'           => wp_get_attachment_url( $new_attachment_id ),
                'message'       => __( 'Görsel optimize edildi.', 'noasoft-ai-woocommerce' ),
            )
        );
    }

    /**
     * Process attachment.
     *
     * @param int $attachment_id Attachment ID.
     * @return int|WP_Error
     */
    public function process_attachment( $attachment_id ) {
        $file_path = get_attached_file( $attachment_id );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            return new WP_Error( 'noasoft_ai_missing_file', __( 'Görsel dosyası bulunamadı.', 'noasoft-ai-woocommerce' ) );
        }

        $processed_path = $this->run_background_removal( $file_path );
        if ( is_wp_error( $processed_path ) ) {
            return $processed_path;
        }

        $final_path = $processed_path;
        if ( ! empty( $this->settings['auto_webp'] ) ) {
            $webp_path = $this->convert_to_webp( $processed_path );
            if ( ! is_wp_error( $webp_path ) ) {
                $final_path = $webp_path;
            }
        }

        $new_attachment_id = $this->import_processed_file( $final_path, $attachment_id );

        if ( file_exists( $processed_path ) && $processed_path !== $file_path ) {
            @unlink( $processed_path );
        }
        if ( file_exists( $final_path ) && $final_path !== $processed_path && $final_path !== $file_path ) {
            @unlink( $final_path );
        }

        return $new_attachment_id;
    }

    /**
     * Call remote API or simulate background removal.
     *
     * @param string $file_path Original file path.
     * @return string|WP_Error Temporary file path.
     */
    protected function run_background_removal( $file_path ) {
        $temp_file = wp_tempnam( wp_basename( $file_path ) );
        if ( ! $temp_file ) {
            return new WP_Error( 'noasoft_ai_temp', __( 'Geçici dosya oluşturulamadı.', 'noasoft-ai-woocommerce' ) );
        }

        $endpoint = isset( $this->settings['api_endpoint'] ) ? trim( $this->settings['api_endpoint'] ) : '';
        if ( empty( $endpoint ) ) {
            if ( ! copy( $file_path, $temp_file ) ) {
                return new WP_Error( 'noasoft_ai_copy', __( 'Dosya kopyalanamadı.', 'noasoft-ai-woocommerce' ) );
            }
            return $temp_file;
        }

        $file_contents = file_get_contents( $file_path );
        if ( false === $file_contents ) {
            return new WP_Error( 'noasoft_ai_read', __( 'Görsel okunamadı.', 'noasoft-ai-woocommerce' ) );
        }

        $mime      = $this->detect_mime_type( $file_path );
        $payload   = array(
            'image'   => 'data:' . $mime . ';base64,' . base64_encode( $file_contents ),
            'options' => array(
                'remove_background' => true,
            ),
        );
        $headers   = array(
            'Content-Type' => 'application/json',
        );
        if ( ! empty( $this->settings['api_key'] ) ) {
            $headers['Authorization'] = 'Bearer ' . $this->settings['api_key'];
        }

        $response = wp_remote_post(
            $endpoint,
            array(
                'body'      => wp_json_encode( $payload ),
                'headers'   => $headers,
                'timeout'   => 45,
                'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error( 'noasoft_ai_http', __( 'API yanıtı başarısız oldu.', 'noasoft-ai-woocommerce' ) );
        }

        $body         = wp_remote_retrieve_body( $response );
        $image_binary = $this->extract_image_binary( $body );
        if ( empty( $image_binary ) ) {
            return new WP_Error( 'noasoft_ai_body', __( 'API görsel verisi döndürmedi.', 'noasoft-ai-woocommerce' ) );
        }

        $written = file_put_contents( $temp_file, $image_binary );
        if ( false === $written ) {
            return new WP_Error( 'noasoft_ai_write', __( 'Görsel kaydedilemedi.', 'noasoft-ai-woocommerce' ) );
        }

        return $temp_file;
    }

    /**
     * Convert processed file to WebP if needed.
     *
     * @param string $file_path Path.
     * @return string|WP_Error
     */
    protected function convert_to_webp( $file_path ) {
        if ( empty( $this->settings['auto_webp'] ) ) {
            return $file_path;
        }

        if ( ! function_exists( 'wp_get_image_editor' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $editor = wp_get_image_editor( $file_path );
        if ( is_wp_error( $editor ) ) {
            return $file_path;
        }

        $quality = isset( $this->settings['webp_quality'] ) ? max( 10, min( 100, absint( $this->settings['webp_quality'] ) ) ) : 85;
        if ( method_exists( $editor, 'set_quality' ) ) {
            $editor->set_quality( $quality );
        }

        $saved = $editor->save( null, 'image/webp' );
        if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
            return $file_path;
        }

        return $saved['path'];
    }

    /**
     * Save the optimized file as a new attachment.
     *
     * @param string $file_path Temp path.
     * @param int    $original_id Original attachment ID.
     * @return int|WP_Error
     */
    protected function import_processed_file( $file_path, $original_id ) {
        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['error'] ) ) {
            return new WP_Error( 'noasoft_ai_upload_dir', $upload_dir['error'] );
        }

        $extension = pathinfo( $file_path, PATHINFO_EXTENSION );
        if ( empty( $extension ) ) {
            $extension = 'png';
        }

        if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
            return new WP_Error( 'noasoft_ai_directory', __( 'Yükleme klasörü oluşturulamadı.', 'noasoft-ai-woocommerce' ) );
        }

        $filename = wp_unique_filename( $upload_dir['path'], 'noasoft-ai-' . uniqid() . '.' . $extension );
        $new_path = trailingslashit( $upload_dir['path'] ) . $filename;

        if ( ! copy( $file_path, $new_path ) ) {
            return new WP_Error( 'noasoft_ai_copy_final', __( 'İşlenen dosya kaydedilemedi.', 'noasoft-ai-woocommerce' ) );
        }

        $filetype = wp_check_filetype( $filename, null );

        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_status'    => 'inherit',
            'post_parent'    => absint( get_post_field( 'post_parent', $original_id ) ),
        );

        $attach_id = wp_insert_attachment( $attachment, $new_path );
        if ( is_wp_error( $attach_id ) ) {
            return $attach_id;
        }

        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $metadata = wp_generate_attachment_metadata( $attach_id, $new_path );
        if ( $metadata ) {
            wp_update_attachment_metadata( $attach_id, $metadata );
        }

        return $attach_id;
    }

    /**
     * Determine mime type from path.
     *
     * @param string $file_path Path.
     * @return string
     */
    protected function detect_mime_type( $file_path ) {
        $check = wp_check_filetype( $file_path );
        if ( $check && ! empty( $check['type'] ) ) {
            return $check['type'];
        }

        return 'image/png';
    }

    /**
     * Extract binary from API response.
     *
     * @param string $body Body string.
     * @return string|null
     */
    protected function extract_image_binary( $body ) {
        $decoded = json_decode( $body, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $candidate = '';
            if ( ! empty( $decoded['image'] ) ) {
                $candidate = $decoded['image'];
            } elseif ( ! empty( $decoded['image_base64'] ) ) {
                $candidate = $decoded['image_base64'];
            } elseif ( ! empty( $decoded['data'] ) ) {
                $candidate = $decoded['data'];
            }

            if ( $candidate ) {
                $candidate = $this->strip_data_prefix( $candidate );
                $binary    = base64_decode( $candidate, true );
                if ( false !== $binary ) {
                    return $binary;
                }
            }

            return null;
        }

        return $body ? $body : null;
    }

    /**
     * Remove data URI prefix.
     *
     * @param string $value Value.
     * @return string
     */
    protected function strip_data_prefix( $value ) {
        $pos = strpos( $value, 'base64,' );
        if ( false !== $pos ) {
            return substr( $value, $pos + 7 );
        }

        return $value;
    }
}
