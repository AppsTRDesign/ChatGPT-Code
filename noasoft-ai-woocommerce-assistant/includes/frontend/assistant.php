<?php
$assistant = $this->settings->get( 'assistant' );
$classes   = 'noasoft-ai-chat noasoft-ai-' . esc_attr( $assistant['position'] );
$avatar    = $assistant['avatar_id'] ? wp_get_attachment_image_url( $assistant['avatar_id'], 'thumbnail' ) : NOASOFT_AI_URL . 'assets/img/avatar.svg';
?>
<div class="<?php echo esc_attr( $classes ); ?>" data-theme='<?php echo esc_attr( wp_json_encode( $assistant['theme'] ) ); ?>' style="--noasoft-radius: <?php echo esc_attr( $assistant['button_radius'] ?? 12 ); ?>px;">
    <button class="noasoft-ai-trigger" aria-expanded="false">
        <span><?php echo esc_html( $assistant['greeting'] ); ?></span>
    </button>
    <div class="noasoft-ai-window" role="dialog" aria-label="AI Asistanı">
        <header>
            <img src="<?php echo esc_url( $avatar ); ?>" alt="AI">
            <div>
                <strong><?php esc_html_e( 'AI Satış Asistanı', 'noasoft-ai' ); ?></strong>
                <p><?php echo esc_html( $assistant['first_message'] ); ?></p>
            </div>
            <button type="button" class="noasoft-ai-close" aria-label="Kapat">&times;</button>
        </header>
        <div class="noasoft-ai-messages" aria-live="polite"></div>
        <form class="noasoft-ai-form" enctype="multipart/form-data">
            <label class="noasoft-ai-upload">
                <input type="file" name="image" accept="image/*">
                <span>📎</span>
            </label>
            <input type="text" name="message" placeholder="Sorularınızı yazın...">
            <button type="submit">Gönder</button>
        </form>
    </div>
</div>
