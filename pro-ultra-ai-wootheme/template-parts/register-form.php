<div class="pro-ultra-auth pro-ultra-card">
<h1><?php esc_html_e( 'Create Account', 'pro-ultra-ai' ); ?></h1>
<p><?php esc_html_e( 'Join to unlock personalized AI shopping.', 'pro-ultra-ai' ); ?></p>
<form method="post" data-action="pro_ultra_register">
<?php wp_nonce_field( 'pro-ultra-auth', 'security' ); ?>
<label for="pro-ultra-register-first"><?php esc_html_e( 'First name', 'pro-ultra-ai' ); ?></label>
<input type="text" id="pro-ultra-register-first" name="first_name" />
<label for="pro-ultra-register-last"><?php esc_html_e( 'Last name', 'pro-ultra-ai' ); ?></label>
<input type="text" id="pro-ultra-register-last" name="last_name" />
<label for="pro-ultra-register-email"><?php esc_html_e( 'Email', 'pro-ultra-ai' ); ?></label>
<input type="email" id="pro-ultra-register-email" name="email" required />
<label for="pro-ultra-register-password"><?php esc_html_e( 'Password', 'pro-ultra-ai' ); ?></label>
<input type="password" id="pro-ultra-register-password" name="password" required />
<label><input type="checkbox" name="kvkk" value="1" required /> <?php esc_html_e( 'I approve KVKK & privacy terms.', 'pro-ultra-ai' ); ?></label>
<button type="submit" class="pro-ultra-btn"><?php esc_html_e( 'Register', 'pro-ultra-ai' ); ?></button>
</form>
<div class="pro-ultra-meta">
<span><?php esc_html_e( 'Already have an account?', 'pro-ultra-ai' ); ?></span>
<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Sign in', 'pro-ultra-ai' ); ?></a>
</div>
</div>
