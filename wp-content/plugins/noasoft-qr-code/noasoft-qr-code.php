<?php
/**
 * Plugin Name: NoaSoft QR Code
 * Plugin URI:  https://qrcode.noasoft.org
 * Description: Responsive QR code generator and management plugin powered by NoaSoft API.
 * Version:     1.0.0
 * Author:      NoaSoft
 * Author URI:  https://qrcode.noasoft.org
 * Text Domain: noasoft-qr-code
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'NoaSoft_QR_Code_Plugin' ) ) {
    class NoaSoft_QR_Code_Plugin {
        const OPTION_SETTINGS = 'noasoft_qr_settings';
        const OPTION_STATS    = 'noasoft_qr_stats';
        const OPTION_LIMIT    = 'noasoft_qr_remaining';

        private static $instance = null;
        private $translations    = [];
        private $locale          = 'en';

        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            $this->load_translations();
            add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
            add_action( 'init', [ $this, 'register_shortcodes' ] );
            add_action( 'widgets_init', [ $this, 'register_widget' ] );
            add_action( 'wp_ajax_noasoft_qr_create', [ $this, 'handle_qr_create' ] );
            add_action( 'wp_ajax_nopriv_noasoft_qr_create', [ $this, 'handle_qr_create' ] );
            add_action( 'wp_ajax_noasoft_qr_stats', [ $this, 'handle_stats_request' ] );
            add_action( 'admin_init', [ $this, 'maybe_register_settings' ] );
        }

        private function load_translations() {
            $default_locale = determine_locale();
            $this->locale   = substr( $default_locale, 0, 2 );
            $file           = plugin_dir_path( __FILE__ ) . 'languages/translations.json';
            if ( file_exists( $file ) ) {
                $json = file_get_contents( $file );
                $data = json_decode( $json, true );
                if ( is_array( $data ) ) {
                    $this->translations = $data;
                }
            }
            if ( empty( $this->translations[ $this->locale ] ) ) {
                $this->locale = 'en';
            }
        }

        public function translate( $key ) {
            if ( ! empty( $this->translations[ $this->locale ][ $key ] ) ) {
                return $this->translations[ $this->locale ][ $key ];
            }
            if ( ! empty( $this->translations['en'][ $key ] ) ) {
                return $this->translations['en'][ $key ];
            }
            return $key;
        }

        public function get_locale() {
            return $this->locale;
        }

        public function get_translations() {
            return $this->translations;
        }

        public function register_admin_menu() {
            $menu_title = $this->translate( 'menu_title' );
            $page_title = $this->translate( 'page_title' );
            $capability = 'manage_options';
            $slug       = 'noasoft-qr-code';
            add_menu_page(
                $page_title,
                $menu_title,
                $capability,
                $slug,
                [ $this, 'render_dashboard_page' ],
                'data:image/svg+xml;base64,' . base64_encode( $this->get_menu_icon_svg() ),
                56
            );
            add_submenu_page( $slug, $this->translate( 'menu_settings' ), $this->translate( 'menu_settings' ), $capability, $slug, [ $this, 'render_dashboard_page' ] );
            add_submenu_page( $slug, $this->translate( 'menu_create' ), $this->translate( 'menu_create' ), $capability, 'noasoft-qr-create', [ $this, 'render_create_page' ] );
            add_submenu_page( $slug, $this->translate( 'menu_shortcodes' ), $this->translate( 'menu_shortcodes' ), $capability, 'noasoft-qr-shortcodes', [ $this, 'render_shortcode_page' ] );
        }

        private function get_menu_icon_svg() {
            $label = strtoupper( $this->locale );
            $svg   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="12" fill="#2563eb"/><text x="50%" y="52%" dominant-baseline="middle" text-anchor="middle" fill="#fff" font-size="20" font-weight="bold" font-family="Arial">' . esc_html( $label ) . '</text></svg>';
            return $svg;
        }

        public function maybe_register_settings() {
            register_setting( 'noasoft_qr_settings_group', self::OPTION_SETTINGS );
        }

        public function enqueue_admin_assets( $hook ) {
            if ( strpos( $hook, 'noasoft-qr' ) === false ) {
                return;
            }
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'noasoft-qr-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3' );
            wp_enqueue_style( 'noasoft-qr-dropzone', 'https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css', [], '5.9.3' );
            wp_enqueue_style( 'noasoft-qr-admin', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css', [], '1.0.0' );

            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wp-color-picker' );
            wp_enqueue_script( 'noasoft-qr-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [ 'jquery' ], '5.3.3', true );
            wp_enqueue_script( 'noasoft-qr-dropzone', 'https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js', [], '5.9.3', true );
            wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js', [], '4.4.4', true );
            wp_enqueue_script( 'noasoft-qr-admin', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', [ 'jquery', 'wp-color-picker', 'noasoft-qr-bootstrap', 'noasoft-qr-dropzone', 'chartjs' ], '1.0.0', true );
            wp_localize_script( 'noasoft-qr-admin', 'NoaSoftQR', [
                'ajax_url'      => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'noasoft_qr_nonce' ),
                'translations'  => $this->get_translations(),
                'locale'        => $this->get_locale(),
                'remaining'     => get_option( self::OPTION_LIMIT, '' ),
                'settings'      => get_option( self::OPTION_SETTINGS, [] ),
                'chart_strings' => [
                    'daily'   => $this->translate( 'chart_daily' ),
                    'weekly'  => $this->translate( 'chart_weekly' ),
                    'monthly' => $this->translate( 'chart_monthly' ),
                    'yearly'  => $this->translate( 'chart_yearly' ),
                ],
            ] );
        }

        public function enqueue_public_assets() {
            wp_enqueue_style( 'noasoft-qr-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3' );
            wp_enqueue_style( 'noasoft-qr-dropzone', 'https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css', [], '5.9.3' );
            wp_enqueue_style( 'noasoft-qr-public', plugin_dir_url( __FILE__ ) . 'assets/css/public.css', [], '1.0.0' );
            wp_enqueue_script( 'noasoft-qr-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [ 'jquery' ], '5.3.3', true );
            wp_enqueue_script( 'noasoft-qr-dropzone', 'https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js', [], '5.9.3', true );
            wp_enqueue_script( 'noasoft-qr-public', plugin_dir_url( __FILE__ ) . 'assets/js/public.js', [ 'jquery', 'noasoft-qr-bootstrap', 'noasoft-qr-dropzone' ], '1.0.0', true );
            wp_localize_script( 'noasoft-qr-public', 'NoaSoftQRPublic', [
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'noasoft_qr_nonce' ),
                'translations' => $this->get_translations(),
                'locale'       => $this->get_locale(),
                'settings'     => get_option( self::OPTION_SETTINGS, [] ),
            ] );
        }

        public function register_shortcodes() {
            add_shortcode( 'noasoft_qr_form', [ $this, 'render_frontend_form' ] );
            add_shortcode( 'noasoft_qr_api_guide', [ $this, 'render_api_guide' ] );
        }

        public function register_widget() {
            register_widget( 'NoaSoft_QR_Widget' );
        }

        public function render_dashboard_page() {
            $settings = get_option( self::OPTION_SETTINGS, [] );
            include plugin_dir_path( __FILE__ ) . 'includes/admin-settings.php';
        }

        public function render_create_page() {
            include plugin_dir_path( __FILE__ ) . 'includes/admin-create.php';
        }

        public function render_shortcode_page() {
            include plugin_dir_path( __FILE__ ) . 'includes/admin-shortcodes.php';
        }

        public function render_frontend_form( $atts = [] ) {
            ob_start();
            include plugin_dir_path( __FILE__ ) . 'includes/public-form.php';
            return ob_get_clean();
        }

        public function render_api_guide() {
            ob_start();
            include plugin_dir_path( __FILE__ ) . 'includes/public-api-guide.php';
            return ob_get_clean();
        }

        public function handle_qr_create() {
            check_ajax_referer( 'noasoft_qr_nonce', 'nonce' );
            $settings = get_option( self::OPTION_SETTINGS, [] );
            $token    = ! empty( $settings['token'] ) ? $settings['token'] : '';

            if ( empty( $token ) ) {
                wp_send_json_error(
                    [
                        'message' => $this->translate( 'error_token_missing' ),
                    ]
                );
            }

            $payload    = wp_unslash( $_POST );
            $api_url    = 'https://qrcode.noasoft.org/api/v1/qr';
            $formats    = isset( $payload['formats'] ) ? (array) $payload['formats'] : [ 'png' ];
            $cleaned    = [];
            $map_fields = [
                'type'                => 'sanitize_text_field',
                'url'                 => 'sanitize_text_field',
                'text_content'        => 'sanitize_textarea_field',
                'email_address'       => 'sanitize_email',
                'phone_number'        => 'sanitize_text_field',
                'sms_number'          => 'sanitize_text_field',
                'sms_message'         => 'sanitize_textarea_field',
                'whatsapp_number'     => 'sanitize_text_field',
                'whatsapp_message'    => 'sanitize_textarea_field',
                'wifi_ssid'           => 'sanitize_text_field',
                'wifi_password'       => 'sanitize_text_field',
                'wifi_encryption'     => 'sanitize_text_field',
                'wifi_hidden'         => 'sanitize_text_field',
                'location_lat'        => 'sanitize_text_field',
                'location_lng'        => 'sanitize_text_field',
                'location_label'      => 'sanitize_text_field',
                'event_title'         => 'sanitize_text_field',
                'event_location'      => 'sanitize_text_field',
                'event_description'   => 'sanitize_textarea_field',
                'event_start'         => 'sanitize_text_field',
                'event_end'           => 'sanitize_text_field',
                'background'          => 'sanitize_hex_color',
                'color'               => 'sanitize_hex_color',
                'aspect_ratio'        => 'sanitize_text_field',
                'logo_url'            => 'sanitize_textarea_field',
            ];

            foreach ( $map_fields as $field => $callback ) {
                if ( isset( $payload[ $field ] ) && '' !== $payload[ $field ] ) {
                    $cleaned[ $field ] = call_user_func( $callback, $payload[ $field ] );
                }
            }

            $cleaned['type']          = $cleaned['type'] ?? 'url';
            $cleaned['background']    = $cleaned['background'] ?? '#ffffff';
            $cleaned['color']         = $cleaned['color'] ?? '#000000';
            $cleaned['aspect_ratio']  = $cleaned['aspect_ratio'] ?? '1:1';
            $cleaned['token']                 = $token;
            $cleaned['width']                 = isset( $payload['width'] ) ? intval( $payload['width'] ) : 400;
            $cleaned['height']                = isset( $payload['height'] ) ? intval( $payload['height'] ) : 400;
            $cleaned['background_transparent'] = ! empty( $payload['background_transparent'] );
            $cleaned['formats']               = array_map( 'sanitize_text_field', $formats );
            $cleaned['output']                = 'json';

            $args = [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'body'    => wp_json_encode( $cleaned ),
                'timeout' => 30,
            ];

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                wp_send_json_error( [
                    'message' => $this->translate( 'error_request' ),
                ] );
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            if ( empty( $data ) || ( isset( $data['status'] ) && 'success' !== $data['status'] ) ) {
                $message = $data['message'] ?? $this->translate( 'error_unknown' );
                wp_send_json_error( [ 'message' => $message ] );
            }

            if ( isset( $data['remaining'] ) ) {
                update_option( self::OPTION_LIMIT, intval( $data['remaining'] ) );
            }

            $this->log_stat_entry();

            wp_send_json_success( [
                'message'   => $this->translate( 'success_generated' ),
                'downloads' => $data['downloads'] ?? [],
                'embed_url' => $data['embed_url'] ?? '',
                'remaining' => $data['remaining'] ?? '',
            ] );
        }

        private function log_stat_entry() {
            $stats     = get_option( self::OPTION_STATS, [] );
            $timestamp = current_time( 'timestamp' );
            $stats[]   = $timestamp;
            update_option( self::OPTION_STATS, $stats );
        }

        public function handle_stats_request() {
            check_ajax_referer( 'noasoft_qr_nonce', 'nonce' );
            $stats = get_option( self::OPTION_STATS, [] );
            $data  = [
                'daily'   => $this->group_stats( $stats, 'day' ),
                'weekly'  => $this->group_stats( $stats, 'week' ),
                'monthly' => $this->group_stats( $stats, 'month' ),
                'yearly'  => $this->group_stats( $stats, 'year' ),
            ];
            wp_send_json_success( $data );
        }

        private function group_stats( $stats, $period ) {
            $grouped = [];
            foreach ( $stats as $timestamp ) {
                switch ( $period ) {
                    case 'day':
                        $key = gmdate( 'Y-m-d', $timestamp );
                        break;
                    case 'week':
                        $key = gmdate( 'o-W', $timestamp );
                        break;
                    case 'month':
                        $key = gmdate( 'Y-m', $timestamp );
                        break;
                    default:
                        $key = gmdate( 'Y', $timestamp );
                        break;
                }
                if ( ! isset( $grouped[ $key ] ) ) {
                    $grouped[ $key ] = 0;
                }
                $grouped[ $key ]++;
            }
            return $grouped;
        }
    }

    function noasoft_qr_plugin_init() {
        NoaSoft_QR_Code_Plugin::get_instance();
    }
    add_action( 'plugins_loaded', 'noasoft_qr_plugin_init' );
}

if ( ! class_exists( 'NoaSoft_QR_Widget' ) ) {
    class NoaSoft_QR_Widget extends WP_Widget {
        public function __construct() {
            parent::__construct(
                'noasoft_qr_widget',
                __( 'NoaSoft QR Generator', 'noasoft-qr-code' ),
                [ 'description' => __( 'Displays the NoaSoft QR generator form.', 'noasoft-qr-code' ) ]
            );
        }

        public function widget( $args, $instance ) {
            echo $args['before_widget'];
            if ( ! empty( $instance['title'] ) ) {
                echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
            }
            echo do_shortcode( '[noasoft_qr_form]' );
            echo $args['after_widget'];
        }

        public function form( $instance ) {
            $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'QR Code Generator', 'noasoft-qr-code' );
            ?>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'noasoft-qr-code' ); ?></label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
            </p>
            <?php
        }

        public function update( $new_instance, $old_instance ) {
            $instance          = [];
            $instance['title'] = sanitize_text_field( $new_instance['title'] );
            return $instance;
        }
    }
}
