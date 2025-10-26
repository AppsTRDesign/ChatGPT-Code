<?php
/**
 * Template tags for VBModern Forum theme
 *
 * @package VBModern_Forum
 */

if ( ! function_exists( 'vbmodern_forum_posted_on' ) ) {
    /**
     * Prints HTML with meta information for the current post-date/time.
     */
    function vbmodern_forum_posted_on() {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

        if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
            $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
        }

        $time_string = sprintf(
            $time_string,
            esc_attr( get_the_date( DATE_W3C ) ),
            esc_html( get_the_date() ),
            esc_attr( get_the_modified_date( DATE_W3C ) ),
            esc_html( get_the_modified_date() )
        );

        echo '<span class="post__meta-item post__meta-item--date">' . wp_kses_post( $time_string ) . '</span>';
    }
}

if ( ! function_exists( 'vbmodern_forum_posted_by' ) ) {
    /**
     * Prints HTML with meta information about theme author.
     */
    function vbmodern_forum_posted_by() {
        echo '<span class="post__meta-item post__meta-item--author">';
        printf(
            /* translators: %s: post author. */
            esc_html__( 'by %s', 'vbmodern-forum' ),
            '<a class="post__meta-link" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>'
        );
        echo '</span>';
    }
}

if ( ! function_exists( 'vbmodern_forum_entry_footer' ) ) {
    /**
     * Prints HTML with meta information for the categories, tags and comments.
     */
    function vbmodern_forum_entry_footer() {
        if ( 'post' === get_post_type() ) {
            $categories_list = get_the_category_list( esc_html__( ', ', 'vbmodern-forum' ) );
            if ( $categories_list ) {
                echo '<span class="post__meta-item post__meta-item--categories"><strong>' . esc_html__( 'Categories:', 'vbmodern-forum' ) . '</strong> ' . wp_kses_post( $categories_list ) . '</span>';
            }

            $tags_list = get_the_tag_list( '', esc_html_x( ' • ', 'list item separator', 'vbmodern-forum' ) );
            if ( $tags_list ) {
                echo '<span class="post__meta-item post__meta-item--tags"><strong>' . esc_html__( 'Tags:', 'vbmodern-forum' ) . '</strong> ' . wp_kses_post( $tags_list ) . '</span>';
            }
        }

        if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
            echo '<span class="post__meta-item post__meta-item--comments">';
            comments_popup_link( esc_html__( 'Leave a comment', 'vbmodern-forum' ), esc_html__( '1 Comment', 'vbmodern-forum' ), esc_html__( '% Comments', 'vbmodern-forum' ) );
            echo '</span>';
        }
    }
}
