<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
?>
<section class="pro-ultra-account-overview">
  <div class="pro-ultra-grid pro-ultra-grid--three">
    <article class="pro-ultra-card">
      <header class="pro-ultra-card__header">
        <p class="pro-ultra-meta"><?php esc_html_e( 'Last order', 'pro-ultra-ai' ); ?></p>
        <h3 class="pro-ultra-card__title">
          <?php
          if ( $last_order ) {
            echo esc_html( sprintf( '#%s — %s', $last_order->get_order_number(), wc_format_datetime( $last_order->get_date_created() ) ) );
          } else {
            esc_html_e( 'No orders yet', 'pro-ultra-ai' );
          }
          ?>
        </h3>
      </header>
      <?php if ( $last_order ) : ?>
        <p class="pro-ultra-card__body"><?php echo esc_html( wc_price( $last_order->get_total() ) ); ?></p>
        <a class="pro-ultra-link" href="<?php echo esc_url( wc_get_endpoint_url( 'view-order', $last_order->get_id(), wc_get_page_permalink( 'myaccount' ) ) ); ?>">
          <?php esc_html_e( 'View order details', 'pro-ultra-ai' ); ?>
        </a>
      <?php endif; ?>
    </article>

    <article class="pro-ultra-card">
      <header class="pro-ultra-card__header">
        <p class="pro-ultra-meta"><?php esc_html_e( 'Favorites', 'pro-ultra-ai' ); ?></p>
        <h3 class="pro-ultra-card__title"><?php echo esc_html( $fav_count ); ?></h3>
      </header>
      <p class="pro-ultra-card__body"><?php esc_html_e( 'Products you saved for quick access.', 'pro-ultra-ai' ); ?></p>
      <a class="pro-ultra-link" href="<?php echo esc_url( wc_get_account_endpoint_url( 'favorites' ) ); ?>"><?php esc_html_e( 'View favorites', 'pro-ultra-ai' ); ?></a>
    </article>

    <article class="pro-ultra-card">
      <header class="pro-ultra-card__header">
        <p class="pro-ultra-meta"><?php esc_html_e( 'Wishlist', 'pro-ultra-ai' ); ?></p>
        <h3 class="pro-ultra-card__title"><?php echo esc_html( $wish_count ); ?></h3>
      </header>
      <p class="pro-ultra-card__body"><?php esc_html_e( 'Keep track of products you plan to buy.', 'pro-ultra-ai' ); ?></p>
      <a class="pro-ultra-link" href="<?php echo esc_url( wc_get_account_endpoint_url( 'wishlist' ) ); ?>"><?php esc_html_e( 'View wishlist', 'pro-ultra-ai' ); ?></a>
    </article>

    <article class="pro-ultra-card">
      <header class="pro-ultra-card__header">
        <p class="pro-ultra-meta"><?php esc_html_e( 'Likes', 'pro-ultra-ai' ); ?></p>
        <h3 class="pro-ultra-card__title"><?php echo esc_html( $like_count ); ?></h3>
      </header>
      <p class="pro-ultra-card__body"><?php esc_html_e( 'Items you enjoyed across the store.', 'pro-ultra-ai' ); ?></p>
      <a class="pro-ultra-link" href="<?php echo esc_url( wc_get_account_endpoint_url( 'likes' ) ); ?>"><?php esc_html_e( 'View likes', 'pro-ultra-ai' ); ?></a>
    </article>

    <article class="pro-ultra-card pro-ultra-card--muted">
      <header class="pro-ultra-card__header">
        <p class="pro-ultra-meta"><?php esc_html_e( 'Account', 'pro-ultra-ai' ); ?></p>
        <h3 class="pro-ultra-card__title"><?php echo esc_html( $customer->get_display_name() ); ?></h3>
      </header>
      <p class="pro-ultra-card__body"><?php echo esc_html( $customer->get_email() ); ?></p>
      <a class="pro-ultra-link" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'Edit account details', 'pro-ultra-ai' ); ?></a>
    </article>

    <article class="pro-ultra-card pro-ultra-card--muted">
      <header class="pro-ultra-card__header">
        <p class="pro-ultra-meta"><?php esc_html_e( 'Addresses', 'pro-ultra-ai' ); ?></p>
        <h3 class="pro-ultra-card__title"><?php esc_html_e( 'Shipping & Billing', 'pro-ultra-ai' ); ?></h3>
      </header>
      <p class="pro-ultra-card__body"><?php esc_html_e( 'Manage your saved addresses for faster checkout.', 'pro-ultra-ai' ); ?></p>
      <a class="pro-ultra-link" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'Manage addresses', 'pro-ultra-ai' ); ?></a>
    </article>
  </div>
</section>
