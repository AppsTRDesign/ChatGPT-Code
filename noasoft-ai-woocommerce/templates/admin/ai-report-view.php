<?php
/**
 * AI report view template.
 *
 * @var array $report_data
 * @var array $chart_data
 */

if ( empty( $report_data ) ) :
    ?>
    <div class="noasoft-ai-report-placeholder">
        <p><?php esc_html_e( 'Bir rapor seçtiğinizde detaylar burada görünecek.', 'noasoft-ai-woocommerce' ); ?></p>
    </div>
    <?php
    return;
endif;

$title            = isset( $report_data['title'] ) ? $report_data['title'] : '';
$metrics          = isset( $report_data['metrics'] ) ? $report_data['metrics'] : array();
$kpis             = isset( $metrics['kpis'] ) ? $metrics['kpis'] : array();
$events           = isset( $metrics['events'] ) ? $metrics['events'] : array();
$range            = isset( $metrics['range'] ) ? $metrics['range'] : array();
$bestsellers      = isset( $metrics['bestsellers'] ) ? $metrics['bestsellers'] : array();
$highest_rated    = isset( $metrics['highest_rated'] ) ? $metrics['highest_rated'] : array();
$summary_html     = wpautop( esc_html( $report_data['ai_summary'] ) );
$recommendations_source = isset( $report_data['ai_recommendations'] ) ? $report_data['ai_recommendations'] : '';
$recommendations  = preg_split( '/\r?\n/', (string) $recommendations_source );
$recommendations  = array_filter( array_map( 'trim', (array) $recommendations ) );
$chart_attr_json  = wp_json_encode( $chart_data );
$chart_attr       = esc_attr( $chart_attr_json ? $chart_attr_json : '{}' );
$range_text       = '';
if ( ! empty( $range['start'] ) && ! empty( $range['end'] ) ) {
    $start_ts  = strtotime( $range['start'] );
    $end_ts    = strtotime( $range['end'] );
    $start_fmt = $start_ts ? wp_date( get_option( 'date_format' ), $start_ts ) : $range['start'];
    $end_fmt   = $end_ts ? wp_date( get_option( 'date_format' ), $end_ts ) : $range['end'];
    $range_text = sprintf( '%1$s - %2$s', esc_html( $start_fmt ), esc_html( $end_fmt ) );
}
$pdf_link = isset( $report_data['id'] ) && method_exists( $this, 'get_pdf_link' ) ? $this->get_pdf_link( $report_data['id'] ) : '';
?>
<div class="noasoft-ai-report-view" data-chart="<?php echo $chart_attr; ?>">
    <header class="noasoft-ai-report-header">
        <div>
            <span class="noasoft-gradient-pill"><?php esc_html_e( 'AI Raporu', 'noasoft-ai-woocommerce' ); ?></span>
            <h2><?php echo esc_html( $title ); ?></h2>
            <p class="meta"><?php echo esc_html( $report_data['created_at'] ); ?><?php if ( $range_text ) : ?> · <?php echo esc_html( $range_text ); ?><?php endif; ?></p>
        </div>
    </header>
    <?php if ( ! empty( $kpis ) ) : ?>
        <div class="noasoft-ai-kpis">
            <?php
            $kpi_labels = array(
                'conversion_rate' => __( 'Dönüşüm Oranı', 'noasoft-ai-woocommerce' ),
                'cart_rate'       => __( 'Sepete Ekleme Oranı', 'noasoft-ai-woocommerce' ),
                'repeat_rate'     => __( 'Tekrar Müşteri Oranı', 'noasoft-ai-woocommerce' ),
                'average_order'   => __( 'Ort. Sipariş Tutarı', 'noasoft-ai-woocommerce' ),
            );
            foreach ( $kpi_labels as $key => $label ) :
                if ( ! isset( $kpis[ $key ] ) ) {
                    continue;
                }
                ?>
                <div class="kpi-card">
                    <span class="label"><?php echo esc_html( $label ); ?></span>
                    <strong><?php echo esc_html( $kpis[ $key ] ); ?><?php echo in_array( $key, array( 'conversion_rate', 'cart_rate', 'repeat_rate' ), true ) ? '%' : ''; ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="noasoft-ai-chart-row">
        <div class="chart-card">
            <h3><?php esc_html_e( 'Etkileşim Özeti', 'noasoft-ai-woocommerce' ); ?></h3>
            <canvas id="noasoft-report-engagement"></canvas>
        </div>
        <div class="chart-card">
            <h3><?php esc_html_e( 'Sipariş Durumları', 'noasoft-ai-woocommerce' ); ?></h3>
            <canvas id="noasoft-report-orders"></canvas>
        </div>
    </div>

    <div class="noasoft-ai-columns">
        <div class="column">
            <h3><?php esc_html_e( 'En Çok Görüntülenen / Sepete Eklenen', 'noasoft-ai-woocommerce' ); ?></h3>
            <ul class="noasoft-ai-product-list">
                <?php
                $viewed = isset( $events['view_product']['top'] ) ? $events['view_product']['top'] : array();
                if ( empty( $viewed ) ) :
                    ?>
                    <li><?php esc_html_e( 'Veri bulunamadı.', 'noasoft-ai-woocommerce' ); ?></li>
                <?php else :
                    foreach ( $viewed as $item ) :
                        ?>
                        <li>
                            <span><?php echo esc_html( isset( $item['product']['name'] ) ? $item['product']['name'] : '' ); ?></span>
                            <small><?php printf( esc_html__( '%d görüntüleme', 'noasoft-ai-woocommerce' ), absint( isset( $item['count'] ) ? $item['count'] : 0 ) ); ?></small>
                        </li>
                    <?php endforeach;
                endif;
                ?>
            </ul>
            <h4><?php esc_html_e( 'Sepete Eklenenler', 'noasoft-ai-woocommerce' ); ?></h4>
            <ul class="noasoft-ai-product-list">
                <?php
                $carted = isset( $events['add_to_cart']['top'] ) ? $events['add_to_cart']['top'] : array();
                if ( empty( $carted ) ) :
                    ?>
                    <li><?php esc_html_e( 'Veri bulunamadı.', 'noasoft-ai-woocommerce' ); ?></li>
                <?php else :
                    foreach ( $carted as $item ) :
                        ?>
                        <li>
                            <span><?php echo esc_html( isset( $item['product']['name'] ) ? $item['product']['name'] : '' ); ?></span>
                            <small><?php printf( esc_html__( '%d sepet ekleme', 'noasoft-ai-woocommerce' ), absint( isset( $item['count'] ) ? $item['count'] : 0 ) ); ?></small>
                        </li>
                    <?php endforeach;
                endif;
                ?>
            </ul>
            <h3><?php esc_html_e( 'En Çok Satanlar', 'noasoft-ai-woocommerce' ); ?></h3>
            <ul class="noasoft-ai-product-list">
                <?php if ( empty( $bestsellers ) ) : ?>
                    <li><?php esc_html_e( 'Veri bulunamadı.', 'noasoft-ai-woocommerce' ); ?></li>
                <?php else : ?>
                    <?php foreach ( $bestsellers as $product ) : ?>
                        <li>
                            <span><?php echo esc_html( isset( $product['name'] ) ? $product['name'] : '' ); ?></span>
                            <small><?php printf( esc_html__( '%d satış', 'noasoft-ai-woocommerce' ), absint( isset( $product['sales'] ) ? $product['sales'] : 0 ) ); ?></small>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="column">
            <h3><?php esc_html_e( 'En Yüksek Puanlılar', 'noasoft-ai-woocommerce' ); ?></h3>
            <ul class="noasoft-ai-product-list">
                <?php if ( empty( $highest_rated ) ) : ?>
                    <li><?php esc_html_e( 'Veri bulunamadı.', 'noasoft-ai-woocommerce' ); ?></li>
                <?php else : ?>
                    <?php foreach ( $highest_rated as $product ) : ?>
                        <li>
                            <span><?php echo esc_html( isset( $product['name'] ) ? $product['name'] : '' ); ?></span>
                            <small><?php printf( esc_html__( 'Ort. %s puan', 'noasoft-ai-woocommerce' ), esc_html( isset( $product['rating'] ) ? $product['rating'] : '0' ) ); ?></small>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
            <h3><?php esc_html_e( 'AI Önerileri', 'noasoft-ai-woocommerce' ); ?></h3>
            <?php if ( ! empty( $recommendations ) ) : ?>
                <ul class="noasoft-ai-recommendations">
                    <?php foreach ( $recommendations as $line ) : ?>
                        <li><?php echo esc_html( $line ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e( 'AI önerileri mevcut değil.', 'noasoft-ai-woocommerce' ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="noasoft-ai-summary">
        <h3><?php esc_html_e( 'AI Özeti', 'noasoft-ai-woocommerce' ); ?></h3>
        <?php echo $summary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
    <?php if ( $pdf_link ) : ?>
        <div class="noasoft-ai-report-footer">
            <p><?php esc_html_e( 'Raporu paylaşmak veya arşivlemek için PDF çıktısı alabilirsiniz.', 'noasoft-ai-woocommerce' ); ?></p>
            <a class="button button-primary" href="<?php echo esc_url( $pdf_link ); ?>">
                <?php esc_html_e( 'PDF İndir', 'noasoft-ai-woocommerce' ); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
