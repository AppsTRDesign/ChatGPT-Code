<?php
$settings   = get_option( NoaSoft_QR_Code_Plugin::OPTION_SETTINGS, [] );
$primary    = $settings['primary_color'] ?? '#2563eb';
$accent     = $settings['accent_color'] ?? '#0ea5e9';
$token      = $settings['token'] ?? '';
$remaining  = get_option( NoaSoft_QR_Code_Plugin::OPTION_LIMIT, '' );
?>
<div class="wrap noasoft-qr-admin" style="max-width:1200px;margin:0 auto;">
    <h1 class="mb-4"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'settings_title' ) ); ?></h1>
    <div class="alert alert-info shadow-sm border-0">
        <?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'token_hint' ) ); ?>
        <a href="https://qrcode.noasoft.org" target="_blank" rel="noopener noreferrer">https://qrcode.noasoft.org</a>
    </div>
    <form method="post" action="options.php" id="noasoft-settings-form" class="bg-white rounded-4 p-4 shadow-sm">
        <?php settings_fields( 'noasoft_qr_settings_group' ); ?>
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'token_label' ) ); ?></label>
                <input type="text" name="<?php echo esc_attr( NoaSoft_QR_Code_Plugin::OPTION_SETTINGS ); ?>[token]" value="<?php echo esc_attr( $token ); ?>" class="form-control form-control-lg" placeholder="TOKEN">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'primary_color' ) ); ?></label>
                <input type="color" name="<?php echo esc_attr( NoaSoft_QR_Code_Plugin::OPTION_SETTINGS ); ?>[primary_color]" value="<?php echo esc_attr( $primary ); ?>" class="form-control form-control-color" data-color-picker>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'accent_color' ) ); ?></label>
                <input type="color" name="<?php echo esc_attr( NoaSoft_QR_Code_Plugin::OPTION_SETTINGS ); ?>[accent_color]" value="<?php echo esc_attr( $accent ); ?>" class="form-control form-control-color" data-color-picker>
            </div>
        </div>
        <div class="mt-4 d-flex gap-3 align-items-center flex-wrap">
            <button type="submit" class="btn btn-primary btn-lg px-4">
                <?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'save_settings' ) ); ?>
            </button>
            <?php if ( $remaining !== '' ) : ?>
                <span class="badge bg-dark fs-6"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'remaining_label' ) . ': ' . $remaining ); ?></span>
            <?php endif; ?>
        </div>
    </form>

    <section class="mt-5 bg-white rounded-4 shadow-sm p-4">
        <h2 class="h4 mb-4"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'stats_title' ) ); ?></h2>
        <div class="row g-4 align-items-center">
            <div class="col-lg-4">
                <div class="list-group list-group-flush" id="noasoft-chart-tabs">
                    <button type="button" class="list-group-item list-group-item-action active" data-target="daily"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'chart_daily' ) ); ?></button>
                    <button type="button" class="list-group-item list-group-item-action" data-target="weekly"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'chart_weekly' ) ); ?></button>
                    <button type="button" class="list-group-item list-group-item-action" data-target="monthly"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'chart_monthly' ) ); ?></button>
                    <button type="button" class="list-group-item list-group-item-action" data-target="yearly"><?php echo esc_html( NoaSoft_QR_Code_Plugin::get_instance()->translate( 'chart_yearly' ) ); ?></button>
                </div>
            </div>
            <div class="col-lg-8">
                <canvas id="noasoft-qr-stats" height="280"></canvas>
            </div>
        </div>
    </section>
</div>
