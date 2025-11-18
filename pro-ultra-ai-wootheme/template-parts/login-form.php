<div class="pro-ultra-auth pro-ultra-card">
<h1><?php esc_html_e( 'Welcome Back', 'pro-ultra-ai' ); ?></h1>
<p><?php esc_html_e( 'Sign in to access your account.', 'pro-ultra-ai' ); ?></p>
<form method="post" data-action="pro_ultra_login">
<?php wp_nonce_field( 'pro-ultra-auth', 'security' ); ?>
<label for="pro-ultra-login-username"><?php esc_html_e( 'Username or Email', 'pro-ultra-ai' ); ?></label>
<input type="text" id="pro-ultra-login-username" name="username" required />
<label for="pro-ultra-login-password"><?php esc_html_e( 'Password', 'pro-ultra-ai' ); ?></label>
<input type="password" id="pro-ultra-login-password" name="password" required />
<div class="pro-ultra-auth-actions">
<label><input type="checkbox" name="rememberme" value="1" /> <?php esc_html_e( 'Remember me', 'pro-ultra-ai' ); ?></label>
<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'pro-ultra-ai' ); ?></a>
</div>
<button type="submit" class="pro-ultra-btn"><?php esc_html_e( 'Login', 'pro-ultra-ai' ); ?></button>
</form>
<div class="pro-ultra-meta">
<span><?php esc_html_e( 'New here?', 'pro-ultra-ai' ); ?></span>
<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Create your account', 'pro-ultra-ai' ); ?></a>
</div>
</div>
