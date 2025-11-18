<?php
namespace ProUltra\Admin;

use WP_Ajax_Upgrader_Skin;
use WP_Filesystem_Base;
use WP_Upgrader;

/**
 * Full setup wizard with demo import and onboarding.
 */
class Setup_Wizard {
    const PAGE_SLUG       = 'pro-ultra-setup';
    const NONCE_ACTION    = 'pro_ultra_setup';
    const OPTION_COMPLETE = 'pro_ultra_setup_completed';
    const OPTION_NOTICE   = 'pro_ultra_setup_notice';

    /**
     * Boot hooks.
     */
    public static function init() {
        add_action( 'after_switch_theme', array( __CLASS__, 'flag_notice' ) );
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_notices', array( __CLASS__, 'maybe_notice' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

        add_action( 'wp_ajax_pro_ultra_setup_requirements', array( __CLASS__, 'ajax_requirements' ) );
        add_action( 'wp_ajax_pro_ultra_setup_plugins', array( __CLASS__, 'ajax_plugins' ) );
        add_action( 'wp_ajax_pro_ultra_setup_import', array( __CLASS__, 'ajax_import' ) );
        add_action( 'wp_ajax_pro_ultra_setup_home', array( __CLASS__, 'ajax_home' ) );
        add_action( 'wp_ajax_pro_ultra_setup_finish', array( __CLASS__, 'ajax_finish' ) );
    }

    /**
     * Mark setup wizard as pending after theme switch.
     */
    public static function flag_notice() {
        update_option( self::OPTION_NOTICE, 1 );
    }

    /**
     * Admin notice prompting the wizard.
     */
    public static function maybe_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( get_option( self::OPTION_COMPLETE ) ) {
            return;
        }

        if ( ! get_option( self::OPTION_NOTICE ) ) {
            return;
        }

        if ( isset( $_GET['page'] ) && self::PAGE_SLUG === sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $url = esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
        echo '<div class="notice notice-info is-dismissible pro-ultra-setup-notice">';
        echo '<p><strong>' . esc_html__( 'Pro Ultra AI WooTheme', 'pro-ultra-ai' ) . '</strong> ' . esc_html__( 'kurulum sihirbazı hazır. Demo içerik ve zorunlu eklentileri yüklemek için sihirbazı başlatın.', 'pro-ultra-ai' ) . '</p>';
        echo '<p><a class="button button-primary" href="' . $url . '">' . esc_html__( 'Setup Wizard başlat', 'pro-ultra-ai' ) . '</a></p>';
        echo '</div>';
    }

    /**
     * Register hidden dashboard page.
     */
    public static function register_menu() {
        add_dashboard_page(
            esc_html__( 'Pro Ultra Setup', 'pro-ultra-ai' ),
            esc_html__( 'Pro Ultra Setup', 'pro-ultra-ai' ),
            'manage_options',
            self::PAGE_SLUG,
            array( __CLASS__, 'render' )
        );
    }

    /**
     * Enqueue admin assets for wizard only.
     */
    public static function enqueue_assets( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'dashboard_page_' . self::PAGE_SLUG !== $screen->id ) {
            return;
        }

        wp_enqueue_style( 'pro-ultra-setup', PRO_ULTRA_AI_URI . 'assets/css/setup-wizard.css', array(), PRO_ULTRA_AI_VERSION );
        wp_enqueue_script( 'pro-ultra-setup', PRO_ULTRA_AI_URI . 'assets/js/setup-wizard.js', array( 'jquery', 'wp-util' ), PRO_ULTRA_AI_VERSION, true );
        wp_localize_script(
            'pro-ultra-setup',
            'proUltraSetup',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( self::NONCE_ACTION ),
                'completed' => (bool) get_option( self::OPTION_COMPLETE ),
                'options'   => admin_url( 'admin.php?page=' . Theme_Options::MENU_SLUG ),
                'viewSite'  => home_url( '/' ),
                'texts'     => array(
                    'checking'   => esc_html__( 'Kontrol ediliyor...', 'pro-ultra-ai' ),
                    'installing' => esc_html__( 'Kuruluyor...', 'pro-ultra-ai' ),
                    'importing'  => esc_html__( 'İçe aktarılıyor...', 'pro-ultra-ai' ),
                    'saving'     => esc_html__( 'Kaydediliyor...', 'pro-ultra-ai' ),
                    'done'       => esc_html__( 'Tamamlandı', 'pro-ultra-ai' ),
                ),
            )
        );
    }

    /**
     * Render wizard UI.
     */
    public static function render() {
        $steps = array(
            'welcome'       => __( 'Hoş geldiniz', 'pro-ultra-ai' ),
            'requirements'  => __( 'Sistem Gereksinimleri', 'pro-ultra-ai' ),
            'plugins'       => __( 'Gerekli Eklentiler', 'pro-ultra-ai' ),
            'import'        => __( 'Demo İçerik', 'pro-ultra-ai' ),
            'homepage'      => __( 'Ana Sayfa Kurulumu', 'pro-ultra-ai' ),
            'finish'        => __( 'Tamamlandı', 'pro-ultra-ai' ),
        );
        ?>
        <div class="wrap pro-ultra-setup-wrap">
            <h1><?php esc_html_e( 'Pro Ultra Kurulum Sihirbazı', 'pro-ultra-ai' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Adım adım eklenti kurulumu, demo içerik ve tema ayarlarını tamamlayın.', 'pro-ultra-ai' ); ?></p>
            <div class="pro-ultra-steps">
                <?php $index = 1; foreach ( $steps as $slug => $label ) : ?>
                    <div class="pro-ultra-step-item" data-step="<?php echo esc_attr( $slug ); ?>">
                        <span class="pro-ultra-step-index"><?php echo esc_html( $index ); ?></span>
                        <span class="pro-ultra-step-label"><?php echo esc_html( $label ); ?></span>
                        <span class="pro-ultra-step-status" aria-hidden="true"></span>
                    </div>
                <?php $index++; endforeach; ?>
            </div>

            <div class="pro-ultra-step-panels">
                <section class="pro-ultra-panel active" data-step="welcome">
                    <div class="pro-ultra-card">
                        <h2><?php esc_html_e( 'Başlayalım', 'pro-ultra-ai' ); ?></h2>
                        <p><?php esc_html_e( 'Kurulum sihirbazı WooCommerce eklentisini kuracak, demo içerikleri ekleyecek ve ana sayfa bloklarını ayarlayacak.', 'pro-ultra-ai' ); ?></p>
                        <button class="button button-primary pro-ultra-next" data-next="requirements"><?php esc_html_e( 'Başlat', 'pro-ultra-ai' ); ?></button>
                    </div>
                </section>

                <section class="pro-ultra-panel" data-step="requirements">
                    <div class="pro-ultra-card">
                        <h2><?php esc_html_e( 'Sistem Gereksinimleri', 'pro-ultra-ai' ); ?></h2>
                        <p><?php esc_html_e( 'Sunucu gereksinimlerinizi otomatik kontrol edin.', 'pro-ultra-ai' ); ?></p>
                        <div class="pro-ultra-results" id="pro-ultra-req-results"></div>
                        <div class="pro-ultra-actions">
                            <button class="button pro-ultra-prev" data-prev="welcome"><?php esc_html_e( 'Geri', 'pro-ultra-ai' ); ?></button>
                            <button class="button button-primary pro-ultra-run" data-action="requirements" data-next="plugins" data-loading-text="<?php esc_attr_e( 'Kontrol ediliyor...', 'pro-ultra-ai' ); ?>"><?php esc_html_e( 'Kontrol Et', 'pro-ultra-ai' ); ?></button>
                        </div>
                    </div>
                </section>

                <section class="pro-ultra-panel" data-step="plugins">
                    <div class="pro-ultra-card">
                        <h2><?php esc_html_e( 'Gerekli Eklentiler', 'pro-ultra-ai' ); ?></h2>
                        <p><?php esc_html_e( 'WooCommerce kurulum ve etkinleştirme işlemleri.', 'pro-ultra-ai' ); ?></p>
                        <div class="pro-ultra-results" id="pro-ultra-plugin-results"></div>
                        <div class="pro-ultra-actions">
                            <button class="button pro-ultra-prev" data-prev="requirements"><?php esc_html_e( 'Geri', 'pro-ultra-ai' ); ?></button>
                            <button class="button button-primary pro-ultra-run" data-action="plugins" data-next="import" data-loading-text="<?php esc_attr_e( 'Kuruluyor...', 'pro-ultra-ai' ); ?>"><?php esc_html_e( 'WooCommerce Kur', 'pro-ultra-ai' ); ?></button>
                        </div>
                    </div>
                </section>

                <section class="pro-ultra-panel" data-step="import">
                    <div class="pro-ultra-card">
                        <h2><?php esc_html_e( 'Demo İçerik', 'pro-ultra-ai' ); ?></h2>
                        <p><?php esc_html_e( 'Örnek sayfalar, ürünler, menüler ve slider verilerini içe aktar.', 'pro-ultra-ai' ); ?></p>
                        <div class="pro-ultra-results" id="pro-ultra-import-results"></div>
                        <div class="pro-ultra-actions">
                            <button class="button pro-ultra-prev" data-prev="plugins"><?php esc_html_e( 'Geri', 'pro-ultra-ai' ); ?></button>
                            <button class="button button-primary pro-ultra-run" data-action="import" data-next="homepage" data-loading-text="<?php esc_attr_e( 'İçe aktarılıyor...', 'pro-ultra-ai' ); ?>"><?php esc_html_e( 'Demo İçeriği Yükle', 'pro-ultra-ai' ); ?></button>
                        </div>
                    </div>
                </section>

                <section class="pro-ultra-panel" data-step="homepage">
                    <div class="pro-ultra-card">
                        <h2><?php esc_html_e( 'Ana Sayfa ve Ayarlar', 'pro-ultra-ai' ); ?></h2>
                        <p><?php esc_html_e( 'Statik ana sayfa, menüler ve tema düzenleri uygulanacak.', 'pro-ultra-ai' ); ?></p>
                        <div class="pro-ultra-results" id="pro-ultra-home-results"></div>
                        <div class="pro-ultra-actions">
                            <button class="button pro-ultra-prev" data-prev="import"><?php esc_html_e( 'Geri', 'pro-ultra-ai' ); ?></button>
                            <button class="button button-primary pro-ultra-run" data-action="home" data-next="finish" data-loading-text="<?php esc_attr_e( 'Kaydediliyor...', 'pro-ultra-ai' ); ?>"><?php esc_html_e( 'Ayarları Uygula', 'pro-ultra-ai' ); ?></button>
                        </div>
                    </div>
                </section>

                <section class="pro-ultra-panel" data-step="finish">
                    <div class="pro-ultra-card success">
                        <h2><?php esc_html_e( 'Kurulum Tamamlandı', 'pro-ultra-ai' ); ?></h2>
                        <p><?php esc_html_e( 'Sihirbaz başarıyla tamamlandı. Siteyi görüntüleyebilir veya tema ayarlarına geçebilirsiniz.', 'pro-ultra-ai' ); ?></p>
                        <div class="pro-ultra-actions">
                            <button class="button pro-ultra-run" data-action="finish" data-loading-text="<?php esc_attr_e( 'Kaydediliyor...', 'pro-ultra-ai' ); ?>"><?php esc_html_e( 'Kurulumu tamamla', 'pro-ultra-ai' ); ?></button>
                            <a class="button button-secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Siteyi Görüntüle', 'pro-ultra-ai' ); ?></a>
                            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Theme_Options::MENU_SLUG ) ); ?>"><?php esc_html_e( 'Tema Ayarlarına Git', 'pro-ultra-ai' ); ?></a>
                        </div>
                    </div>
                </section>
            </div>
            <input type="hidden" id="pro-ultra-setup-nonce" value="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>" />
        </div>
        <?php
    }

    /**
     * Requirement check handler.
     */
    public static function ajax_requirements() {
        self::verify_permissions();
        $requirements = array();

        $php_version = phpversion();
        $requirements[] = array(
            'label'   => __( 'PHP 8.0 veya üzeri', 'pro-ultra-ai' ),
            'status'  => version_compare( $php_version, '8.0', '>=' ),
            'message' => sprintf( __( 'Mevcut: %s', 'pro-ultra-ai' ), esc_html( $php_version ) ),
        );

        $requirements[] = array(
            'label'   => __( 'cURL eklentisi', 'pro-ultra-ai' ),
            'status'  => function_exists( 'curl_init' ),
            'message' => function_exists( 'curl_init' ) ? __( 'Aktif', 'pro-ultra-ai' ) : __( 'cURL devre dışı', 'pro-ultra-ai' ),
        );

        $requirements[] = array(
            'label'   => __( 'JSON desteği', 'pro-ultra-ai' ),
            'status'  => function_exists( 'json_encode' ),
            'message' => function_exists( 'json_encode' ) ? __( 'Aktif', 'pro-ultra-ai' ) : __( 'JSON desteği bulunamadı', 'pro-ultra-ai' ),
        );

        $requirements[] = array(
            'label'   => __( 'mbstring eklentisi', 'pro-ultra-ai' ),
            'status'  => extension_loaded( 'mbstring' ),
            'message' => extension_loaded( 'mbstring' ) ? __( 'Aktif', 'pro-ultra-ai' ) : __( 'mbstring devre dışı', 'pro-ultra-ai' ),
        );

        $filesystem = self::get_filesystem();
        $requirements[] = array(
            'label'   => __( 'Dosya yazma izni', 'pro-ultra-ai' ),
            'status'  => $filesystem instanceof WP_Filesystem_Base,
            'message' => $filesystem instanceof WP_Filesystem_Base ? __( 'Yazma izni mevcut', 'pro-ultra-ai' ) : __( 'Dosya sistemi erişilemiyor', 'pro-ultra-ai' ),
        );

        $pass = true;
        foreach ( $requirements as $req ) {
            if ( empty( $req['status'] ) ) {
                $pass = false;
                break;
            }
        }

        wp_send_json_success(
            array(
                'requirements' => $requirements,
                'pass'         => $pass,
            )
        );
    }

    /**
     * Install/activate WooCommerce.
     */
    public static function ajax_plugins() {
        self::verify_permissions();
        $results = array();
        $slug    = 'woocommerce';
        $file    = 'woocommerce/woocommerce.php';

        if ( self::is_plugin_active( $file ) ) {
            $results[] = array(
                'label'   => __( 'WooCommerce etkin', 'pro-ultra-ai' ),
                'status'  => true,
                'message' => __( 'Zaten aktif.', 'pro-ultra-ai' ),
            );
            wp_send_json_success( array( 'plugins' => $results ) );
        }

        if ( file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
            $activated = activate_plugin( $file );
            $results[] = array(
                'label'   => __( 'WooCommerce etkinleştirildi', 'pro-ultra-ai' ),
                'status'  => ! is_wp_error( $activated ),
                'message' => is_wp_error( $activated ) ? $activated->get_error_message() : __( 'Başarılı.', 'pro-ultra-ai' ),
            );
            wp_send_json_success( array( 'plugins' => $results ) );
        }

        $install = self::install_plugin( $slug );
        $results[] = array(
            'label'   => __( 'WooCommerce kurulumu', 'pro-ultra-ai' ),
            'status'  => $install['success'],
            'message' => $install['message'],
        );

        if ( $install['success'] ) {
            $activate = activate_plugin( $file );
            $results[] = array(
                'label'   => __( 'WooCommerce etkinleştirildi', 'pro-ultra-ai' ),
                'status'  => ! is_wp_error( $activate ),
                'message' => is_wp_error( $activate ) ? $activate->get_error_message() : __( 'Başarılı.', 'pro-ultra-ai' ),
            );
        }

        wp_send_json_success( array( 'plugins' => $results ) );
    }

    /**
     * Import demo content (pages, products, menus, options).
     */
    public static function ajax_import() {
        self::verify_permissions();

        if ( ! self::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce etkin değil.', 'pro-ultra-ai' ) ) );
        }

        $pages    = self::import_pages();
        $products = self::import_products();
        $menus    = self::import_menus( $pages );
        $options  = self::seed_options();

        wp_send_json_success(
            array(
                'pages'    => $pages,
                'products' => $products,
                'menus'    => $menus,
                'options'  => $options,
            )
        );
    }

    /**
     * Homepage & layout application.
     */
    public static function ajax_home() {
        self::verify_permissions();
        $home_id = get_option( 'pro_ultra_home_page_id' );
        if ( ! $home_id ) {
            $page = get_page_by_path( 'ana-sayfa' );
            if ( $page ) {
                $home_id = $page->ID;
            }
        }

        if ( $home_id ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $home_id );
        }

        $blog = get_page_by_path( 'blog' );
        if ( $blog ) {
            update_option( 'page_for_posts', $blog->ID );
        }

        // Ensure default blocks and layout are persisted for frontend usage.
        update_option( Theme_Options::OPTION_HOME, Theme_Options::get_home_blocks() );
        update_option( Theme_Options::OPTION_LAYOUT, Theme_Options::sanitize_layout_settings( Theme_Options::get_layout_settings() ) );
        update_option( self::OPTION_NOTICE, 0 );

        wp_send_json_success(
            array(
                'message' => __( 'Ana sayfa ve düzenler uygulandı.', 'pro-ultra-ai' ),
                'home_id' => $home_id,
            )
        );
    }

    /**
     * Mark wizard finished.
     */
    public static function ajax_finish() {
        self::verify_permissions();
        update_option( self::OPTION_COMPLETE, 1 );
        update_option( self::OPTION_NOTICE, 0 );
        wp_send_json_success( array( 'message' => __( 'Kurulum tamamlandı.', 'pro-ultra-ai' ) ) );
    }

    /**
     * Verify capability and nonce.
     */
    protected static function verify_permissions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Yetkiniz yok.', 'pro-ultra-ai' ) ), 403 );
        }

        check_ajax_referer( self::NONCE_ACTION, 'security' );
    }

    /**
     * Lightweight filesystem accessor.
     */
    protected static function get_filesystem() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $creds = request_filesystem_credentials( admin_url() );
        if ( ! WP_Filesystem( $creds ) ) {
            return null;
        }
        global $wp_filesystem;
        return $wp_filesystem;
    }

    /**
     * Check plugin active state.
     */
    protected static function is_plugin_active( $plugin_file ) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        return function_exists( '\is_plugin_active' ) && is_plugin_active( $plugin_file );
    }

    /**
     * Install plugin from WordPress.org repository.
     */
    protected static function install_plugin( $slug ) {
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/file.php';

        $api = plugins_api( 'plugin_information', array(
            'slug'   => $slug,
            'fields' => array( 'sections' => false ),
        ) );

        if ( is_wp_error( $api ) ) {
            return array(
                'success' => false,
                'message' => $api->get_error_message(),
            );
        }

        $skin      = new WP_Ajax_Upgrader_Skin();
        $upgrader  = new WP_Upgrader( $skin );
        $installed = $upgrader->install( $api->download_link );

        if ( is_wp_error( $installed ) ) {
            return array(
                'success' => false,
                'message' => $installed->get_error_message(),
            );
        }

        return array(
            'success' => true,
            'message' => __( 'Kurulum tamamlandı.', 'pro-ultra-ai' ),
        );
    }

    /**
     * Seed pages.
     */
    protected static function import_pages() {
        $created = array();
        $pages   = array(
            array( 'title' => __( 'Ana Sayfa', 'pro-ultra-ai' ), 'slug' => 'ana-sayfa', 'content' => '[pro_ultra_home_demo]' ),
            array( 'title' => __( 'Blog', 'pro-ultra-ai' ), 'slug' => 'blog', 'content' => '' ),
            array( 'title' => __( 'İletişim', 'pro-ultra-ai' ), 'slug' => 'iletisim', 'content' => '[contact-form-7]' ),
            array( 'title' => __( 'Hesabım', 'pro-ultra-ai' ), 'slug' => 'hesabim', 'content' => '[woocommerce_my_account]' ),
        );

        foreach ( $pages as $page ) {
            $existing = get_page_by_path( $page['slug'] );
            if ( $existing ) {
                $created[ $page['slug'] ] = $existing->ID;
                continue;
            }
            $id = wp_insert_post(
                array(
                    'post_title'   => $page['title'],
                    'post_name'    => $page['slug'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => $page['content'],
                )
            );
            if ( ! is_wp_error( $id ) ) {
                $created[ $page['slug'] ] = $id;
                if ( 'ana-sayfa' === $page['slug'] ) {
                    update_option( 'pro_ultra_home_page_id', $id );
                }
            }
        }

        return $created;
    }

    /**
     * Seed menu and assign locations.
     */
    protected static function import_menus( $pages ) {
        $menu_name = 'Pro Ultra Menü';
        $menu      = wp_get_nav_menu_object( $menu_name );
        if ( ! $menu ) {
            $menu_id = wp_create_nav_menu( $menu_name );
        } else {
            $menu_id = $menu->term_id;
        }

        if ( ! empty( $pages['ana-sayfa'] ) ) {
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'  => __( 'Ana Sayfa', 'pro-ultra-ai' ),
                'menu-item-object' => 'page',
                'menu-item-object-id' => $pages['ana-sayfa'],
                'menu-item-type'   => 'post_type',
                'menu-item-status' => 'publish',
            ) );
        }

        if ( ! empty( $pages['blog'] ) ) {
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'  => __( 'Blog', 'pro-ultra-ai' ),
                'menu-item-object' => 'page',
                'menu-item-object-id' => $pages['blog'],
                'menu-item-type'   => 'post_type',
                'menu-item-status' => 'publish',
            ) );
        }

        if ( ! empty( $pages['hesabim'] ) ) {
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'  => __( 'Hesabım', 'pro-ultra-ai' ),
                'menu-item-object' => 'page',
                'menu-item-object-id' => $pages['hesabim'],
                'menu-item-type'   => 'post_type',
                'menu-item-status' => 'publish',
            ) );
        }

        $locations              = get_theme_mod( 'nav_menu_locations', array() );
        $locations['primary']   = $menu_id;
        $locations['secondary'] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );

        return array(
            'menu_id'   => $menu_id,
            'menu_name' => $menu_name,
        );
    }

    /**
     * Seed sample WooCommerce products.
     */
    protected static function import_products() {
        $created = array();
        if ( ! class_exists( '\WC_Product_Simple' ) ) {
            return array( 'error' => __( 'WooCommerce ürün sınıfları bulunamadı.', 'pro-ultra-ai' ) );
        }

        $categories = array();
        $cat_names  = array(
            'elektronik' => __( 'Elektronik', 'pro-ultra-ai' ),
            'giyim'      => __( 'Giyim', 'pro-ultra-ai' ),
        );

        foreach ( $cat_names as $slug => $name ) {
            $term = term_exists( $slug, 'product_cat' );
            if ( ! $term ) {
                $term = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
            }
            if ( ! is_wp_error( $term ) ) {
                $categories[ $slug ] = (int) $term['term_id'];
            }
        }

        $products = array(
            array(
                'name'        => __( 'AI Kulaklık Pro', 'pro-ultra-ai' ),
                'price'       => '149.90',
                'sku'         => 'AI-HP-01',
                'stock'       => 30,
                'category'    => isset( $categories['elektronik'] ) ? $categories['elektronik'] : 0,
                'description' => __( 'Gürültü engelleme ve AI ekolayzer profilleri ile premium kulaklık.', 'pro-ultra-ai' ),
            ),
            array(
                'name'        => __( 'Akıllı Koşu Ayakkabısı', 'pro-ultra-ai' ),
                'price'       => '89.00',
                'sku'         => 'AI-SHOE-01',
                'stock'       => 50,
                'category'    => isset( $categories['giyim'] ) ? $categories['giyim'] : 0,
                'description' => __( 'Hafif taban, nefes alan kumaş ve AI destekli konfor analizi.', 'pro-ultra-ai' ),
            ),
        );

        foreach ( $products as $data ) {
            if ( function_exists( '\wc_get_product_id_by_sku' ) && wc_get_product_id_by_sku( $data['sku'] ) ) {
                $created[] = array( 'name' => $data['name'], 'id' => wc_get_product_id_by_sku( $data['sku'] ) );
                continue;
            }

            $product = new \WC_Product_Simple();
            $product->set_name( $data['name'] );
            $product->set_regular_price( $data['price'] );
            $product->set_sku( $data['sku'] );
            $product->set_manage_stock( true );
            $product->set_stock_quantity( $data['stock'] );
            $product->set_description( $data['description'] );
            $product->set_short_description( wp_trim_words( $data['description'], 20 ) );
            if ( ! empty( $data['category'] ) ) {
                $product->set_category_ids( array( $data['category'] ) );
            }
            $id = $product->save();
            if ( $id ) {
                $created[] = array( 'name' => $data['name'], 'id' => $id );
            }
        }

        // Variable example.
        if ( class_exists( '\WC_Product_Variable' ) ) {
            $variable = new \WC_Product_Variable();
            $variable->set_name( __( 'AI Performans Tişörtü', 'pro-ultra-ai' ) );
            $variable->set_sku( 'AI-TEE-01' );
            $variable->set_description( __( 'Ter tutmayan, hafif kumaşlı ve AI hareket analiziyle uyumlu performans tişörtü.', 'pro-ultra-ai' ) );
            $variable->set_manage_stock( false );
            if ( isset( $categories['giyim'] ) ) {
                $variable->set_category_ids( array( $categories['giyim'] ) );
            }
            $attr_id = wc_attribute_taxonomy_id_by_name( 'pa_beden' );
            if ( ! $attr_id ) {
                $attr_id = wc_create_attribute(
                    array(
                        'slug' => 'beden',
                        'name' => __( 'Beden', 'pro-ultra-ai' ),
                    )
                );
            }
            if ( ! is_wp_error( $attr_id ) && $attr_id ) {
                $taxonomy = wc_attribute_taxonomy_name_by_id( $attr_id );
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    register_taxonomy( $taxonomy, 'product', array( 'label' => __( 'Beden', 'pro-ultra-ai' ) ) );
                }
                $attribute = new \WC_Product_Attribute();
                $attribute->set_id( $attr_id );
                $attribute->set_name( $taxonomy );
                $attribute->set_options( array( 'S', 'M', 'L' ) );
                $attribute->set_visible( true );
                $attribute->set_variation( true );
                $variable->set_attributes( array( $attribute ) );
            }
            $variable_id = $variable->save();
            if ( $variable_id ) {
                if ( isset( $taxonomy ) ) {
                    foreach ( array( 'S', 'M', 'L' ) as $size ) {
                        if ( ! term_exists( $size, $taxonomy ) ) {
                            wp_insert_term( $size, $taxonomy );
                        }
                    }
                    wp_set_object_terms( $variable_id, array( 'S', 'M', 'L' ), $taxonomy );
                }
                $created[] = array( 'name' => __( 'AI Performans Tişörtü', 'pro-ultra-ai' ), 'id' => $variable_id );
            }
        }

        return $created;
    }

    /**
     * Seed theme options and slider data.
     */
    protected static function seed_options() {
        $colors = Theme_Options::sanitize_color_settings( Theme_Options::get_color_settings() );
        update_option( Theme_Options::OPTION_COLORS, $colors );

        $layout = Theme_Options::sanitize_layout_settings( Theme_Options::get_layout_settings() );
        update_option( Theme_Options::OPTION_LAYOUT, $layout );

        $ai = Theme_Options::sanitize_ai_settings( Theme_Options::get_ai_settings() );
        update_option( Theme_Options::OPTION_AI, $ai );

        $home_blocks = Theme_Options::get_home_blocks();
        update_option( Theme_Options::OPTION_HOME, $home_blocks );

        $slider_items = array(
            array(
                'title'       => __( 'Yeni Sezon AI Koleksiyonu', 'pro-ultra-ai' ),
                'description' => __( 'AI destekli önerilerle kişiselleştirilmiş alışveriş deneyimi.', 'pro-ultra-ai' ),
                'cta'         => __( 'Hemen keşfet', 'pro-ultra-ai' ),
                'link'        => home_url( '/' ),
            ),
            array(
                'title'       => __( 'Pro Ultra Fırsatları', 'pro-ultra-ai' ),
                'description' => __( 'Çok satanlar ve indirimdekiler için akıllı filtreler.', 'pro-ultra-ai' ),
                'cta'         => __( 'Alışverişe başla', 'pro-ultra-ai' ),
                'link'        => home_url( '/shop' ),
            ),
        );
        update_option( 'pro_ultra_slider_items', $slider_items );

        return array(
            'colors' => $colors,
            'layout' => $layout,
            'home'   => $home_blocks,
            'slider' => count( $slider_items ),
        );
    }
}
