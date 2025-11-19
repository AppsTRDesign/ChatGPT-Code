<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap noasoft-ai-admin">
    <h1><?php esc_html_e( 'NoaSoft AI WooCommerce Suite', 'noasoft-ai' ); ?></h1>

    <div class="noasoft-ai-tabs" id="noasoft-ai-tabs">
        <ul>
            <li><a href="#tab-general"><?php esc_html_e( 'Genel Ayarlar', 'noasoft-ai' ); ?></a></li>
            <li><a href="#tab-api"><?php esc_html_e( 'API Seçenekleri', 'noasoft-ai' ); ?></a></li>
            <li><a href="#tab-assistant"><?php esc_html_e( 'AI Satış Asistanı', 'noasoft-ai' ); ?></a></li>
            <li><a href="#tab-product"><?php esc_html_e( 'Ürün Otomasyonları', 'noasoft-ai' ); ?></a></li>
            <li><a href="#tab-reports"><?php esc_html_e( 'Raporlama', 'noasoft-ai' ); ?></a></li>
            <li><a href="#tab-shortcodes"><?php esc_html_e( 'Shortcode Rehberi', 'noasoft-ai' ); ?></a></li>
        </ul>

        <div id="tab-general" class="noasoft-ai-panel">
            <form method="post" action="options.php">
                <?php settings_fields( 'noasoft_ai_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Dil', 'noasoft-ai' ); ?></th>
                        <td>
                            <select name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[language]' ); ?>">
                                <option value="tr_TR" <?php selected( $settings['language'], 'tr_TR' ); ?>>Türkçe</option>
                                <option value="en_US" <?php selected( $settings['language'], 'en_US' ); ?>>English</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Modüller', 'noasoft-ai' ); ?></th>
                        <td class="noasoft-ai-modules">
                            <?php foreach ( $settings['modules'] as $module => $enabled ) : ?>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . "[modules][$module]" ); ?>" value="1" <?php checked( $enabled, true ); ?>>
                                    <span><?php echo esc_html( ucfirst( str_replace( '_', ' ', $module ) ) ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <div id="tab-api" class="noasoft-ai-panel">
            <form method="post" action="options.php">
                <?php settings_fields( 'noasoft_ai_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Sağlayıcı', 'noasoft-ai' ); ?></th>
                        <td>
                            <select name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[api_provider]' ); ?>">
                                <option value="chatgpt" <?php selected( $settings['api_provider'], 'chatgpt' ); ?>>ChatGPT</option>
                                <option value="deepseek" <?php selected( $settings['api_provider'], 'deepseek' ); ?>>DeepSeek</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>ChatGPT API Key</th>
                        <td><input type="password" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[chatgpt_key]' ); ?>" value="<?php echo esc_attr( $settings['chatgpt_key'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>ChatGPT Model</th>
                        <td><input type="text" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[chatgpt_model]' ); ?>" value="<?php echo esc_attr( $settings['chatgpt_model'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>DeepSeek API Key</th>
                        <td><input type="password" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[deepseek_key]' ); ?>" value="<?php echo esc_attr( $settings['deepseek_key'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>DeepSeek Model</th>
                        <td><input type="text" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[deepseek_model]' ); ?>" value="<?php echo esc_attr( $settings['deepseek_model'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Sıcaklık', 'noasoft-ai' ); ?></th>
                        <td><input type="number" step="0.1" min="0" max="1" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[temperature]' ); ?>" value="<?php echo esc_attr( $settings['temperature'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th>Max Tokens</th>
                        <td><input type="number" min="128" max="4000" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[max_tokens]' ); ?>" value="<?php echo esc_attr( $settings['max_tokens'] ); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>

        <div id="tab-assistant" class="noasoft-ai-panel">
            <form method="post" action="options.php" class="noasoft-ai-assistant-form">
                <?php settings_fields( 'noasoft_ai_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Aktif', 'noasoft-ai' ); ?></th>
                        <td><input type="checkbox" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[assistant][status]' ); ?>" value="1" <?php checked( $settings['assistant']['status'], true ); ?>></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Karşılama', 'noasoft-ai' ); ?></th>
                        <td><input type="text" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[assistant][greeting]' ); ?>" value="<?php echo esc_attr( $settings['assistant']['greeting'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'İlk Mesaj', 'noasoft-ai' ); ?></th>
                        <td><input type="text" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[assistant][first_message]' ); ?>" value="<?php echo esc_attr( $settings['assistant']['first_message'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Konum', 'noasoft-ai' ); ?></th>
                        <td>
                            <select name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[assistant][position]' ); ?>">
                                <option value="bottom-right" <?php selected( $settings['assistant']['position'], 'bottom-right' ); ?>>Sağ Alt</option>
                                <option value="bottom-left" <?php selected( $settings['assistant']['position'], 'bottom-left' ); ?>>Sol Alt</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Renkler', 'noasoft-ai' ); ?></th>
                        <td class="noasoft-ai-color-grid">
                            <?php foreach ( $settings['assistant']['theme'] as $key => $color ) : ?>
                                <label>
                                    <span><?php echo esc_html( ucfirst( $key ) ); ?></span>
                                    <input type="text" class="noasoft-ai-color" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . "[assistant][theme][$key]" ); ?>" value="<?php echo esc_attr( $color ); ?>">
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Buton Radius', 'noasoft-ai' ); ?></th>
                        <td><input type="range" min="0" max="32" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[assistant][button_radius]' ); ?>" value="<?php echo esc_attr( $settings['assistant']['button_radius'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Asistan Avatarı', 'noasoft-ai' ); ?></th>
                        <td>
                            <div class="noasoft-ai-avatar-field" data-target="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[assistant][avatar_id]' ); ?>">
                                <input type="hidden" name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[assistant][avatar_id]' ); ?>" value="<?php echo esc_attr( $settings['assistant']['avatar_id'] ); ?>">
                                <button class="button noasoft-ai-upload" type="button">Avatar Seç</button>
                                <button class="button noasoft-ai-remove" type="button"><?php esc_html_e( 'Kaldır', 'noasoft-ai' ); ?></button>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>

        <div id="tab-product" class="noasoft-ai-panel">
            <p><?php esc_html_e( 'AI ürün açıklamaları, SEO etiketleri ve görsel optimizasyonu bu alandan yönetilir.', 'noasoft-ai' ); ?></p>
            <div class="noasoft-ai-cards">
                <div class="noasoft-ai-card">
                    <h3><?php esc_html_e( 'Prompt Ayarları', 'noasoft-ai' ); ?></h3>
                    <textarea name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[prompts][summary]' ); ?>" form="noasoft-ai-prompts"><?php echo esc_textarea( $settings['prompts']['summary'] ); ?></textarea>
                </div>
                <div class="noasoft-ai-card">
                    <h3><?php esc_html_e( 'Öneri Prompu', 'noasoft-ai' ); ?></h3>
                    <textarea name="<?php echo esc_attr( NoaSoft_AI_Settings::OPTION . '[prompts][recommendation]' ); ?>" form="noasoft-ai-prompts"><?php echo esc_textarea( $settings['prompts']['recommendation'] ); ?></textarea>
                </div>
            </div>
            <form method="post" action="options.php" id="noasoft-ai-prompts">
                <?php settings_fields( 'noasoft_ai_settings_group' ); ?>
                <?php submit_button(); ?>
            </form>
        </div>

        <div id="tab-reports" class="noasoft-ai-panel">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'noasoft_ai_report' ); ?>
                <input type="hidden" name="action" value="noasoft_ai_generate_report">
                <?php submit_button( __( 'Rapor Oluştur', 'noasoft-ai' ) ); ?>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="noasoft_ai_export_report">
                <?php submit_button( __( 'Son Raporu PDF Dışa Aktar', 'noasoft-ai' ), 'secondary' ); ?>
            </form>
            <canvas id="noasoft-ai-report-chart" width="400" height="200"></canvas>
        </div>

        <div id="tab-shortcodes" class="noasoft-ai-panel">
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Shortcode', 'noasoft-ai' ); ?></th>
                        <th><?php esc_html_e( 'Açıklama', 'noasoft-ai' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( NoaSoft_AI_Shortcodes::get_docs() as $shortcode => $desc ) : ?>
                        <tr>
                            <td><code>[<?php echo esc_html( $shortcode ); ?>]</code></td>
                            <td><?php echo esc_html( $desc ); ?></td>
                            <td><button class="button noasoft-ai-copy" data-code="[<?php echo esc_attr( $shortcode ); ?>]">Kopyala</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
