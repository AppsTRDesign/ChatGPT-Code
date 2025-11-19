<?php if ( empty( $data['items'] ) ) : ?>
    <p><?php esc_html_e( 'Karşılaştırma için ürün bulunamadı.', 'noasoft-ai' ); ?></p>
<?php else : ?>
    <div class="noasoft-ai-compare">
        <?php foreach ( $data['items'] as $item ) : ?>
            <article>
                <h4><?php echo esc_html( $item['name'] ); ?></h4>
                <div class="price"><?php echo wp_kses_post( $item['price'] ); ?></div>
                <ul>
                    <?php foreach ( $item['highlights'] as $highlight ) : ?>
                        <li><?php echo esc_html( $highlight ); ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="button" href="<?php echo esc_url( $item['permalink'] ); ?>"><?php esc_html_e( 'Ürüne git', 'noasoft-ai' ); ?></a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ( ! empty( $data['analysis'] ) ) : ?>
        <div class="noasoft-ai-card">
            <h4><?php esc_html_e( 'AI Analizi', 'noasoft-ai' ); ?></h4>
            <p><?php echo esc_html( $data['analysis'] ); ?></p>
            <a class="button" href="<?php echo esc_url( $data['link'] ); ?>"><?php esc_html_e( 'Karşılaştırma linki oluştur', 'noasoft-ai' ); ?></a>
        </div>
    <?php endif; ?>
<?php endif; ?>
