<?php
namespace ProUltra\AI;

use ProUltra\Admin\Theme_Options;

/**
 * AI product writer module (admin only).
 */
class Product_Writer {
        /**
         * Boot hooks.
         */
        public static function init() {
                add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
                add_action( 'save_post_product', array( __CLASS__, 'save_meta' ), 10, 2 );
                add_action( 'wp_ajax_pro_ultra_ai_product_write', array( __CLASS__, 'generate' ) );
                add_action( 'admin_enqueue_scripts', array( __CLASS__, 'localize' ) );
        }

        /**
         * Add AI writer metabox.
         */
        public static function register_metabox() {
                add_meta_box(
                        'pro-ultra-ai-writer',
                        __( 'AI Ürün İçerik Yazarı', 'pro-ultra-ai' ),
                        array( __CLASS__, 'render_metabox' ),
                        'product',
                        'side',
                        'high'
                );
        }

        /**
         * Render metabox content.
         *
         * @param \WP_Post $post Post instance.
         */
        public static function render_metabox( $post ) {
                $settings = Theme_Options::get_ai_settings();
                $features = get_post_meta( $post->ID, '_pro_ultra_ai_features', true );
                $keywords = get_post_meta( $post->ID, '_pro_ultra_ai_keywords', true );
                $benefits = get_post_meta( $post->ID, '_pro_ultra_ai_benefits', true );
                wp_nonce_field( 'pro_ultra_ai_writer_meta', 'pro_ultra_ai_writer_nonce' );
                ?>
                <p><strong><?php esc_html_e( 'AI ile doldur', 'pro-ultra-ai' ); ?></strong></p>
                <p><?php esc_html_e( 'Ürün adını girip butona tıklayın, içerikleri otomatik dolduralım.', 'pro-ultra-ai' ); ?></p>
                <p>
                        <label for="pro-ultra-ai-category"><?php esc_html_e( 'Kategori (isteğe bağlı)', 'pro-ultra-ai' ); ?></label>
                        <input type="text" id="pro-ultra-ai-category" class="widefat" placeholder="<?php esc_attr_e( 'Elektronik, Spor, Moda...', 'pro-ultra-ai' ); ?>" />
                </p>
                <p>
                        <label for="pro-ultra-ai-provider"><?php esc_html_e( 'Sağlayıcı', 'pro-ultra-ai' ); ?></label>
                        <select id="pro-ultra-ai-provider" class="widefat">
                                <option value="chatgpt" <?php selected( $settings['provider'], 'chatgpt' ); ?>><?php esc_html_e( 'ChatGPT', 'pro-ultra-ai' ); ?></option>
                                <option value="deepseek" <?php selected( $settings['provider'], 'deepseek' ); ?>><?php esc_html_e( 'DeepSeek', 'pro-ultra-ai' ); ?></option>
                        </select>
                </p>
                <p>
                        <label for="pro-ultra-ai-temperature"><?php esc_html_e( 'Yaratıcılık', 'pro-ultra-ai' ); ?></label>
                        <input type="number" min="0" max="1" step="0.1" id="pro-ultra-ai-temperature" class="widefat" value="<?php echo esc_attr( $settings['temperature'] ); ?>" />
                </p>
                <p>
                        <label for="pro-ultra-ai-max-tokens"><?php esc_html_e( 'Max tokens', 'pro-ultra-ai' ); ?></label>
                        <input type="number" min="100" max="2000" step="50" id="pro-ultra-ai-max-tokens" class="widefat" value="<?php echo esc_attr( $settings['max_tokens'] ); ?>" />
                </p>
                <p>
                        <label for="pro-ultra-ai-language"><?php esc_html_e( 'İçerik dili', 'pro-ultra-ai' ); ?></label>
                        <select id="pro-ultra-ai-language" class="widefat">
                                <option value="auto" <?php selected( $settings['language'], 'auto' ); ?>><?php esc_html_e( 'WordPress diline göre', 'pro-ultra-ai' ); ?></option>
                                <option value="tr" <?php selected( $settings['language'], 'tr' ); ?>><?php esc_html_e( 'Türkçe', 'pro-ultra-ai' ); ?></option>
                                <option value="en" <?php selected( $settings['language'], 'en' ); ?>><?php esc_html_e( 'İngilizce', 'pro-ultra-ai' ); ?></option>
                        </select>
                </p>
                <p>
                        <button type="button" class="button button-primary" id="pro-ultra-ai-generate-product" data-loading-text="<?php esc_attr_e( 'Üretiliyor...', 'pro-ultra-ai' ); ?>">
                                <?php esc_html_e( 'AI ile doldur', 'pro-ultra-ai' ); ?>
                        </button>
                </p>
                <hr />
                <p>
                        <label for="pro-ultra-ai-features"><?php esc_html_e( 'Özellikler', 'pro-ultra-ai' ); ?></label>
                        <textarea id="pro-ultra-ai-features" name="pro_ultra_ai_features" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'Özellikleri satır satır girin', 'pro-ultra-ai' ); ?>"><?php echo esc_textarea( $features ); ?></textarea>
                </p>
                <p>
                        <label for="pro-ultra-ai-keywords"><?php esc_html_e( 'AI Anahtar Kelimeler', 'pro-ultra-ai' ); ?></label>
                        <textarea id="pro-ultra-ai-keywords" name="pro_ultra_ai_keywords" rows="2" class="widefat" placeholder="kelime1, kelime2, kelime3"><?php echo esc_textarea( $keywords ); ?></textarea>
                </p>
                <p>
                        <label for="pro-ultra-ai-benefits"><?php esc_html_e( 'AI Ne İşe Yarar?', 'pro-ultra-ai' ); ?></label>
                        <textarea id="pro-ultra-ai-benefits" name="pro_ultra_ai_benefits" rows="3" class="widefat" placeholder="<?php esc_attr_e( 'Kullanıcıya sağlayacağı fayda özetleri', 'pro-ultra-ai' ); ?>"><?php echo esc_textarea( $benefits ); ?></textarea>
                </p>
        <?php
        }

        /**
         * Save AI fields.
         */
        public static function save_meta( $post_id, $post ) {
                if ( ! isset( $_POST['pro_ultra_ai_writer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pro_ultra_ai_writer_nonce'] ) ), 'pro_ultra_ai_writer_meta' ) ) {
                        return;
                }

                if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                        return;
                }

                if ( 'product' !== $post->post_type ) {
                        return;
                }

                if ( ! current_user_can( 'edit_product', $post_id ) ) {
                        return;
                }

                $map = array(
                        '_pro_ultra_ai_features' => 'pro_ultra_ai_features',
                        '_pro_ultra_ai_keywords' => 'pro_ultra_ai_keywords',
                        '_pro_ultra_ai_benefits' => 'pro_ultra_ai_benefits',
                );

                foreach ( $map as $meta_key => $field ) {
                        if ( isset( $_POST[ $field ] ) ) {
                                $value = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
                                update_post_meta( $post_id, $meta_key, $value );
                        }
                }
        }

        /**
         * Localize admin script only on product screen.
         */
        public static function localize( $hook ) {
                $screen = get_current_screen();
                if ( ! $screen || 'product' !== $screen->post_type ) {
                        return;
                }

                $settings = Theme_Options::get_ai_settings();

                wp_localize_script(
                        'pro-ultra-main',
                        'proUltraAIWriter',
                        array(
                                'nonce'        => wp_create_nonce( 'pro-ultra-ai' ),
                                'defaultTemp'  => $settings['temperature'],
                                'defaultMax'   => $settings['max_tokens'],
                                'defaultLang'  => $settings['language'],
                                'defaultProv'  => $settings['provider'],
                                'successTitle' => __( 'AI içerik oluşturuldu.', 'pro-ultra-ai' ),
                                'errorTitle'   => __( 'AI isteği başarısız.', 'pro-ultra-ai' ),
                        )
                );
        }

        /**
         * AJAX handler: generate product content.
         */
        public static function generate() {
                check_ajax_referer( 'pro-ultra-ai', 'security' );

                if ( ! current_user_can( 'edit_products' ) ) {
                        wp_send_json_error( array( 'message' => __( 'İzin yok.', 'pro-ultra-ai' ) ) );
                }

                $settings    = Theme_Options::get_ai_settings();
                $title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
                $category    = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
                $provider    = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : $settings['provider'];
                $temperature = isset( $_POST['temperature'] ) ? (float) wp_unslash( $_POST['temperature'] ) : $settings['temperature'];
                $max_tokens  = isset( $_POST['max_tokens'] ) ? (int) wp_unslash( $_POST['max_tokens'] ) : $settings['max_tokens'];
                $language    = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : $settings['language'];

                if ( empty( $title ) ) {
                        wp_send_json_error( array( 'message' => __( 'Ürün adı girin.', 'pro-ultra-ai' ) ) );
                }

                $prompt   = self::build_prompt( $title, $category, $language );
                $response = self::call_provider( $provider, $prompt, $temperature, $max_tokens );

                if ( is_wp_error( $response ) ) {
                        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
                }

                $payload = self::normalize_payload( $response );

                if ( empty( $payload ) ) {
                        wp_send_json_error( array( 'message' => __( 'Geçerli AI yanıtı alınamadı.', 'pro-ultra-ai' ) ) );
                }

                wp_send_json_success(
                        array(
                                'message' => __( 'AI içerik hazır.', 'pro-ultra-ai' ),
                                'payload' => $payload,
                        )
                );
        }

        /**
         * Build instruction prompt.
         */
        protected static function build_prompt( $title, $category, $language_setting ) {
                $locale       = get_locale();
                $language_map = array(
                        'tr'   => 'Turkish',
                        'en'   => 'English',
                        'auto' => ( 'tr_TR' === $locale || 'tr' === $locale ) ? 'Turkish' : 'English',
                );
                $language     = isset( $language_map[ $language_setting ] ) ? $language_map[ $language_setting ] : $language_map['auto'];
                $category_text = $category ? sprintf( 'Category: %s.', $category ) : '';

                return sprintf(
                        'You are an ecommerce copywriter. Write JSON with keys: seo_baslik, seo_meta_aciklama, kisa_aciklama, uzun_aciklama, urun_ozellikleri (array), anahtar_kelimeler (array), ne_ise_yarar_metin. Language: %s. Product name: %s. %s Keep tone persuasive and concise. Return only JSON.',
                        $language,
                        sanitize_text_field( $title ),
                        $category_text
                );
        }

        /**
         * Call remote provider.
         */
        protected static function call_provider( $provider, $prompt, $temperature, $max_tokens ) {
                $api_key = self::get_api_key( $provider );

                if ( empty( $api_key ) ) {
                        return new \WP_Error( 'missing_key', __( 'API anahtarı eksik. Tema ayarlarını kontrol edin.', 'pro-ultra-ai' ) );
                }

                $max_tokens = max( 100, min( 2000, (int) $max_tokens ) );
                $body       = array();
                $headers    = array();

                switch ( $provider ) {
                        case 'deepseek':
                                $endpoint = 'https://api.deepseek.com/chat/completions';
                                $body     = array(
                                        'model'       => 'deepseek-chat',
                                        'messages'    => array(
                                                array( 'role' => 'system', 'content' => 'Ecommerce AI writer' ),
                                                array( 'role' => 'user', 'content' => $prompt ),
                                        ),
                                        'temperature' => max( 0, min( 1, $temperature ) ),
                                        'max_tokens'  => $max_tokens,
                                );
                                $headers  = array(
                                        'Content-Type'  => 'application/json',
                                        'Authorization' => 'Bearer ' . $api_key,
                                );
                                break;
                        case 'chatgpt':
                        default:
                                $endpoint = 'https://api.openai.com/v1/chat/completions';
                                $body     = array(
                                        'model'       => 'gpt-4o-mini',
                                        'messages'    => array(
                                                array( 'role' => 'system', 'content' => 'Ecommerce AI writer' ),
                                                array( 'role' => 'user', 'content' => $prompt ),
                                        ),
                                        'temperature' => max( 0, min( 1, $temperature ) ),
                                        'max_tokens'  => $max_tokens,
                                );
                                $headers  = array(
                                        'Content-Type'  => 'application/json',
                                        'Authorization' => 'Bearer ' . $api_key,
                                );
                                break;
                }

                $args = array(
                        'headers' => $headers,
                        'body'    => wp_json_encode( $body ),
                        'timeout' => 30,
                );

                $response = wp_remote_post( $endpoint, $args );

                if ( is_wp_error( $response ) ) {
                        return $response;
                }

                $code = (int) wp_remote_retrieve_response_code( $response );
                if ( 200 !== $code ) {
                        $message = wp_remote_retrieve_body( $response );
                        return new \WP_Error( 'ai_http_error', sprintf( __( 'API hatası: %s', 'pro-ultra-ai' ), sanitize_text_field( $message ) ) );
                }

                $body = wp_remote_retrieve_body( $response );
                $data = json_decode( $body, true );

                if ( ! $data ) {
                        return new \WP_Error( 'ai_parse_error', __( 'API yanıtı çözümlenemedi.', 'pro-ultra-ai' ) );
                }

                return $data;
        }

        /**
         * Parse API response into normalized payload.
         */
        protected static function normalize_payload( $data ) {
                // OpenAI/DeepSeek chat format.
                if ( isset( $data['choices'][0]['message']['content'] ) ) {
                        $raw     = self::clean_json_content( $data['choices'][0]['message']['content'] );
                        $decoded = json_decode( $raw, true );
                        if ( is_array( $decoded ) ) {
                                return self::hydrate_payload( $decoded );
                        }
                }

                // If already structured.
                if ( isset( $data['seo_baslik'] ) ) {
                        return self::hydrate_payload( $data );
                }

                return array(
                        'seo_baslik'         => __( 'AI SEO Başlığı', 'pro-ultra-ai' ),
                        'seo_meta_aciklama'  => __( 'Ürün için SEO meta açıklaması.', 'pro-ultra-ai' ),
                        'kisa_aciklama'      => __( 'Bu ürün için AI tabanlı kısa açıklama örneği.', 'pro-ultra-ai' ),
                        'uzun_aciklama'      => __( 'AI uzun açıklama örneği: Ürününüzün öne çıkan faydalarını ve özelliklerini vurgulayın.', 'pro-ultra-ai' ),
                        'urun_ozellikleri'   => array( __( 'Yüksek kalite', 'pro-ultra-ai' ), __( 'Hızlı teslimat', 'pro-ultra-ai' ) ),
                        'anahtar_kelimeler'  => array( __( 'ai', 'pro-ultra-ai' ), __( 'e-ticaret', 'pro-ultra-ai' ) ),
                        'ne_ise_yarar_metin' => __( 'Dönüşüm oranını artırmak için tasarlanmış AI metni.', 'pro-ultra-ai' ),
                );
        }

        /**
         * Ensure payload keys exist.
         */
        protected static function hydrate_payload( $raw ) {
                return array(
                        'seo_baslik'         => isset( $raw['seo_baslik'] ) ? sanitize_text_field( $raw['seo_baslik'] ) : '',
                        'seo_meta_aciklama'  => isset( $raw['seo_meta_aciklama'] ) ? wp_kses_post( $raw['seo_meta_aciklama'] ) : '',
                        'kisa_aciklama'      => isset( $raw['kisa_aciklama'] ) ? wp_kses_post( $raw['kisa_aciklama'] ) : '',
                        'uzun_aciklama'      => isset( $raw['uzun_aciklama'] ) ? wp_kses_post( $raw['uzun_aciklama'] ) : '',
                        'urun_ozellikleri'   => isset( $raw['urun_ozellikleri'] ) && is_array( $raw['urun_ozellikleri'] ) ? array_map( 'sanitize_text_field', $raw['urun_ozellikleri'] ) : array(),
                        'anahtar_kelimeler'  => isset( $raw['anahtar_kelimeler'] ) && is_array( $raw['anahtar_kelimeler'] ) ? array_map( 'sanitize_text_field', $raw['anahtar_kelimeler'] ) : array(),
                        'ne_ise_yarar_metin' => isset( $raw['ne_ise_yarar_metin'] ) ? wp_kses_post( $raw['ne_ise_yarar_metin'] ) : '',
                );
        }

        /**
         * Clean possible fenced code blocks from provider responses.
         */
        protected static function clean_json_content( $content ) {
                $content = trim( wp_kses_post( $content ) );
                $content = preg_replace( '/^```json/mi', '', $content );
                $content = preg_replace( '/^```/mi', '', $content );
                $content = preg_replace( '/```$/m', '', $content );
                return trim( $content );
        }

        /**
         * Retrieve API key by provider.
         */
public static function get_api_key( $provider ) {
                $options = Theme_Options::get_ai_settings();

                switch ( $provider ) {
                        case 'deepseek':
                                return isset( $options['deepseek_key'] ) ? sanitize_text_field( $options['deepseek_key'] ) : '';
                        case 'chatgpt':
                        default:
                                return isset( $options['chatgpt_key'] ) ? sanitize_text_field( $options['chatgpt_key'] ) : '';
                }
        }
}
