<?php
/**
 * Additional helper functions for VBModern Forum theme
 *
 * @package VBModern_Forum
 */

/**
 * Filters the excerpt more string to add custom call-to-action.
 *
 * @param string $more Default more string.
 */
function vbmodern_forum_excerpt_more( $more ) {
    if ( is_admin() ) {
        return $more;
    }

    return sprintf( '… <a class="post__meta-link" href="%1$s">%2$s</a>', esc_url( get_permalink() ), esc_html__( 'Continue reading', 'vbmodern-forum' ) );
}
add_filter( 'excerpt_more', 'vbmodern_forum_excerpt_more' );

/**
 * Adds custom classes to the body tag for forum layouts.
 *
 * @param array $classes Body classes.
 */
function vbmodern_forum_body_classes( $classes ) {
    $classes[] = 'vbmodern-forum';
    $classes[] = is_user_logged_in() ? 'vbmodern-forum--member' : 'vbmodern-forum--guest';

    return $classes;
}
add_filter( 'body_class', 'vbmodern_forum_body_classes' );

/**
 * Helper to get badges for topic activity.
 *
 * @param int $post_id Post ID.
 */
function vbmodern_forum_get_activity_badge( $post_id ) {
    $comment_count = get_comments_number( $post_id );
    $badge         = [ 'label' => __( 'New', 'vbmodern-forum' ), 'class' => 'badge--primary' ];

    if ( $comment_count > 25 ) {
        $badge = [ 'label' => __( 'Hot', 'vbmodern-forum' ), 'class' => 'badge--danger' ];
    } elseif ( $comment_count > 10 ) {
        $badge = [ 'label' => __( 'Active', 'vbmodern-forum' ), 'class' => 'badge--success' ];
    }

    return $badge;
}

/**
 * Render icon markup.
 *
 * @param string $name Icon name.
 */
function vbmodern_forum_icon( $name ) {
    $icons = [
        'chevron-right' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'activity'      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 12h-4l-3 9-6-18-3 9H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'users'         => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 00-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'chat'          => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];

    return $icons[ $name ] ?? '';
}
