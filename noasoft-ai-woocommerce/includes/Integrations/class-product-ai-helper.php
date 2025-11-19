<?php
namespace NoaSoft\AiWoo\Integrations;

use NoaSoft\AiWoo\Helpers\AI_Client_Factory;
use NoaSoft\AiWoo\Helpers\Options;

/**
 * Product AI helper module.
 */
class Product_AI_Helper {
    /**
     * Module enabled flag.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->enabled = Options::is_module_enabled( 'product_helper' );

        if ( ! is_admin() || ! $this->enabled ) {
            return;
        }

        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post_product', array( $this, 'save_meta_box' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_noasoft_ai_product_helper_generate', array( $this, 'ajax_generate_content' ) );
        add_action( 'wp_ajax_noasoft_ai_product_helper_apply', array( $this, 'ajax_apply_content' ) );
    }

    /**
     * Register helper meta box.
     *
     * @return void
     */
    public function register_meta_box() {
        add_meta_box(
            'noasoft-ai-product-helper',
            __( 'NoaSoft AI Ürün Yardımcısı', 'noasoft-ai-woocommerce' ),
            array( $this, 'render_meta_box' ),
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render meta box UI.
     *
     * @param \WP_Post $post Current post.
     * @return void
     */
    public function render_meta_box( $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        wp_nonce_field( 'noasoft_ai_product_helper', 'noasoft_ai_product_helper_nonce' );

        $seo_title       = get_post_meta( $post->ID, '_noasoft_ai_seo_title', true );
        $seo_description = get_post_meta( $post->ID, '_noasoft_ai_seo_description', true );
        $use_cases       = get_post_meta( $post->ID, '_noasoft_ai_use_cases', true );
        $benefits        = get_post_meta( $post->ID, '_noasoft_ai_benefits', true );
        $features        = get_post_meta( $post->ID, '_noasoft_ai_features', true );
        $idea_text       = get_post_meta( $post->ID, '_noasoft_ai_idea', true );
        $focus_keyword   = get_post_meta( $post->ID, '_noasoft_ai_focus_keyword', true );
        $long_desc       = get_post_meta( $post->ID, '_noasoft_ai_long_description', true );

        if ( empty( $seo_title ) ) {
            $seo_title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
        }
        if ( empty( $seo_description ) ) {
            $seo_description = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
        }
        if ( empty( $focus_keyword ) ) {
            $focus_keyword = get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true );
        }

        $short_description = $post->post_excerpt;
        $tags               = $this->get_product_tags_as_string( $post->ID );
        ?>
        <div class="noasoft-ai-helper-meta" id="noasoft-ai-product-helper">
            <p class="description">
                <?php esc_html_e( 'AI, ürün başlık ve açıklamalarını tek tıkla oluştursun. Gerekirse alanları düzenleyip kaydedebilirsiniz.', 'noasoft-ai-woocommerce' ); ?>
            </p>
            <div class="noasoft-ai-helper-toolbar">
                <button type="button" class="button button-primary noasoft-ai-generate">
                    <?php esc_html_e( 'AI ile Oluştur', 'noasoft-ai-woocommerce' ); ?>
                </button>
                <button type="button" class="button button-secondary noasoft-ai-apply" data-product-id="<?php echo esc_attr( $post->ID ); ?>">
                    <?php esc_html_e( 'AI İçeriğini Uygula', 'noasoft-ai-woocommerce' ); ?>
                </button>
                <span class="spinner"></span>
            </div>
            <div class="noasoft-ai-helper-grid">
                <p class="noasoft-ai-helper-idea">
                    <label for="noasoft_ai_helper_idea"><?php esc_html_e( 'Ürün adı veya kısa içerik', 'noasoft-ai-woocommerce' ); ?></label>
                    <input type="text" id="noasoft_ai_helper_idea" name="noasoft_ai_helper[idea]" value="<?php echo esc_attr( $idea_text ? $idea_text : $post->post_title ); ?>" placeholder="<?php esc_attr_e( 'Örn: Kablosuz Kulaklık, ANC, 30 saat pil', 'noasoft-ai-woocommerce' ); ?>" />
                    <span class="description"><?php esc_html_e( 'AI üretimi bu girdiye göre özelleştirilecek.', 'noasoft-ai-woocommerce' ); ?></span>
                </p>
                <p>
                    <label for="noasoft_ai_helper_seo_title"><?php esc_html_e( 'SEO Başlık', 'noasoft-ai-woocommerce' ); ?></label>
                    <input type="text" id="noasoft_ai_helper_seo_title" name="noasoft_ai_helper[seo_title]" value="<?php echo esc_attr( $seo_title ); ?>" />
                </p>
                <p>
                    <label for="noasoft_ai_helper_focus_keyword"><?php esc_html_e( 'Odak Anahtar Kelime', 'noasoft-ai-woocommerce' ); ?></label>
                    <input type="text" id="noasoft_ai_helper_focus_keyword" name="noasoft_ai_helper[focus_keyword]" value="<?php echo esc_attr( $focus_keyword ); ?>" />
                    <span class="description"><?php esc_html_e( 'SEO eklentilerine otomatik işlenecek odak anahtar kelime.', 'noasoft-ai-woocommerce' ); ?></span>
                </p>
                <p>
                    <label for="noasoft_ai_helper_seo_description"><?php esc_html_e( 'SEO Açıklama', 'noasoft-ai-woocommerce' ); ?></label>
                    <textarea id="noasoft_ai_helper_seo_description" name="noasoft_ai_helper[seo_description]" rows="3"><?php echo esc_textarea( $seo_description ); ?></textarea>
                </p>
                <p>
                    <label for="noasoft_ai_helper_short_description"><?php esc_html_e( 'Kısa Açıklama (Excerpt)', 'noasoft-ai-woocommerce' ); ?></label>
                    <textarea id="noasoft_ai_helper_short_description" name="noasoft_ai_helper[short_description]" rows="4"><?php echo esc_textarea( $short_description ); ?></textarea>
                    <span class="description"><?php esc_html_e( 'Bu alan ürün excerpt içeriği olarak kaydedilir.', 'noasoft-ai-woocommerce' ); ?></span>
                </p>
                <p>
                    <label for="noasoft_ai_helper_tags"><?php esc_html_e( 'Etiketler', 'noasoft-ai-woocommerce' ); ?></label>
                    <input type="text" id="noasoft_ai_helper_tags" name="noasoft_ai_helper[tags]" value="<?php echo esc_attr( $tags ); ?>" />
                    <span class="description"><?php esc_html_e( 'Virgül ile ayırın. Kaydedildiğinde ürün etiketlerine uygulanır.', 'noasoft-ai-woocommerce' ); ?></span>
                </p>
                <p>
                    <label for="noasoft_ai_helper_long_description"><?php esc_html_e( 'Uzun Açıklama (HTML)', 'noasoft-ai-woocommerce' ); ?></label>
                    <textarea id="noasoft_ai_helper_long_description" name="noasoft_ai_helper[long_description]" rows="6"><?php echo esc_textarea( $long_desc ); ?></textarea>
                    <span class="description"><?php esc_html_e( 'AI tarafından oluşturulan hiyerarşik içerik.', 'noasoft-ai-woocommerce' ); ?></span>
                </p>
                <p>
                    <label for="noasoft_ai_helper_use_cases"><?php esc_html_e( 'Ürün ne işe yarar?', 'noasoft-ai-woocommerce' ); ?></label>
                    <textarea id="noasoft_ai_helper_use_cases" name="noasoft_ai_helper[use_cases]" rows="4"><?php echo esc_textarea( $use_cases ); ?></textarea>
                </p>
                <p>
                    <label for="noasoft_ai_helper_benefits"><?php esc_html_e( 'Avantajlar (her satırda bir madde)', 'noasoft-ai-woocommerce' ); ?></label>
                    <textarea id="noasoft_ai_helper_benefits" name="noasoft_ai_helper[benefits]" rows="4"><?php echo esc_textarea( $benefits ); ?></textarea>
                </p>
                <p>
                    <label for="noasoft_ai_helper_features"><?php esc_html_e( 'Özellikler (her satırda bir madde)', 'noasoft-ai-woocommerce' ); ?></label>
                    <textarea id="noasoft_ai_helper_features" name="noasoft_ai_helper[features]" rows="4"><?php echo esc_textarea( $features ); ?></textarea>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets for editor screens.
     *
     * @param string $hook Current hook.
     * @return void
     */
    public function enqueue_assets( $hook ) {
        if ( ! $this->enabled || ( 'post.php' !== $hook && 'post-new.php' !== $hook ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( empty( $screen ) || 'product' !== $screen->post_type ) {
            return;
        }

        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! $post_id && isset( $_POST['post_ID'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $post_id = absint( $_POST['post_ID'] );
        }

        $post = $post_id ? get_post( $post_id ) : null;

        wp_enqueue_script(
            'noasoft-ai-product-helper',
            NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/admin-product-helper.js',
            array( 'jquery' ),
            NOASOFT_AI_WOO_VERSION,
            true
        );

        wp_localize_script(
            'noasoft-ai-product-helper',
            'NoaSoftProductHelper',
            array(
                'ajax_url'      => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'noasoft_ai_product_helper' ),
                'product_id'    => $post_id,
                'product_title' => $post ? $post->post_title : '',
                'short_desc'    => $post ? $post->post_excerpt : '',
                'description'   => $post ? $post->post_content : '',
                'fields'        => array(
                    'title' => 'title',
                ),
                'messages'      => array(
                    'success' => __( 'AI önerileri alanlara aktarıldı.', 'noasoft-ai-woocommerce' ),
                    'error'   => __( 'İçerik oluşturulamadı. Lütfen tekrar deneyin.', 'noasoft-ai-woocommerce' ),
                    'apply_success' => __( 'AI içeriği kaydedildi.', 'noasoft-ai-woocommerce' ),
                    'apply_error'   => __( 'AI içeriği kaydedilemedi.', 'noasoft-ai-woocommerce' ),
                ),
            )
        );
    }

    /**
     * AJAX: generate product copy.
     *
     * @return void
     */
    public function ajax_generate_content() {
        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Modül devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        check_ajax_referer( 'noasoft_ai_product_helper', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bu işlem için yetkiniz yok.', 'noasoft-ai-woocommerce' ) ), 403 );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $payload    = array(
            'title'             => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
            'short_description' => isset( $_POST['short_description'] ) ? wp_strip_all_tags( wp_unslash( $_POST['short_description'] ) ) : '',
            'description'       => isset( $_POST['description'] ) ? wp_strip_all_tags( wp_unslash( $_POST['description'] ) ) : '',
            'idea'              => isset( $_POST['idea'] ) ? sanitize_text_field( wp_unslash( $_POST['idea'] ) ) : '',
            'tags'              => isset( $_POST['tags'] ) ? $this->sanitize_tags_list( wp_unslash( $_POST['tags'] ) ) : array(),
        );

        $content = $this->generate_content( $product_id, $payload );

        if ( is_wp_error( $content ) ) {
            wp_send_json_error( array( 'message' => $content->get_error_message() ), 200 );
        }

        if ( empty( $content ) ) {
            wp_send_json_error( array( 'message' => __( 'AI cevabı alınamadı.', 'noasoft-ai-woocommerce' ) ), 200 );
        }

        wp_send_json_success( array( 'content' => $content ) );
    }

    /**
     * AJAX: apply generated content to product fields.
     */
    public function ajax_apply_content() {
        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Modül devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        check_ajax_referer( 'noasoft_ai_product_helper', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        if ( ! $product_id || ! current_user_can( 'edit_product', $product_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Bu işlem için yetkiniz yok.', 'noasoft-ai-woocommerce' ) ), 403 );
        }

        $payload = isset( $_POST['payload'] ) ? (array) wp_unslash( $_POST['payload'] ) : array();
        $sanitized = array(
            'seo_title'       => isset( $payload['seo_title'] ) ? sanitize_text_field( $payload['seo_title'] ) : '',
            'seo_description' => isset( $payload['seo_description'] ) ? sanitize_textarea_field( $payload['seo_description'] ) : '',
            'short_description' => isset( $payload['short_description'] ) ? wp_kses_post( $payload['short_description'] ) : '',
            'use_cases'       => isset( $payload['use_cases'] ) ? wp_kses_post( $payload['use_cases'] ) : '',
            'benefits'        => isset( $payload['benefits'] ) ? (array) $payload['benefits'] : array(),
            'features'        => isset( $payload['features'] ) ? (array) $payload['features'] : array(),
            'tags'            => isset( $payload['tags'] ) ? sanitize_text_field( $payload['tags'] ) : '',
            'focus_keyword'   => isset( $payload['focus_keyword'] ) ? sanitize_text_field( $payload['focus_keyword'] ) : '',
            'long_description'=> isset( $payload['long_description'] ) ? wp_kses_post( $payload['long_description'] ) : '',
        );

        $result = $this->apply_payload_to_product( $product_id, $sanitized );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 200 );
        }

        wp_send_json_success( array( 'message' => __( 'AI içeriği kaydedildi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Generate product content via AI.
     *
     * @param int   $product_id Product ID.
     * @param array $data Product data.
     * @return array
     */
    public function generate_content( $product_id, $data ) {
        $title       = isset( $data['idea'] ) && $data['idea'] ? $data['idea'] : ( ( isset( $data['title'] ) && $data['title'] ) ? $data['title'] : __( 'Ürün', 'noasoft-ai-woocommerce' ) );
        $prompt      = Options::get_prompt( 'product_helper', __( 'Lütfen ürün için SEO odaklı metinler üret.', 'noasoft-ai-woocommerce' ) );
        $prompt     .= "\n- seo_title, seo_description, focus_keyword, short_description, long_description, use_cases, benefits (array), features (array), tags alanlarını döndür.\n- focus_keyword tüm metinlerde doğal şekilde kullanılmalı.\n- HTML güvenli ve hiyerarşik olsun.";
        $context     = $this->build_context( $product_id, $title, $data );
        $client      = AI_Client_Factory::make();
        $ai_response = array();

        if ( $client ) {
            try {
                $ai_response = $client->chat( $prompt, $context );
            } catch ( \Throwable $th ) {
                \NoaSoft\AiWoo\Helpers\Logger::log_exception( $th, array( 'context' => 'product_ai_helper' ) );
                return new \WP_Error( 'product_ai_exception', __( 'AI isteği başarısız oldu.', 'noasoft-ai-woocommerce' ) );
            }

            if ( is_wp_error( $ai_response ) ) {
                \NoaSoft\AiWoo\Helpers\Logger::log( 'Product helper AI error', array( 'error' => $ai_response->get_error_message() ) );

                return $ai_response;
            }
        }

        return $this->normalize_ai_payload( $ai_response, $context );
    }

    /**
     * Save meta box data.
     *
     * @param int     $post_id Post ID.
     * @param \WP_Post $post Post object.
     * @param bool    $update Update flag.
     * @return void
     */
    public function save_meta_box( $post_id, $post, $update ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        if ( ! $this->enabled ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! isset( $_POST['noasoft_ai_product_helper_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['noasoft_ai_product_helper_nonce'] ) ), 'noasoft_ai_product_helper' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = isset( $_POST['noasoft_ai_helper'] ) ? (array) wp_unslash( $_POST['noasoft_ai_helper'] ) : array();

        $seo_title       = isset( $fields['seo_title'] ) ? sanitize_text_field( $fields['seo_title'] ) : '';
        $seo_description = isset( $fields['seo_description'] ) ? sanitize_textarea_field( $fields['seo_description'] ) : '';
        $short_desc      = isset( $fields['short_description'] ) ? wp_kses_post( $fields['short_description'] ) : '';
        $tags_string     = isset( $fields['tags'] ) ? $fields['tags'] : '';
        $use_cases       = isset( $fields['use_cases'] ) ? wp_kses_post( $fields['use_cases'] ) : '';
        $benefits        = isset( $fields['benefits'] ) ? sanitize_textarea_field( $fields['benefits'] ) : '';
        $features        = isset( $fields['features'] ) ? sanitize_textarea_field( $fields['features'] ) : '';
        $idea_text       = isset( $fields['idea'] ) ? sanitize_text_field( $fields['idea'] ) : '';
        $focus_keyword   = isset( $fields['focus_keyword'] ) ? sanitize_text_field( $fields['focus_keyword'] ) : '';
        $long_desc       = isset( $fields['long_description'] ) ? wp_kses_post( $fields['long_description'] ) : '';

        $this->update_seo_meta( $post_id, $seo_title, $seo_description );
        $this->update_focus_keyword( $post_id, $focus_keyword );
        $this->update_tag_terms( $post_id, $tags_string );

        update_post_meta( $post_id, '_noasoft_ai_use_cases', $use_cases );
        update_post_meta( $post_id, '_noasoft_ai_benefits', $benefits );
        update_post_meta( $post_id, '_noasoft_ai_features', $features );
        update_post_meta( $post_id, '_noasoft_ai_idea', $idea_text );
        update_post_meta( $post_id, '_noasoft_ai_focus_keyword', $focus_keyword );
        update_post_meta( $post_id, '_noasoft_ai_long_description', $long_desc );

        $this->sync_post_fields( $post_id, $short_desc, $use_cases, $benefits, $features, $long_desc );
    }

    /**
     * Update SEO metadata & fallbacks.
     *
     * @param int    $post_id Post ID.
     * @param string $seo_title Title.
     * @param string $seo_description Description.
     * @return void
     */
    protected function update_seo_meta( $post_id, $seo_title, $seo_description ) {
        update_post_meta( $post_id, '_noasoft_ai_seo_title', $seo_title );
        update_post_meta( $post_id, '_noasoft_ai_seo_description', $seo_description );

        if ( class_exists( 'WPSEO_Meta' ) || defined( 'WPSEO_VERSION' ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $seo_description );
        }

        if ( defined( 'RANK_MATH_VERSION' ) ) {
            update_post_meta( $post_id, 'rank_math_title', $seo_title );
            update_post_meta( $post_id, 'rank_math_description', $seo_description );
        }

        if ( defined( 'AIOSEO_VERSION' ) ) {
            update_post_meta( $post_id, '_aioseo_title', $seo_title );
            update_post_meta( $post_id, '_aioseo_description', $seo_description );
        }
    }

    /**
     * Sync excerpt, content blocks and tags.
     *
     * @param int    $post_id Post ID.
     * @param string $short_desc Short description.
     * @param string $use_cases Use cases.
     * @param string $benefits Benefits text.
     * @param string $features Features text.
     * @return void
     */
    protected function sync_post_fields( $post_id, $short_desc, $use_cases, $benefits, $features, $long_desc = '' ) {
        $updates = array(
            'ID'           => $post_id,
            'post_excerpt' => null,
            'post_content' => null,
        );

        $current_excerpt = get_post_field( 'post_excerpt', $post_id );
        if ( $current_excerpt !== $short_desc ) {
            $updates['post_excerpt'] = $short_desc;
        }

        $new_content = $this->compose_content_with_sections( $post_id, $use_cases, $benefits, $features, $long_desc );
        if ( null !== $new_content ) {
            $updates['post_content'] = $new_content;
        }

        $this->commit_post_updates( $updates );
    }

    /**
     * Update terms.
     *
     * @param int    $post_id Post ID.
     * @param string $tags_string Tag string.
     * @return void
     */
    protected function update_tag_terms( $post_id, $tags_string ) {
        if ( ! taxonomy_exists( 'product_tag' ) ) {
            return;
        }

        $tags = $this->sanitize_tags_list( $tags_string );
        if ( empty( $tags ) ) {
            return;
        }

        wp_set_post_terms( $post_id, $tags, 'product_tag', false );
    }

    /**
     * Update focus keyword for popular SEO plugins.
     *
     * @param int    $post_id Post ID.
     * @param string $keyword Focus keyword.
     * @return void
     */
    protected function update_focus_keyword( $post_id, $keyword ) {
        if ( ! $keyword ) {
            return;
        }

        update_post_meta( $post_id, '_noasoft_ai_focus_keyword', $keyword );

        if ( class_exists( 'WPSEO_Meta' ) || defined( 'WPSEO_VERSION' ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', $keyword );
        }

        if ( defined( 'RANK_MATH_VERSION' ) ) {
            update_post_meta( $post_id, 'rank_math_focus_keyword', $keyword );
        }

        if ( defined( 'AIOSEO_VERSION' ) ) {
            update_post_meta( $post_id, '_aioseo_keywords', $keyword );
        }
    }

    /**
     * Apply AI payload to product fields.
     *
     * @param int   $post_id Post ID.
     * @param array $payload Sanitized payload.
     * @return true|\WP_Error
     */
    protected function apply_payload_to_product( $post_id, $payload ) {
        $this->update_seo_meta( $post_id, $payload['seo_title'], $payload['seo_description'] );
        $this->update_focus_keyword( $post_id, $payload['focus_keyword'] );
        $this->update_tag_terms( $post_id, $payload['tags'] );

        update_post_meta( $post_id, '_noasoft_ai_use_cases', $payload['use_cases'] );
        update_post_meta( $post_id, '_noasoft_ai_benefits', is_array( $payload['benefits'] ) ? implode( "\n", $payload['benefits'] ) : $payload['benefits'] );
        update_post_meta( $post_id, '_noasoft_ai_features', is_array( $payload['features'] ) ? implode( "\n", $payload['features'] ) : $payload['features'] );
        update_post_meta( $post_id, '_noasoft_ai_long_description', $payload['long_description'] );

        $this->commit_post_updates(
            array(
                'ID'           => $post_id,
                'post_excerpt' => $payload['short_description'],
                'post_content' => $this->compose_content_with_sections( $post_id, $payload['use_cases'], $payload['benefits'], $payload['features'], $payload['long_description'] ),
            )
        );

        return true;
    }

    /**
     * Commit wp_update_post with recursion guard.
     *
     * @param array $updates Updates.
     * @return void
     */
    protected function commit_post_updates( $updates ) {
        $filtered = array();
        foreach ( $updates as $key => $value ) {
            if ( 'ID' === $key || null !== $value ) {
                $filtered[ $key ] = $value;
            }
        }

        if ( count( $filtered ) <= 1 ) {
            return;
        }

        remove_action( 'save_post_product', array( $this, 'save_meta_box' ), 10 );
        wp_update_post( $filtered );
        add_action( 'save_post_product', array( $this, 'save_meta_box' ), 10, 3 );
    }

    /**
     * Build context for AI prompt.
     *
     * @param int    $product_id Product ID.
     * @param string $title Title.
     * @param array  $data Input data.
     * @return array
     */
    protected function build_context( $product_id, $title, $data ) {
        $context = array(
            'product_id'        => $product_id,
            'title'             => $title,
            'short_description' => isset( $data['short_description'] ) ? wp_strip_all_tags( $data['short_description'] ) : '',
            'description'       => isset( $data['description'] ) ? wp_strip_all_tags( $data['description'] ) : '',
            'tags'              => isset( $data['tags'] ) ? array_map( 'sanitize_text_field', (array) $data['tags'] ) : array(),
            'site'              => get_bloginfo( 'name' ),
            'categories'        => array(),
            'attributes'        => array(),
            'stock_status'      => '',
            'rating'            => 0,
        );

        if ( $product_id && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $context['price']        = $product->get_price();
                $context['sku']          = $product->get_sku();
                $context['stock_status'] = $product->get_stock_status();
                $context['rating']       = $product->get_average_rating();

                $context['categories'] = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
                $context['tags']        = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );

                $attributes = $product->get_attributes();
                if ( is_array( $attributes ) ) {
                    foreach ( $attributes as $attribute ) {
                        if ( $attribute->is_taxonomy() ) {
                            $tax_terms = wp_get_post_terms( $product_id, $attribute->get_name(), array( 'fields' => 'names' ) );
                            $context['attributes'][ $attribute->get_name() ] = $tax_terms;
                        } else {
                            $context['attributes'][ $attribute->get_name() ] = (array) $attribute->get_options();
                        }
                    }
                }
            }
        }

        return $context;
    }

    /**
     * Normalize AI response to deterministic array.
     *
     * @param array $response AI raw response.
     * @param array $context Prompt context.
     * @return array
     */
    protected function normalize_ai_payload( $response, $context ) {
        $title     = $context['title'];
        $site_name = $context['site'];

        $seo_title       = sprintf( __( '%1$s | %2$s öneriyor', 'noasoft-ai-woocommerce' ), $title, $site_name );
        $seo_description = sprintf( __( '%1$s için mağazamızdan en iyi teklifleri keşfedin.', 'noasoft-ai-woocommerce' ), $title );
        $short_desc      = $context['short_description'] ? $context['short_description'] : __( 'Ürününüz için akıllı, dikkat çekici bir açıklama burada yer alacak.', 'noasoft-ai-woocommerce' );
        $use_cases       = __( 'Ürün günlük kullanımda zamandan tasarruf sağlar ve müşterilerinize profesyonel sonuç sunar.', 'noasoft-ai-woocommerce' );
        $benefits        = array(
            __( 'Hızlı kurulum ve anında sonuç.', 'noasoft-ai-woocommerce' ),
            __( 'Uygun maliyet / yüksek performans dengesi.', 'noasoft-ai-woocommerce' ),
            __( 'Müşteri deneyimini iyileştiren premium detaylar.', 'noasoft-ai-woocommerce' ),
        );
        $features        = array(
            __( 'Uzun ömürlü malzeme kalitesi.', 'noasoft-ai-woocommerce' ),
            __( 'Modern tasarım ve ergonomik kullanım.', 'noasoft-ai-woocommerce' ),
        );
        $tags            = $this->suggest_tags_from_title( $title );
        $focus_keyword   = sanitize_text_field( $title );
        $long_desc       = '';

        if ( isset( $response['seo_title'] ) ) {
            $seo_title = sanitize_text_field( $response['seo_title'] );
        } elseif ( isset( $response['headline'] ) ) {
            $seo_title = sanitize_text_field( $response['headline'] );
        }

        if ( isset( $response['seo_description'] ) ) {
            $seo_description = sanitize_textarea_field( $response['seo_description'] );
        }

        if ( isset( $response['short_description'] ) ) {
            $short_desc = wp_kses_post( $response['short_description'] );
        }

        if ( isset( $response['long_description'] ) ) {
            $long_desc = wp_kses_post( $response['long_description'] );
        }

        if ( isset( $response['use_cases'] ) ) {
            $use_cases = wp_kses_post( is_array( $response['use_cases'] ) ? implode( ' ', $response['use_cases'] ) : $response['use_cases'] );
        }

        if ( isset( $response['benefits'] ) && is_array( $response['benefits'] ) ) {
            $benefits = array_map( 'sanitize_text_field', $response['benefits'] );
        }

        if ( isset( $response['features'] ) && is_array( $response['features'] ) ) {
            $features = array_map( 'sanitize_text_field', $response['features'] );
        }

        if ( isset( $response['tags'] ) ) {
            if ( is_array( $response['tags'] ) ) {
                $tags = array_map( 'sanitize_text_field', $response['tags'] );
            } else {
                $tags = $this->sanitize_tags_list( $response['tags'] );
            }
        }

        if ( isset( $response['focus_keyword'] ) ) {
            $focus_keyword = sanitize_text_field( $response['focus_keyword'] );
        }

        if ( empty( $long_desc ) ) {
            $long_desc  = '<!--noasoft-ai-product-helper-->';
            $long_desc .= '<h2>' . esc_html( $title ) . '</h2>';
            $long_desc .= '<p>' . esc_html__( 'Aşağıdaki içerik AI tarafından önerilmiştir.', 'noasoft-ai-woocommerce' ) . '</p>';
            $long_desc .= '<h3>' . esc_html__( 'Öne çıkan faydalar', 'noasoft-ai-woocommerce' ) . '</h3>';
            $long_desc .= '<ul>';
            foreach ( $benefits as $benefit ) {
                $long_desc .= '<li>' . esc_html( $benefit ) . '</li>';
            }
            $long_desc .= '</ul>';
            $long_desc .= '<!--/noasoft-ai-product-helper-->';
        }

        return array(
            'seo_title'        => $seo_title,
            'seo_description'  => $seo_description,
            'short_description'=> $short_desc,
            'use_cases'        => $use_cases,
            'benefits'         => $benefits,
            'features'         => $features,
            'tags'             => implode( ', ', $tags ),
            'focus_keyword'    => $focus_keyword,
            'long_description' => $long_desc,
        );
    }

    /**
     * Compose content and AI sections for main description.
     *
     * @param int    $post_id Post ID.
     * @param string $use_cases Use cases.
     * @param string $benefits Benefits text.
     * @param string $features Features text.
     * @return string|null
     */
    protected function compose_content_with_sections( $post_id, $use_cases, $benefits, $features, $long_desc = '' ) {
        $current = get_post_field( 'post_content', $post_id );
        $clean   = trim( $this->strip_existing_sections( $current ) );
        $base    = $long_desc ? wp_kses_post( $long_desc ) : $clean;
        $block   = $this->build_content_sections( $use_cases, $benefits, $features );

        $updated = $base;
        if ( $block ) {
            $updated = $base ? $base . "\n\n" . $block : $block;
        }

        if ( trim( (string) $updated ) === trim( (string) $current ) ) {
            return null;
        }

        return $updated;
    }

    /**
     * Remove previous AI blocks.
     *
     * @param string $content Original content.
     * @return string
     */
    protected function strip_existing_sections( $content ) {
        return preg_replace( '/<!--noasoft-ai-product-helper-->.*?<!--\/noasoft-ai-product-helper-->/s', '', (string) $content );
    }

    /**
     * Build helper block HTML.
     *
     * @param string $use_cases Use cases.
     * @param string $benefits Benefits text.
     * @param string $features Features text.
     * @return string
     */
    protected function build_content_sections( $use_cases, $benefits, $features ) {
        $benefits_list = $this->parse_list_text( $benefits );
        $features_list = $this->parse_list_text( $features );
        $use_cases     = trim( wp_kses_post( $use_cases ) );

        if ( empty( $use_cases ) && empty( $benefits_list ) && empty( $features_list ) ) {
            return '';
        }

        ob_start();
        ?>
        <!--noasoft-ai-product-helper-->
        <div class="noasoft-ai-product-helper-block">
            <?php if ( $use_cases ) : ?>
                <h3><?php esc_html_e( 'Ürün Ne İşe Yarar?', 'noasoft-ai-woocommerce' ); ?></h3>
                <p><?php echo wp_kses_post( $use_cases ); ?></p>
            <?php endif; ?>
            <?php if ( $benefits_list ) : ?>
                <h3><?php esc_html_e( 'Avantajlar', 'noasoft-ai-woocommerce' ); ?></h3>
                <ul>
                    <?php foreach ( $benefits_list as $benefit ) : ?>
                        <li><?php echo esc_html( $benefit ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ( $features_list ) : ?>
                <h3><?php esc_html_e( 'Öne Çıkan Özellikler', 'noasoft-ai-woocommerce' ); ?></h3>
                <ul>
                    <?php foreach ( $features_list as $feature ) : ?>
                        <li><?php echo esc_html( $feature ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <!--/noasoft-ai-product-helper-->
        <?php
        return trim( ob_get_clean() );
    }

    /**
     * Parse textarea list into array.
     *
     * @param string $text Multiline text.
     * @return array
     */
    protected function parse_list_text( $text ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $text );
        $lines = array_map( 'trim', $lines );
        $lines = array_filter( $lines );

        return array_values( array_map( 'sanitize_text_field', $lines ) );
    }

    /**
     * Convert tag string to array.
     *
     * @param string|array $tags Tag string or array.
     * @return array
     */
    protected function sanitize_tags_list( $tags ) {
        if ( is_array( $tags ) ) {
            $list = $tags;
        } else {
            $list = preg_split( '/,/', (string) $tags );
        }

        $clean = array();
        foreach ( $list as $tag ) {
            $tag = trim( wp_strip_all_tags( $tag ) );
            if ( $tag ) {
                $clean[] = $tag;
            }
        }

        return array_values( array_unique( $clean ) );
    }

    /**
     * Suggest tags from title.
     *
     * @param string $title Title.
     * @return array
     */
    protected function suggest_tags_from_title( $title ) {
        $title = strtolower( remove_accents( $title ) );
        $parts = preg_split( '/[^a-z0-9]+/', $title );
        $parts = array_filter( $parts, function ( $part ) {
            return strlen( $part ) >= 4;
        } );
        $parts = array_slice( array_unique( $parts ), 0, 6 );

        if ( empty( $parts ) ) {
            $parts = array( 'premium', 'trend', 'shop' );
        }

        return array_map( 'sanitize_text_field', $parts );
    }

    /**
     * Get product tags as CSV.
     *
     * @param int $post_id Post ID.
     * @return string
     */
    protected function get_product_tags_as_string( $post_id ) {
        if ( ! taxonomy_exists( 'product_tag' ) ) {
            return '';
        }

        $terms = wp_get_post_terms( $post_id, 'product_tag', array( 'fields' => 'names' ) );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        return implode( ', ', array_map( 'sanitize_text_field', $terms ) );
    }
}
