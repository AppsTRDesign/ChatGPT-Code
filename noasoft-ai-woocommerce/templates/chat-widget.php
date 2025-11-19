<?php
/**
 * Chat widget template.
 *
 * @var array $chat_settings
 * @var array $chat_context
 */
?>
<div class="noasoft-chat-embed <?php echo ! empty( $chat_context['is_floating'] ) ? 'is-floating' : 'is-inline'; ?> noasoft-bubble-<?php echo esc_attr( $chat_settings['bubble_style'] ); ?>"
    data-position="<?php echo esc_attr( $chat_settings['position'] ); ?>"
    style="--noasoft-chat-primary: <?php echo esc_attr( $chat_settings['primary_color'] ); ?>; --noasoft-chat-accent: <?php echo esc_attr( $chat_settings['accent_color'] ); ?>; --noasoft-chat-height: <?php echo absint( $chat_settings['panel_height'] ); ?>px;">
    <?php if ( ! empty( $chat_context['show_launcher'] ) ) : ?>
        <button type="button" class="noasoft-chat-launcher" aria-expanded="false">
            <span class="avatar">
                <img src="<?php echo esc_url( $chat_settings['avatar_url'] ); ?>" alt="<?php esc_attr_e( 'AI Asistan', 'noasoft-ai-woocommerce' ); ?>" />
            </span>
            <span class="label">AI</span>
        </button>
    <?php endif; ?>
    <div class="noasoft-chat-panel" role="dialog" aria-live="polite">
        <header class="noasoft-chat-header">
            <div class="avatar">
                <img src="<?php echo esc_url( $chat_settings['avatar_url'] ); ?>" alt="<?php esc_attr_e( 'AI Asistan', 'noasoft-ai-woocommerce' ); ?>" />
            </div>
            <div class="meta">
                <h3><?php echo esc_html( $chat_settings['header_title'] ); ?></h3>
                <p><?php echo esc_html( $chat_settings['greeting'] ); ?></p>
            </div>
            <button type="button" class="noasoft-chat-close" aria-label="<?php esc_attr_e( 'Pencereyi kapat', 'noasoft-ai-woocommerce' ); ?>">&times;</button>
        </header>
        <div class="noasoft-chat-body">
            <div class="noasoft-chat-welcome">
                <span class="noasoft-gradient-pill">AI</span>
                <p><?php esc_html_e( 'Sana özel öneriler ve sipariş desteği için buradayım.', 'noasoft-ai-woocommerce' ); ?></p>
            </div>
            <div class="noasoft-chat-messages" role="log" aria-live="polite"></div>
            <div class="noasoft-chat-typing" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>
            <div class="noasoft-chat-suggestions"></div>
            <div class="noasoft-chat-quick-actions" role="group" aria-label="<?php esc_attr_e( 'Hızlı işlemler', 'noasoft-ai-woocommerce' ); ?>">
                <button type="button" data-intent="order_status"><?php esc_html_e( 'Sipariş Durumu', 'noasoft-ai-woocommerce' ); ?></button>
                <button type="button" data-intent="shipping_status"><?php esc_html_e( 'Kargo Takibi', 'noasoft-ai-woocommerce' ); ?></button>
                <button type="button" data-intent="stock_status"><?php esc_html_e( 'Stok Kontrolü', 'noasoft-ai-woocommerce' ); ?></button>
                <button type="button" data-intent="product_info"><?php esc_html_e( 'Ürün Bilgisi', 'noasoft-ai-woocommerce' ); ?></button>
            </div>
        </div>
        <footer class="noasoft-chat-footer">
            <form class="noasoft-chat-form">
                <input type="text" name="message" placeholder="<?php esc_attr_e( 'Sorunuzu yazın...', 'noasoft-ai-woocommerce' ); ?>" autocomplete="off" />
                <input type="file" name="chat_image" accept="image/*" class="noasoft-chat-file" hidden />
                <?php if ( ! empty( $chat_settings['enable_image_uploads'] ) ) : ?>
                    <button type="button" class="noasoft-chat-upload" title="<?php esc_attr_e( 'Görsel ile öneri iste', 'noasoft-ai-woocommerce' ); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm3 10l2.5-3.33L12 14l3.5-4.67L20 15v1H4v-1Zm1-7a2 2 0 1 0 2 2a2 2 0 0 0-2-2Z"/></svg>
                    </button>
                <?php endif; ?>
                <button type="submit" class="noasoft-chat-send" aria-label="<?php esc_attr_e( 'Gönder', 'noasoft-ai-woocommerce' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3.4 20.4L22 12L3.4 3.6L3 10l10 2l-10 2z"/></svg>
                </button>
            </form>
            <span class="noasoft-chat-spinner" aria-hidden="true"></span>
        </footer>
    </div>
</div>
