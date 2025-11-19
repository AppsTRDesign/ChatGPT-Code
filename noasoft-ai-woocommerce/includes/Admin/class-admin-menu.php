<?php
namespace NoaSoft\AiWoo\Admin;

/**
 * Admin menu registration.
 */
class Admin_Menu {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    /**
     * Register admin menu.
     */
    public function register_menu() {
        add_menu_page(
            __( 'NoaSoft AI Woo', 'noasoft-ai-woocommerce' ),
            __( 'NoaSoft AI Woo', 'noasoft-ai-woocommerce' ),
            'manage_woocommerce',
            'noasoft-ai-woo',
            array( $this, 'render_main_page' ),
            'data:image/svg+xml;base64,' . base64_encode( '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8 8.009 8.009 0 0 1-8 8Zm1-13h-2v2h2Zm0 4h-2v7h2Z"/></svg>' ),
            56
        );
    }

    /**
     * Render placeholder.
     */
    public function render_main_page() {
        ?>
        <div class="wrap noasoft-admin-landing">
            <header class="hero">
                <div>
                    <p class="eyebrow"><?php esc_html_e( 'AI ile güçlendirilmiş WooCommerce eklentisi', 'noasoft-ai-woocommerce' ); ?></p>
                    <h1><?php esc_html_e( 'NoaSoft AI WooCommerce Assistant', 'noasoft-ai-woocommerce' ); ?></h1>
                    <p class="lead"><?php esc_html_e( 'Satış sohbeti, ürün önerisi, admin raporları, görsel optimizasyonu ve çeviri yönetimini tek panelde birleştiren üretim seviyesinde çözüm.', 'noasoft-ai-woocommerce' ); ?></p>
                    <div class="cta">
                        <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=noasoft-ai-woo-settings' ) ); ?>"><?php esc_html_e( 'Ayarları Aç', 'noasoft-ai-woocommerce' ); ?></a>
                        <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=noasoft-ai-woo-reports' ) ); ?>"><?php esc_html_e( 'AI Raporlarına Git', 'noasoft-ai-woocommerce' ); ?></a>
                    </div>
                </div>
                <div class="meta">
                    <div class="chip primary"><?php esc_html_e( 'tr_TR & en_US yerelleştirme', 'noasoft-ai-woocommerce' ); ?></div>
                    <div class="chip"><?php esc_html_e( 'WooCommerce uyumlu', 'noasoft-ai-woocommerce' ); ?></div>
                    <div class="chip"><?php esc_html_e( 'SweetAlert & Chart.js entegrasyonlu', 'noasoft-ai-woocommerce' ); ?></div>
                </div>
            </header>

            <section class="feature-grid">
                <article>
                    <h3><?php esc_html_e( 'AI Satış Sohbeti', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( 'Sipariş durumu, kargo takibi, stok kontrolü ve ürün bilgisi için hazır hızlı işlemler, genişletilebilir prompt yönetimi.', 'noasoft-ai-woocommerce' ); ?></p>
                </article>
                <article>
                    <h3><?php esc_html_e( 'Ürün Öneri & Karşılaştırma', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( 'Davranış bazlı öneri kartları, karşılaştırma formu, paylaşılan bağlantı ve PDF çıktılarıyla modern e-ticaret deneyimi.', 'noasoft-ai-woocommerce' ); ?></p>
                </article>
                <article>
                    <h3><?php esc_html_e( 'Admin Rapor & Öneri', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( '7/30/60/90 günlük metrik toplama, Chart.js grafikler, AI özet ve PDF indirilebilir raporlar.', 'noasoft-ai-woocommerce' ); ?></p>
                </article>
                <article>
                    <h3><?php esc_html_e( 'Görsel Araçlar', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( 'remove.bg destekli arka plan silme, WebP optimizasyonu ve medya kitaplığına otomatik ekleme.', 'noasoft-ai-woocommerce' ); ?></p>
                </article>
                <article>
                    <h3><?php esc_html_e( 'Dil & Çeviri', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( 'tr_TR ve en_US için PO/JSON içe–dışa aktarma, inline override editörü ve eklenti dili seçimi.', 'noasoft-ai-woocommerce' ); ?></p>
                </article>
                <article>
                    <h3><?php esc_html_e( 'Güvenlik & Loglama', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( 'Nonce, yetki kontrolleri, hata yakalama ve yüklenmeyecek durumlarda güvenli devre dışı bırakma.', 'noasoft-ai-woocommerce' ); ?></p>
                </article>
            </section>

            <section class="translations">
                <h3><?php esc_html_e( 'Çok Dilli Hazır', 'noasoft-ai-woocommerce' ); ?></h3>
                <p><?php esc_html_e( 'Eklenti arayüzü tr_TR ve en_US çevirileriyle gelir; admin panelinden dil dosyalarını içe aktarabilir, JSON override’ları yönetebilirsiniz.', 'noasoft-ai-woocommerce' ); ?></p>
            </section>
        </div>
        <?php
    }
}
