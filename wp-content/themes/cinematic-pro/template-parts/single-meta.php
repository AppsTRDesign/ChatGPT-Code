<?php
$meta = apply_filters( 'cinematic_pro_meta', [], get_the_ID() );
?>
<div class="single-meta">
<?php if ( ! empty( $meta['release_date'] ) ) : ?>
<div class="single-meta__item">
<span class="single-meta__label"><?php esc_html_e( 'Yayın Tarihi', 'cinematic-pro' ); ?></span>
<span class="single-meta__value"><?php echo esc_html( $meta['release_date'] ); ?></span>
</div>
<?php endif; ?>

<?php if ( ! empty( $meta['rating'] ) ) : ?>
<div class="single-meta__item">
<span class="single-meta__label"><?php esc_html_e( 'IMDB Puanı', 'cinematic-pro' ); ?></span>
<span class="single-meta__value">⭐ <?php echo esc_html( $meta['rating'] ); ?></span>
</div>
<?php endif; ?>

<?php if ( ! empty( $meta['trailer'] ) ) : ?>
<div class="single-meta__item">
<span class="single-meta__label"><?php esc_html_e( 'Fragman', 'cinematic-pro' ); ?></span>
<a class="single-meta__value" href="<?php echo esc_url( $meta['trailer'] ); ?>" target="_blank" rel="noopener">
<?php esc_html_e( 'Fragmanı İzle', 'cinematic-pro' ); ?>
</a>
</div>
<?php endif; ?>

<?php the_terms( get_the_ID(), 'genre', '<div class="single-meta__item"><span class="single-meta__label">' . esc_html__( 'Türler', 'cinematic-pro' ) . '</span><span class="single-meta__value">', ', ', '</span></div>' ); ?>
</div>
