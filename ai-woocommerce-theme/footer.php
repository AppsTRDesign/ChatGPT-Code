<footer class="ai-container" style="padding:2rem 1.5rem;">
<div class="ai-grid columns-3">
<div>
<h3><?php esc_html_e( 'AI Satış Asistanı', 'ai-commerce-pro' ); ?></h3>
<p><?php esc_html_e( 'Davranışlara göre öneriler, SKU sorguları ve kıyaslamalar.', 'ai-commerce-pro' ); ?></p>
<button class="ai-btn" id="ai-toggle-assistant"><?php esc_html_e( 'Asistanı Aç', 'ai-commerce-pro' ); ?></button>
</div>
<div>
<h3><?php esc_html_e( 'Hızlı Bağlantılar', 'ai-commerce-pro' ); ?></h3>
<?php wp_nav_menu( [ 'theme_location' => 'footer', 'container' => false ] ); ?>
</div>
<div>
<h3><?php esc_html_e( 'Profil & Wishlist', 'ai-commerce-pro' ); ?></h3>
<ul>
<li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Hesabım', 'ai-commerce-pro' ); ?></a></li>
<li><a href="#favorites"><?php esc_html_e( 'Favoriler', 'ai-commerce-pro' ); ?></a></li>
<li><a href="#wishlist"><?php esc_html_e( 'Wishlist', 'ai-commerce-pro' ); ?></a></li>
</ul>
</div>
</div>
<p style="margin-top:1.25rem;color:#6c757d;">© <?php echo esc_html( date_i18n( 'Y' ) ); ?> AI Commerce Pro</p>
</footer>
<div id="ai-assistant-panel" class="ai-assistant-panel" aria-hidden="true">
<div class="ai-assistant-header">
<strong><?php esc_html_e( 'AI Satış Danışmanı', 'ai-commerce-pro' ); ?></strong>
<button type="button" class="ai-assistant-close" aria-label="Kapat">×</button>
</div>
<div class="ai-assistant-body">
<div class="ai-assistant-log" role="log" aria-live="polite"></div>
<form id="ai-assistant-form" class="ai-assistant-form">
<textarea name="prompt" rows="3" placeholder="<?php esc_attr_e( 'İsteklerinizi yazın, ürün, SKU veya kargo sorun.', 'ai-commerce-pro' ); ?>"></textarea>
<div class="ai-flex" style="gap:6px;flex-wrap:wrap;">
<label><input type="radio" name="mode" value="chat" checked /> Chat</label>
<label><input type="radio" name="mode" value="recommend" /> <?php esc_html_e( 'Öner', 'ai-commerce-pro' ); ?></label>
<label><input type="radio" name="mode" value="compare" /> <?php esc_html_e( 'Kıyasla', 'ai-commerce-pro' ); ?></label>
<label><input type="radio" name="mode" value="order" /> <?php esc_html_e( 'Kargo/SKU', 'ai-commerce-pro' ); ?></label>
</div>
<button class="ai-btn" type="submit"><?php esc_html_e( 'Gönder', 'ai-commerce-pro' ); ?></button>
</form>
</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
