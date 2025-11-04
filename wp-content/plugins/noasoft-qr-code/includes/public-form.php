<?php
$instance     = NoaSoft_QR_Code_Plugin::get_instance();
$locale       = $instance->get_locale();
$unique       = wp_unique_id( 'noasoft_public_' );
$dynamic_id   = $unique . '_dynamic';
$dropzone_id  = $unique . '_dropzone';
$logo_field   = $unique . '_logo';
$preview_id   = $unique . '_preview';
?>
<div class="noasoft-qr-frontend" data-locale="<?php echo esc_attr( $locale ); ?>">
    <form class="noasoft-qr-form" data-frontend="true" data-dynamic="<?php echo esc_attr( $dynamic_id ); ?>" data-dropzone="<?php echo esc_attr( $dropzone_id ); ?>" data-logo-field="<?php echo esc_attr( $logo_field ); ?>" data-logo-preview="<?php echo esc_attr( $preview_id ); ?>">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_type' ) ); ?></label>
                <select name="type" class="form-select">
                    <option value="url"><?php echo esc_html( $instance->translate( 'type_url' ) ); ?></option>
                    <option value="text"><?php echo esc_html( $instance->translate( 'type_text' ) ); ?></option>
                    <option value="email"><?php echo esc_html( $instance->translate( 'type_email' ) ); ?></option>
                    <option value="phone"><?php echo esc_html( $instance->translate( 'type_phone' ) ); ?></option>
                    <option value="sms"><?php echo esc_html( $instance->translate( 'type_sms' ) ); ?></option>
                    <option value="whatsapp"><?php echo esc_html( $instance->translate( 'type_whatsapp' ) ); ?></option>
                    <option value="wifi"><?php echo esc_html( $instance->translate( 'type_wifi' ) ); ?></option>
                    <option value="location"><?php echo esc_html( $instance->translate( 'type_location' ) ); ?></option>
                    <option value="event"><?php echo esc_html( $instance->translate( 'type_event' ) ); ?></option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_ratio' ) ); ?></label>
                <select name="aspect_ratio" class="form-select">
                    <option value="1:1">1:1</option>
                    <option value="4:5">4:5</option>
                    <option value="16:9">16:9</option>
                    <option value="9:16">9:16</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_width' ) ); ?></label>
                <input type="number" name="width" value="400" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_height' ) ); ?></label>
                <input type="number" name="height" value="400" class="form-control">
            </div>
        </div>
        <div class="row g-3 mt-0" id="<?php echo esc_attr( $dynamic_id ); ?>"></div>
        <div class="row g-3 mt-0">
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_color' ) ); ?></label>
                <input type="color" name="color" value="#000000" class="form-control form-control-color">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_background' ) ); ?></label>
                <input type="color" name="background" value="#ffffff" class="form-control form-control-color">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="background_transparent" value="1" id="noasoft-public-transparent">
                    <label class="form-check-label" for="noasoft-public-transparent"><?php echo esc_html( $instance->translate( 'field_transparent' ) ); ?></label>
                </div>
            </div>
        </div>
        <div class="row g-3 mt-0">
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_format' ) ); ?></label>
                <select name="formats[]" class="form-select" multiple>
                    <option value="png" selected>PNG</option>
                    <option value="svg">SVG</option>
                    <option value="jpg">JPG</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo esc_html( $instance->translate( 'field_logo' ) ); ?></label>
                <input type="hidden" name="logo_url" id="<?php echo esc_attr( $logo_field ); ?>">
                <div id="<?php echo esc_attr( $dropzone_id ); ?>" class="dropzone rounded-3 border border-dashed border-2"></div>
                <small class="text-muted d-block mt-2" id="<?php echo esc_attr( $preview_id ); ?>"></small>
            </div>
        </div>
        <div class="mt-3 text-end">
            <button type="submit" class="btn btn-primary px-4">
                <?php echo esc_html( $instance->translate( 'btn_generate' ) ); ?>
            </button>
        </div>
    </form>
    <div class="noasoft-qr-result mt-4"></div>
</div>
