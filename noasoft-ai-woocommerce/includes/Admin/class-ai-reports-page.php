<?php
namespace NoaSoft\AiWoo\Admin;

use NoaSoft\AiWoo\Helpers\Reports_Helper;
use NoaSoft\AiWoo\Helpers\PDF_Exporter;
use NoaSoft\AiWoo\Helpers\AI_Client_Factory;
use NoaSoft\AiWoo\Helpers\Options;

/**
 * AI reports admin page.
 */
class AI_Reports_Page {
    /**
     * Reports helper.
     *
     * @var Reports_Helper
     */
    protected $helper;

    /**
     * PDF exporter.
     *
     * @var PDF_Exporter
     */
    protected $pdf_exporter;

    /**
     * Module enabled flag.
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Allowed report ranges.
     *
     * @var array
     */
    protected $range_options = array( 7, 30, 60, 90 );

    /**
     * Constructor.
     */
    public function __construct() {
        $this->helper       = new Reports_Helper();
        $this->pdf_exporter = new PDF_Exporter();
        $this->enabled      = Options::is_module_enabled( 'admin_reports' );

        add_action( 'admin_menu', array( $this, 'register_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_noasoft_ai_generate_report', array( $this, 'ajax_generate_report' ) );
        add_action( 'wp_ajax_noasoft_ai_get_report', array( $this, 'ajax_get_report' ) );
        add_action( 'admin_post_noasoft_ai_export_report', array( $this, 'handle_pdf_export' ) );
    }

    /**
     * Register submenu page.
     */
    public function register_page() {
        add_submenu_page(
            'noasoft-ai-woo',
            __( 'AI Raporları', 'noasoft-ai-woocommerce' ),
            __( 'AI Raporları', 'noasoft-ai-woocommerce' ),
            'manage_woocommerce',
            'noasoft-ai-woo-reports',
            array( $this, 'render' )
        );
    }

    /**
     * Enqueue scripts for reports screen.
     *
     * @param string $hook Hook suffix.
     * @return void
     */
    public function enqueue_assets( $hook ) {
        if ( 'noasoft-ai-woo_page_noasoft-ai-woo-reports' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '4.4.0',
            true
        );

        wp_enqueue_script(
            'noasoft-ai-admin-reports',
            NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/admin-reports.js',
            array( 'jquery', 'chartjs' ),
            NOASOFT_AI_WOO_VERSION,
            true
        );

        wp_localize_script(
            'noasoft-ai-admin-reports',
            'NoaSoftAiReports',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'noasoft_ai_admin' ),
                'enabled'  => $this->enabled,
                'strings'  => array(
                    'creating'    => __( 'Rapor oluşturuluyor...', 'noasoft-ai-woocommerce' ),
                    'created'     => __( 'AI raporu hazır.', 'noasoft-ai-woocommerce' ),
                    'error'       => __( 'Rapor oluşturulamadı.', 'noasoft-ai-woocommerce' ),
                    'fetch_error' => __( 'Rapor yüklenemedi.', 'noasoft-ai-woocommerce' ),
                    'view'        => __( 'Görüntüle', 'noasoft-ai-woocommerce' ),
                    'pdf'         => __( 'PDF İndir', 'noasoft-ai-woocommerce' ),
                ),
            )
        );
    }

    /**
     * Render page.
     */
    public function render() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Bu sayfayı görüntüleme yetkiniz yok.', 'noasoft-ai-woocommerce' ) );
        }

        $reports      = $this->helper->get_reports( 25 );
        $active_report = ! empty( $reports ) ? $this->helper->get_report( $reports[0]['id'] ) : null;
        $active_html  = $this->render_report_template( $active_report );
        ?>
        <div class="wrap noasoft-ai-reports">
            <h1><?php esc_html_e( 'AI Raporları', 'noasoft-ai-woocommerce' ); ?></h1>
            <?php if ( ! $this->enabled ) : ?>
                <div class="notice notice-warning"><p><?php esc_html_e( 'AI Admin Raporları modülü pasif durumda. Ayarlar > Modüller sekmesinden aktifleştirebilirsiniz.', 'noasoft-ai-woocommerce' ); ?></p></div>
            <?php endif; ?>
            <div class="noasoft-ai-reports-toolbar">
                <div class="range-picker">
                    <label for="noasoft-report-range"><?php esc_html_e( 'Tarih Aralığı', 'noasoft-ai-woocommerce' ); ?></label>
                    <select id="noasoft-report-range" class="noasoft-report-range">
                        <?php foreach ( $this->range_options as $range ) : ?>
                            <option value="<?php echo esc_attr( $range ); ?>" <?php selected( $range, 30 ); ?>><?php echo esc_html( sprintf( __( 'Son %s gün', 'noasoft-ai-woocommerce' ), $range ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="button button-primary noasoft-generate-report" type="button" <?php disabled( ! $this->enabled ); ?>><?php esc_html_e( 'Yeni Rapor Oluştur', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="description"><?php esc_html_e( 'Seçtiğiniz aralık için WooCommerce ve UX metrikleri analiz edilir.', 'noasoft-ai-woocommerce' ); ?></span>
                <button type="button" class="button" data-modal-target="#noasoft-modal-report-preview"><?php esc_html_e( 'UI Önizleme', 'noasoft-ai-woocommerce' ); ?></button>
            </div>
            <div class="noasoft-ai-reports-grid">
                <div class="noasoft-ai-reports-list">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Başlık', 'noasoft-ai-woocommerce' ); ?></th>
                                <th><?php esc_html_e( 'Tarih', 'noasoft-ai-woocommerce' ); ?></th>
                                <th><?php esc_html_e( 'İşlemler', 'noasoft-ai-woocommerce' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $reports ) ) : ?>
                                <tr>
                                    <td colspan="3"><?php esc_html_e( 'Henüz kayıtlı bir rapor bulunmuyor.', 'noasoft-ai-woocommerce' ); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $reports as $report ) : ?>
                                    <tr class="noasoft-report-row" data-report-id="<?php echo esc_attr( $report['id'] ); ?>">
                                        <td><?php echo esc_html( $report['title'] ); ?></td>
                                        <td><?php echo esc_html( $this->format_date( $report['created_at'] ) ); ?></td>
                                        <td>
                                            <button class="button button-small view-report" type="button" data-report-id="<?php echo esc_attr( $report['id'] ); ?>"><?php esc_html_e( 'Görüntüle', 'noasoft-ai-woocommerce' ); ?></button>
                                            <a class="button button-small" href="<?php echo esc_url( $this->get_pdf_link( $report['id'] ) ); ?>"><?php esc_html_e( 'PDF İndir', 'noasoft-ai-woocommerce' ); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="noasoft-ai-report-panel">
                    <?php echo $active_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>
        <div class="noasoft-modal" id="noasoft-modal-report-preview" aria-hidden="true">
            <div class="noasoft-modal-dialog">
                <button type="button" class="noasoft-modal-close" aria-label="<?php esc_attr_e( 'Kapat', 'noasoft-ai-woocommerce' ); ?>">&times;</button>
                <h3><?php esc_html_e( 'Rapor Önizleme', 'noasoft-ai-woocommerce' ); ?></h3>
                <div class="noasoft-modal-body">
                    <p><?php esc_html_e( 'AI rapor kartı yüklenirken skeleton ve grafik önizlemesi burada gösterilir.', 'noasoft-ai-woocommerce' ); ?></p>
                </div>
                <button type="button" class="button" data-modal-close><?php esc_html_e( 'Kapat', 'noasoft-ai-woocommerce' ); ?></button>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: generate report.
     */
    public function ajax_generate_report() {
        $this->ensure_permissions();

        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Modül pasif durumda.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $days     = $this->sanitize_range( isset( $_POST['range'] ) ? $_POST['range'] : 30 );
        $metrics  = $this->helper->collect_metrics( $days );
        $prompt   = Options::get_prompt( 'admin_report', __( 'Aşağıdaki verileri analiz ederek kısa bir özet ve uygulanabilir aksiyon listesi oluştur.', 'noasoft-ai-woocommerce' ) );
        $provider = AI_Client_Factory::make();

        if ( ! $provider ) {
            wp_send_json_error( array( 'message' => __( 'Aktif AI sağlayıcısı ayarlanmadı.', 'noasoft-ai-woocommerce' ) ), 200 );
        }

        $payload  = $prompt . "\n\nMETRICS:\n" . wp_json_encode( $metrics );
        $response = $provider->chat( $payload, array( 'metrics' => $metrics ) );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ), 200 );
        }

        $parsed = $this->parse_ai_response( $response );

        $title = sprintf( __( 'AI Ticaret Raporu - %s', 'noasoft-ai-woocommerce' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
        $report = $this->helper->save_report(
            array(
                'title'              => $title,
                'raw_data'           => $metrics,
                'ai_summary'         => $parsed['summary'],
                'ai_recommendations' => implode( "\n", $parsed['recommendations'] ),
            )
        );

        if ( ! $report ) {
            wp_send_json_error( array( 'message' => __( 'Rapor kaydedilemedi.', 'noasoft-ai-woocommerce' ) ), 500 );
        }

        $html = $this->render_report_template( $report );

        wp_send_json_success(
            array(
                'report'  => $this->format_report_row( $report ),
                'html'    => $html,
                'message' => __( 'Rapor oluşturuldu.', 'noasoft-ai-woocommerce' ),
            )
        );
    }

    /**
     * AJAX: fetch report details.
     */
    public function ajax_get_report() {
        $this->ensure_permissions();

        $report_id = isset( $_POST['report_id'] ) ? absint( $_POST['report_id'] ) : 0;
        if ( ! $report_id ) {
            wp_send_json_error( array( 'message' => __( 'Geçersiz rapor.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $report = $this->helper->get_report( $report_id );
        if ( ! $report ) {
            wp_send_json_error( array( 'message' => __( 'Rapor bulunamadı.', 'noasoft-ai-woocommerce' ) ), 404 );
        }

        wp_send_json_success(
            array(
                'report' => $this->format_report_row( $report ),
                'html'   => $this->render_report_template( $report ),
            )
        );
    }

    /**
     * Handle PDF export request.
     */
    public function handle_pdf_export() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Bu işlem için yetkiniz yok.', 'noasoft-ai-woocommerce' ) );
        }

        $report_id = isset( $_GET['report_id'] ) ? absint( $_GET['report_id'] ) : 0;
        check_admin_referer( 'noasoft_ai_report_pdf_' . $report_id );

        $report = $this->helper->get_report( $report_id );
        if ( ! $report ) {
            wp_die( esc_html__( 'Rapor bulunamadı.', 'noasoft-ai-woocommerce' ) );
        }

        $metrics = isset( $report['metrics'] ) ? $report['metrics'] : array();
        $path    = $this->pdf_exporter->export(
            array(
                'title'           => $report['title'],
                'created_at'      => $this->format_date( $report['created_at'] ),
                'summary'         => $report['ai_summary'],
                'recommendations' => $this->split_recommendations( $report['ai_recommendations'] ),
                'metrics'         => $metrics,
            )
        );

        if ( ! $path || ! file_exists( $path ) ) {
            wp_die( esc_html__( 'PDF oluşturulamadı.', 'noasoft-ai-woocommerce' ) );
        }

        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' );
        header( 'Content-Length: ' . filesize( $path ) );
        readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
        unlink( $path );
        exit;
    }

    /**
     * Ensure AJAX permissions.
     *
     * @return void
     */
    protected function ensure_permissions() {
        check_ajax_referer( 'noasoft_ai_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Yetkiniz yok.', 'noasoft-ai-woocommerce' ) ), 403 );
        }
    }

    /**
     * Render report template.
     *
     * @param array|null $report Report data.
     * @return string
     */
    protected function render_report_template( $report ) {
        $template = NOASOFT_AI_WOO_PLUGIN_DIR . 'templates/admin/ai-report-view.php';
        if ( ! file_exists( $template ) ) {
            return '';
        }

        $chart_data = $this->prepare_chart_payload( $report );
        $report_data = $report;
        if ( $report_data ) {
            $report_data['created_at'] = $this->format_date( $report_data['created_at'] );
        }

        ob_start();
        include $template;
        return ob_get_clean();
    }

    /**
     * Prepare chart payload for JS.
     *
     * @param array|null $report Report data.
     * @return array
     */
    protected function prepare_chart_payload( $report ) {
        $metrics    = isset( $report['metrics'] ) ? $report['metrics'] : array();
        $events     = isset( $metrics['events'] ) ? $metrics['events'] : array();
        $orders     = isset( $metrics['orders'] ) ? $metrics['orders'] : array();
        $statuses   = isset( $orders['statuses'] ) ? $orders['statuses'] : array();
        $status_lbl = array();
        $status_val = array();
        if ( $statuses ) {
            foreach ( $statuses as $status ) {
                $status_lbl[] = isset( $status['label'] ) ? $status['label'] : '';
                $status_val[] = isset( $status['count'] ) ? (int) $status['count'] : 0;
            }
        } else {
            $status_lbl[] = __( 'Veri yok', 'noasoft-ai-woocommerce' );
            $status_val[] = 0;
        }

        $engagement_labels = array(
            __( 'Görüntüleme', 'noasoft-ai-woocommerce' ),
            __( 'Sepete Ekleme', 'noasoft-ai-woocommerce' ),
            __( 'Favori', 'noasoft-ai-woocommerce' ),
            __( 'Yorum', 'noasoft-ai-woocommerce' ),
        );
        $engagement_values = array(
            isset( $events['view_product']['total'] ) ? (int) $events['view_product']['total'] : 0,
            isset( $events['add_to_cart']['total'] ) ? (int) $events['add_to_cart']['total'] : 0,
            isset( $events['favorite']['total'] ) ? (int) $events['favorite']['total'] : 0,
            isset( $events['review']['total'] ) ? (int) $events['review']['total'] : 0,
        );

        return array(
            'engagement' => array(
                'labels' => $engagement_labels,
                'data'   => $engagement_values,
            ),
            'orders'     => array(
                'labels' => $status_lbl,
                'data'   => $status_val,
            ),
        );
    }

    /**
     * Parse AI response.
     *
     * @param array $response AI payload.
     * @return array
     */
    protected function parse_ai_response( $response ) {
        $summary = '';
        $recommendations = array();
        $raw = '';

        if ( isset( $response['reply'] ) && is_string( $response['reply'] ) ) {
            $raw = $response['reply'];
        }

        if ( isset( $response['summary'] ) ) {
            $summary = is_string( $response['summary'] ) ? $response['summary'] : wp_json_encode( $response['summary'] );
        }

        if ( isset( $response['recommendations'] ) ) {
            $recommendations = (array) $response['recommendations'];
        }

        if ( $raw ) {
            $decoded = json_decode( $raw, true );
            if ( is_array( $decoded ) ) {
                if ( isset( $decoded['summary'] ) ) {
                    $summary = is_string( $decoded['summary'] ) ? $decoded['summary'] : wp_json_encode( $decoded['summary'] );
                }
                if ( isset( $decoded['recommendations'] ) ) {
                    $recommendations = (array) $decoded['recommendations'];
                }
            } elseif ( ! $summary ) {
                $summary = $raw;
            }
        }

        if ( empty( $recommendations ) && $summary ) {
            $recommendations = array( $summary );
        }

        return array(
            'summary'        => wp_strip_all_tags( $summary ),
            'recommendations' => array_map( 'wp_strip_all_tags', $recommendations ),
        );
    }

    /**
     * Create PDF download nonce link.
     *
     * @param int $report_id Report ID.
     * @return string
     */
    protected function get_pdf_link( $report_id ) {
        $url = add_query_arg(
            array(
                'action'    => 'noasoft_ai_export_report',
                'report_id' => (int) $report_id,
            ),
            admin_url( 'admin-post.php' )
        );

        return wp_nonce_url( $url, 'noasoft_ai_report_pdf_' . $report_id );
    }

    /**
     * Format row for JS.
     *
     * @param array $report Report row.
     * @return array
     */
    protected function format_report_row( $report ) {
        return array(
            'id'         => (int) $report['id'],
            'title'      => $report['title'],
            'created_at' => $this->format_date( $report['created_at'] ),
            'pdf'        => $this->get_pdf_link( $report['id'] ),
        );
    }

    /**
     * Human readable datetime.
     *
     * @param string $datetime Datetime string.
     * @return string
     */
    protected function format_date( $datetime ) {
        $timestamp = strtotime( $datetime );
        if ( ! $timestamp ) {
            return $datetime;
        }

        return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
    }

    /**
     * Sanitize range input.
     *
     * @param mixed $value Submitted value.
     * @return int
     */
    protected function sanitize_range( $value ) {
        $value = absint( $value );
        if ( in_array( $value, $this->range_options, true ) ) {
            return $value;
        }

        return 30;
    }

    /**
     * Convert newline string to array.
     *
     * @param string $text Text.
     * @return array
     */
    protected function split_recommendations( $text ) {
        $lines = preg_split( '/\r?\n/', (string) $text );
        $lines = array_map( 'trim', $lines );
        $lines = array_filter( $lines );

        return array_values( $lines );
    }
}
