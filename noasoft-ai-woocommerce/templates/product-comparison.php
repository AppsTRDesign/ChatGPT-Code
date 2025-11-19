<?php
/**
 * Product comparison template.
 *
 * @var array $prefill
 * @var bool  $auto_run
 * @var array $strings
 */
$prefill_one = isset( $prefill['product_one'] ) ? $prefill['product_one'] : '';
$prefill_two = isset( $prefill['product_two'] ) ? $prefill['product_two'] : '';
$prefill_json = wp_json_encode(
    array(
        'product_one' => $prefill_one,
        'product_two' => $prefill_two,
    )
);
$prefill_json = $prefill_json ? $prefill_json : '{}';
?>
<div class="noasoft-ai-compare" data-prefill='<?php echo esc_attr( $prefill_json ); ?>' data-auto-run="<?php echo $auto_run ? '1' : '0'; ?>">
    <div class="noasoft-ai-compare-header">
        <div>
            <span class="noasoft-gradient-pill">AI</span>
            <h3><?php echo esc_html( $strings['title'] ); ?></h3>
            <p><?php echo esc_html( $strings['instructions'] ); ?></p>
        </div>
        <div class="noasoft-ai-compare-tools" aria-live="polite">
            <button type="button" class="button button-secondary" data-action="copy-share" disabled>
                <?php echo esc_html( $strings['share'] ); ?>
            </button>
            <button type="button" class="button button-primary" data-action="export-pdf" disabled>
                <?php echo esc_html( $strings['pdf'] ); ?>
            </button>
        </div>
    </div>
    <form class="noasoft-ai-compare-form">
        <div class="noasoft-ai-compare-fields">
            <label>
                <span><?php esc_html_e( 'Ürün 1', 'noasoft-ai-woocommerce' ); ?></span>
                <input type="text" name="product_one" value="<?php echo esc_attr( $prefill_one ); ?>" placeholder="<?php esc_attr_e( 'ID / SKU / İsim', 'noasoft-ai-woocommerce' ); ?>" />
            </label>
            <label>
                <span><?php esc_html_e( 'Ürün 2', 'noasoft-ai-woocommerce' ); ?></span>
                <input type="text" name="product_two" value="<?php echo esc_attr( $prefill_two ); ?>" placeholder="<?php esc_attr_e( 'ID / SKU / İsim', 'noasoft-ai-woocommerce' ); ?>" />
            </label>
        </div>
        <div class="noasoft-ai-compare-actions">
            <button type="submit" class="button button-primary">
                <?php echo esc_html( $strings['submit'] ); ?>
            </button>
            <button type="button" class="button button-secondary" data-action="swap">
                <?php echo esc_html( $strings['swap'] ); ?>
            </button>
        </div>
    </form>
    <div class="noasoft-ai-compare-status" aria-live="assertive"></div>
    <div class="noasoft-ai-compare-results" data-empty-text="<?php echo esc_attr( $strings['empty'] ); ?>">
        <p class="noasoft-ai-compare-empty"><?php echo esc_html( $strings['empty'] ); ?></p>
    </div>
</div>
