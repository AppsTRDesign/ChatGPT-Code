<?php
$instance = NoaSoft_QR_Code_Plugin::get_instance();
$settings = get_option( NoaSoft_QR_Code_Plugin::OPTION_SETTINGS, [] );
$primary  = $settings['primary_color'] ?? '#2563eb';
$accent   = $settings['accent_color'] ?? '#0ea5e9';
?>
<div class="wrap noasoft-qr-admin" style="max-width:1200px;margin:0 auto;">
    <h1 class="mb-4"><?php echo esc_html( $instance->translate( 'create_title' ) ); ?></h1>
    <div class="bg-white rounded-4 shadow-sm p-4">
        <form id="noasoft-qr-create-form" class="noasoft-qr-form" data-primary="<?php echo esc_attr( $primary ); ?>" data-accent="<?php echo esc_attr( $accent ); ?>">
            <div class="row g-4">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_type' ) ); ?></label>
                    <select name="type" class="form-select form-select-lg" id="noasoft-type">
                        <option value="url"><?php echo esc_html( $instance->translate( 'type_url' ) ); ?></option>
                        <option value="text"><?php echo esc_html( $instance->translate( 'type_text' ) ); ?></option>
                        <option value="email"><?php echo esc_html( $instance->translate( 'type_email' ) ); ?></option>
                        <option value="phone"><?php echo esc_html( $instance->translate( 'type_phone' ) ); ?></option>
                        <option value="wifi"><?php echo esc_html( $instance->translate( 'type_wifi' ) ); ?></option>
                        <option value="event"><?php echo esc_html( $instance->translate( 'type_event' ) ); ?></option>
                        <option value="location"><?php echo esc_html( $instance->translate( 'type_location' ) ); ?></option>
                        <option value="sms"><?php echo esc_html( $instance->translate( 'type_sms' ) ); ?></option>
                        <option value="whatsapp"><?php echo esc_html( $instance->translate( 'type_whatsapp' ) ); ?></option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_ratio' ) ); ?></label>
                    <select name="aspect_ratio" class="form-select form-select-lg">
                        <option value="1:1">1:1</option>
                        <option value="4:5">4:5</option>
                        <option value="16:9">16:9</option>
                        <option value="9:16">9:16</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_width' ) ); ?></label>
                    <input type="number" name="width" value="400" class="form-control form-control-lg">
                </div>
                <div class="col-lg-2">
                    <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_height' ) ); ?></label>
                    <input type="number" name="height" value="400" class="form-control form-control-lg">
                </div>
            </div>

            <div class="row g-4 mt-1" id="noasoft-dynamic-fields"></div>

            <div class="row g-4 mt-1">
                <div class="col-md-6">
                    <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_color' ) ); ?></label>
                    <input type="color" name="color" value="#000000" class="form-control form-control-color">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_background' ) ); ?></label>
                    <input type="color" name="background" value="#ffffff" class="form-control form-control-color">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" name="background_transparent" id="transparent-bg">
                        <label class="form-check-label" for="transparent-bg"><?php echo esc_html( $instance->translate( 'field_transparent' ) ); ?></label>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-md-6">
                    <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_format' ) ); ?></label>
                    <select name="formats[]" class="form-select form-select-lg" multiple>
                        <option value="png" selected>PNG</option>
                        <option value="svg">SVG</option>
                        <option value="jpg">JPG</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_logo' ) ); ?></label>
                    <input type="hidden" name="logo_url" id="noasoft-logo-url">
                    <div id="noasoft-logo-dropzone" class="dropzone rounded-3 border border-dashed border-2" data-url-field="noasoft-logo-url"></div>
                    <small class="text-muted d-block mt-2" id="noasoft-logo-preview"></small>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-lg btn-primary px-5" id="noasoft-submit">
                    <?php echo esc_html( $instance->translate( 'btn_generate' ) ); ?>
                </button>
            </div>
        </form>
    </div>

    <div class="mt-4" id="noasoft-qr-results"></div>
</div>
