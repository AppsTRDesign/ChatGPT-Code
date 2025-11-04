<?php
$instance = NoaSoft_QR_Code_Plugin::get_instance();
$shortcodes = [
    [
        'code'        => '[noasoft_qr_form]',
        'description' => $instance->translate( 'shortcode_form_desc' ),
    ],
    [
        'code'        => '[noasoft_qr_api_guide]',
        'description' => $instance->translate( 'shortcode_api_desc' ),
    ],
];
?>
<div class="wrap noasoft-qr-admin" style="max-width:1200px;margin:0 auto;">
    <h1 class="mb-4"><?php echo esc_html( $instance->translate( 'shortcode_title' ) ); ?></h1>
    <div class="bg-white rounded-4 shadow-sm p-4">
        <p class="text-muted mb-4"><?php echo esc_html( $instance->translate( 'shortcode_intro' ) ); ?></p>
        <div class="row g-4">
            <?php foreach ( $shortcodes as $shortcode ) : ?>
                <div class="col-md-6">
                    <div class="border rounded-4 p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <p class="fw-semibold mb-2 text-primary"><?php echo esc_html( $shortcode['description'] ); ?></p>
                            <pre class="bg-light rounded-3 p-3 mb-3 text-dark small"><?php echo esc_html( $shortcode['code'] ); ?></pre>
                        </div>
                        <button type="button" class="btn btn-outline-primary w-100" data-copy="<?php echo esc_attr( $shortcode['code'] ); ?>">
                            <?php echo esc_html( $instance->translate( 'btn_copy' ) ); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <hr class="my-4">
        <h2 class="h5 mb-3"><?php echo esc_html( $instance->translate( 'shortcode_options_title' ) ); ?></h2>
        <div class="accordion" id="noasoft-shortcode-accordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <?php echo esc_html( $instance->translate( 'shortcode_form_title' ) ); ?>
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#noasoft-shortcode-accordion">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><strong>type</strong> - <?php echo esc_html( $instance->translate( 'shortcode_form_type' ) ); ?></li>
                            <li><strong>width</strong> - <?php echo esc_html( $instance->translate( 'shortcode_form_width' ) ); ?></li>
                            <li><strong>height</strong> - <?php echo esc_html( $instance->translate( 'shortcode_form_height' ) ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        <?php echo esc_html( $instance->translate( 'shortcode_api_title' ) ); ?>
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#noasoft-shortcode-accordion">
                    <div class="accordion-body">
                        <p class="mb-0"><?php echo esc_html( $instance->translate( 'shortcode_api_details' ) ); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
