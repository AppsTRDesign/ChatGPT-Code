<?php
class NoaSoft_AI_Product_Tools {
    public static function parse_product_response( $response ) {
        $defaults = array(
            'seo_title'  => '',
            'seo_desc'   => '',
            'tags'       => '',
            'advantages' => '',
            'features'   => '',
        );

        if ( empty( $response ) ) {
            return $defaults;
        }

        $lines = explode( "\n", wp_strip_all_tags( $response ) );
        $current = '';
        foreach ( $lines as $line ) {
            if ( stripos( $line, 'seo' ) !== false && stripos( $line, 'title' ) !== false ) {
                $current = 'seo_title';
                $defaults[ $current ] = trim( $line );
                continue;
            }
            if ( stripos( $line, 'açıklama' ) !== false ) {
                $current = 'seo_desc';
                $defaults[ $current ] = trim( $line );
                continue;
            }
            if ( stripos( $line, 'etiket' ) !== false ) {
                $current = 'tags';
                $defaults[ $current ] = trim( $line );
                continue;
            }
            if ( false !== stripos( $line, 'avantaj' ) ) {
                $current = 'advantages';
                $defaults['advantages'] .= '- ' . trim( $line ) . "\n";
                continue;
            }
            if ( false !== stripos( $line, 'özellik' ) ) {
                $current = 'features';
                $defaults['features'] .= '- ' . trim( $line ) . "\n";
                continue;
            }

            if ( $current ) {
                $defaults[ $current ] .= ' ' . trim( $line );
            }
        }

        return array_map( 'trim', $defaults );
    }

    public static function optimize_featured_image( $product_id ) {
        $image_id = get_post_thumbnail_id( $product_id );
        if ( ! $image_id ) {
            return __( 'Önce bir ürün görseli yükleyin.', 'noasoft-ai' );
        }

        $file = get_attached_file( $image_id );
        if ( ! file_exists( $file ) ) {
            return __( 'Görsel bulunamadı.', 'noasoft-ai' );
        }

        if ( class_exists( 'Imagick' ) ) {
            try {
                $imagick = new Imagick( $file );
                $imagick->setImageAlphaChannel( Imagick::ALPHACHANNEL_SET );
                $color = $imagick->getImagePixelColor( 0, 0 );
                $imagick->transparentPaintImage( $color, 0, 10, false );
                $imagick->writeImage( $file );
            } catch ( Exception $e ) {
                // Silently ignore background removal issues.
            }
        }

        $editor = wp_get_image_editor( $file );
        if ( is_wp_error( $editor ) ) {
            return $editor->get_error_message();
        }

        $editor->resize( 1200, 1200, false );
        $editor->set_quality( 90 );
        $result = $editor->save( null, 'image/webp' );

        if ( is_wp_error( $result ) ) {
            return $result->get_error_message();
        }

        if ( ! empty( $result['path'] ) ) {
            update_attached_file( $image_id, $result['path'] );
            wp_update_attachment_metadata( $image_id, wp_generate_attachment_metadata( $image_id, $result['path'] ) );
        }

        update_post_meta( $product_id, '_noasoft_ai_last_optimized', current_time( 'mysql' ) );
        return __( 'Görsel WebP formatında optimize edildi.', 'noasoft-ai' );
    }
}
