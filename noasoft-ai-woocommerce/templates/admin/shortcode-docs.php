<?php
/**
 * Shortcode documentation template.
 *
 * @var array $shortcode_data View data from Shortcode_Docs_Page::get_view_data().
 */
?>
<div class="noasoft-shortcode-docs">
    <?php if ( empty( $shortcode_data['shortcodes'] ) ) : ?>
        <p><?php esc_html_e( 'Henüz shortcode tanımlanmadı.', 'noasoft-ai-woocommerce' ); ?></p>
    <?php else : ?>
        <div class="noasoft-shortcode-grid">
            <?php foreach ( $shortcode_data['shortcodes'] as $doc ) : ?>
                <section class="noasoft-shortcode-card">
                    <div class="card-head">
                        <h3><?php echo esc_html( $doc['title'] ); ?></h3>
                        <code><?php echo esc_html( $doc['tag'] ); ?></code>
                    </div>
                    <p class="description"><?php echo esc_html( $doc['description'] ); ?></p>
                    <?php if ( ! empty( $doc['params'] ) ) : ?>
                        <ul class="param-list">
                            <?php foreach ( $doc['params'] as $param ) : ?>
                                <li>
                                    <strong><?php echo esc_html( $param['name'] ); ?>:</strong>
                                    <span><?php echo esc_html( $param['desc'] ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="shortcode-copy">
                        <input type="text" readonly value="<?php echo esc_attr( $doc['example'] ); ?>" />
                        <button type="button" class="button button-secondary noasoft-copy" data-copy="<?php echo esc_attr( $doc['example'] ); ?>"><?php esc_html_e( 'Kopyala', 'noasoft-ai-woocommerce' ); ?></button>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $shortcode_data['widgets'] ) ) : ?>
        <div class="noasoft-widget-docs">
            <h2><?php esc_html_e( 'Widget Rehberi', 'noasoft-ai-woocommerce' ); ?></h2>
            <div class="widget-list">
                <?php foreach ( $shortcode_data['widgets'] as $widget ) : ?>
                    <article>
                        <h3><?php echo esc_html( $widget['name'] ); ?></h3>
                        <p><?php echo esc_html( $widget['description'] ); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
