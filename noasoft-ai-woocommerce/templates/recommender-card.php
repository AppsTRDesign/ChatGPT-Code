<?php
/**
 * Recommender card template.
 *
 * @var array  $recommender_data Data payload.
 * @var string $recommender_layout Layout slug.
 */
?>
<div class="noasoft-recommender-card layout-<?php echo esc_attr( $recommender_layout ); ?>" data-product-id="<?php echo isset( $recommender_data['product']['id'] ) ? esc_attr( $recommender_data['product']['id'] ) : 0; ?>">
    <?php if ( empty( $recommender_data ) ) : ?>
        <p class="noasoft-recommender-empty"><?php esc_html_e( 'Öneri verisi bulunamadı.', 'noasoft-ai-woocommerce' ); ?></p>
    <?php else : ?>
        <div class="noasoft-recommender-header">
            <div>
                <h3><?php echo esc_html( $recommender_data['ai_copy']['headline'] ); ?></h3>
                <span class="noasoft-recommender-meta" data-meta-label="<?php esc_attr_e( 'son etkileşime göre', 'noasoft-ai-woocommerce' ); ?>">
                    <?php echo esc_html( sprintf( __( '%d son etkileşime göre', 'noasoft-ai-woocommerce' ), count( $recommender_data['events'] ) ) ); ?>
                </span>
            </div>
            <button type="button" class="noasoft-recommender-refresh">
                <?php esc_html_e( 'Önerileri Güncelle', 'noasoft-ai-woocommerce' ); ?>
            </button>
        </div>
        <div class="noasoft-recommender-product">
            <div class="noasoft-recommender-media">
                <img src="<?php echo esc_url( $recommender_data['product']['image'] ); ?>" alt="<?php echo esc_attr( $recommender_data['product']['title'] ); ?>" />
            </div>
            <div>
                <a href="<?php echo esc_url( $recommender_data['product']['permalink'] ); ?>" class="noasoft-recommender-title"><?php echo esc_html( $recommender_data['product']['title'] ); ?></a>
                <div class="noasoft-recommender-price"><?php echo wp_kses_post( $recommender_data['product']['price_html'] ); ?></div>
            </div>
        </div>
        <div class="noasoft-recommender-columns">
            <div>
                <h4><?php esc_html_e( 'Artıları', 'noasoft-ai-woocommerce' ); ?></h4>
                <ul class="noasoft-recommender-pros">
                    <?php foreach ( $recommender_data['ai_copy']['pros'] as $pro ) : ?>
                        <li><?php echo esc_html( $pro ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4><?php esc_html_e( 'Eksileri', 'noasoft-ai-woocommerce' ); ?></h4>
                <ul class="noasoft-recommender-cons">
                    <?php foreach ( $recommender_data['ai_copy']['cons'] as $con ) : ?>
                        <li><?php echo esc_html( $con ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <p class="noasoft-recommender-why"><?php echo esc_html( $recommender_data['ai_copy']['why'] ); ?></p>
        <div class="noasoft-recommender-cta-row">
            <a class="button button-primary" href="<?php echo esc_url( $recommender_data['product']['permalink'] ); ?>" target="_blank" rel="noreferrer">
                <?php esc_html_e( 'Ürüne Git', 'noasoft-ai-woocommerce' ); ?>
            </a>
            <button type="button" class="button button-outline noasoft-recommender-add" data-product="<?php echo esc_attr( $recommender_data['product']['id'] ); ?>">
                <?php esc_html_e( 'Sepete Ekle', 'noasoft-ai-woocommerce' ); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
