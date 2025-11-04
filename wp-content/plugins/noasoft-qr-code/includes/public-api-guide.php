<?php
$instance = NoaSoft_QR_Code_Plugin::get_instance();
$faqs = [
    [
        'title'   => $instance->translate( 'faq_auth_title' ),
        'content' => $instance->translate( 'faq_auth_body' ),
    ],
    [
        'title'   => $instance->translate( 'faq_endpoint_title' ),
        'content' => $instance->translate( 'faq_endpoint_body' ),
    ],
    [
        'title'   => $instance->translate( 'faq_types_title' ),
        'content' => $instance->translate( 'faq_types_body' ),
    ],
    [
        'title'   => $instance->translate( 'faq_limits_title' ),
        'content' => $instance->translate( 'faq_limits_body' ),
    ],
];
?>
<div class="noasoft-api-guide">
    <div class="accordion" id="noasoft-api-accordion">
        <?php foreach ( $faqs as $index => $faq ) : ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="api-heading-<?php echo esc_attr( $index ); ?>">
                    <button class="accordion-button <?php echo 0 === $index ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#api-collapse-<?php echo esc_attr( $index ); ?>" aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="api-collapse-<?php echo esc_attr( $index ); ?>">
                        <?php echo esc_html( $faq['title'] ); ?>
                    </button>
                </h2>
                <div id="api-collapse-<?php echo esc_attr( $index ); ?>" class="accordion-collapse collapse <?php echo 0 === $index ? 'show' : ''; ?>" aria-labelledby="api-heading-<?php echo esc_attr( $index ); ?>" data-bs-parent="#noasoft-api-accordion">
                    <div class="accordion-body">
                        <?php echo wp_kses_post( wpautop( $faq['content'] ) ); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
