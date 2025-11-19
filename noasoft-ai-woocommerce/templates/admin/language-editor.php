<?php
/**
 * Language manager template.
 *
 * @var array $language_view View data from Language_Page::get_view_data().
 */
if ( empty( $language_view ) || ! is_array( $language_view ) ) {
    return;
}
$inline_locale = ( isset( $language_view['selected_locale'] ) && 'default' !== $language_view['selected_locale'] ) ? $language_view['selected_locale'] : 'tr_TR';
if ( empty( $language_view['locale_panels'][ $inline_locale ] ) ) {
    $inline_locale = 'tr_TR';
}
?>
<div class="noasoft-language-manager" data-selected-locale="<?php echo esc_attr( $language_view['selected_locale'] ); ?>">
    <div class="language-top-bar">
        <label for="noasoft-plugin-locale" class="language-select-label">
            <span class="label"><?php esc_html_e( 'Eklenti Dili', 'noasoft-ai-woocommerce' ); ?></span>
            <select id="noasoft-plugin-locale">
                <?php foreach ( $language_view['select_options'] as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $language_view['selected_locale'], $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="description"><?php esc_html_e( 'Bu ayar yalnızca NoaSoft AI Woo eklentisinin arayüzünü etkiler.', 'noasoft-ai-woocommerce' ); ?></p>
    </div>

    <div class="language-panels">
        <?php foreach ( $language_view['locale_panels'] as $locale => $panel ) : ?>
            <div class="language-panel<?php echo $locale === $inline_locale ? ' is-active' : ''; ?>" data-locale="<?php echo esc_attr( $locale ); ?>">
                <div class="panel-header">
                    <div>
                        <h3><?php echo esc_html( $panel['label'] ); ?></h3>
                        <p class="description">
                            <?php
                            printf(
                                esc_html__( '%1$d anahtar · %2$d özel çeviri', 'noasoft-ai-woocommerce' ),
                                intval( $panel['total'] ),
                                intval( $panel['overrides'] )
                            );
                            ?>
                        </p>
                    </div>
                    <div class="language-panel-actions">
                        <button type="button" class="button button-secondary noasoft-language-export" data-format="po" data-locale="<?php echo esc_attr( $locale ); ?>">
                            <?php esc_html_e( 'PO Dışa Aktar', 'noasoft-ai-woocommerce' ); ?>
                        </button>
                        <button type="button" class="button button-secondary noasoft-language-export" data-format="json" data-locale="<?php echo esc_attr( $locale ); ?>">
                            <?php esc_html_e( 'JSON Dışa Aktar', 'noasoft-ai-woocommerce' ); ?>
                        </button>
                        <label class="button noasoft-language-import-label">
                            <?php esc_html_e( 'PO İçe Aktar', 'noasoft-ai-woocommerce' ); ?>
                            <input type="file" class="noasoft-language-import" data-format="po" data-locale="<?php echo esc_attr( $locale ); ?>" accept=".po" />
                        </label>
                        <label class="button noasoft-language-import-label">
                            <?php esc_html_e( 'JSON İçe Aktar', 'noasoft-ai-woocommerce' ); ?>
                            <input type="file" class="noasoft-language-import" data-format="json" data-locale="<?php echo esc_attr( $locale ); ?>" accept=".json" />
                        </label>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

        <div class="language-inline-editor" data-active-locale="<?php echo esc_attr( $inline_locale ); ?>">
            <div class="inline-header">
                <div>
                    <h2><?php esc_html_e( 'Inline Çeviri Düzenleyici', 'noasoft-ai-woocommerce' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Metin veya anahtar arayarak hızlıca düzenleme yapabilirsiniz. Boş bırakmanız varsayılan çeviriyi geri yükler.', 'noasoft-ai-woocommerce' ); ?></p>
                </div>
                <div class="inline-actions">
                    <input type="search" id="noasoft-language-search" placeholder="<?php esc_attr_e( 'Metin ara...', 'noasoft-ai-woocommerce' ); ?>" />
                    <button type="button" class="button" data-modal-target="#noasoft-modal-language-preview"><?php esc_html_e( 'Önizleme', 'noasoft-ai-woocommerce' ); ?></button>
                </div>
            </div>
        <div class="language-inline-results" data-empty-text="<?php esc_attr_e( 'Arama yaptıktan sonra sonuçlar listelenecek.', 'noasoft-ai-woocommerce' ); ?>" data-loading-text="<?php esc_attr_e( 'Yükleniyor...', 'noasoft-ai-woocommerce' ); ?>">
            <div class="language-inline-empty"><?php esc_html_e( 'Arama kutusuna kelime girin.', 'noasoft-ai-woocommerce' ); ?></div>
        </div>
    </div>
</div>
