<?php
namespace ProUltra\Core;

use ProUltra\Admin\Theme_Options;

/**
 * SVG icon helper for layout-aware inline icons.
 */
class SVG_Icons {
    /**
     * Get sanitized inline SVG markup for a named icon.
     *
     * @param string $name  Icon slug e.g. payment-visa.
     * @param string $class Optional CSS class string.
     * @return string
     */
    public static function get_icon( $name, $class = '' ) {
        $slug   = sanitize_file_name( wp_strip_all_tags( $name ) );
        $layout = Theme_Options::get_layout_settings();
        $folder = self::get_folder_by_layout( $layout['layout'] );
        $path   = trailingslashit( PRO_ULTRA_AI_PATH . 'assets/svg/' . $folder ) . $slug . '.svg';

        if ( ! file_exists( $path ) ) {
            $fallback = trailingslashit( PRO_ULTRA_AI_PATH . 'assets/svg/minimal' ) . $slug . '.svg';
            if ( file_exists( $fallback ) ) {
                $path = $fallback;
            } else {
                return '';
            }
        }

        $svg = file_get_contents( $path );
        if ( ! $svg ) {
            return '';
        }

        if ( $class ) {
            if ( strpos( $svg, '<svg' ) !== false ) {
                $svg = preg_replace( '/<svg(\s)/', '<svg class="' . esc_attr( $class ) . '"$1', $svg, 1 );
            }
        }

        return wp_kses( $svg, self::allowed_tags() );
    }

    /**
     * Map layout slug to svg folder.
     */
    protected static function get_folder_by_layout( $layout ) {
        $map = array(
            'minimal-white'   => 'minimal',
            'dark-future'     => 'dark',
            'gradient-modern' => 'gradient',
            'classic-shop'    => 'classic',
            'luxury-gold'     => 'luxury',
        );

        return isset( $map[ $layout ] ) ? $map[ $layout ] : 'minimal';
    }

    /**
     * Allowed tags/attributes for inline SVG output.
     */
    protected static function allowed_tags() {
        $attrs = array(
            'class'         => true,
            'aria-label'    => true,
            'aria-hidden'   => true,
            'role'          => true,
            'fill'          => true,
            'fill-opacity'  => true,
            'stroke'        => true,
            'stroke-width'  => true,
            'stroke-linecap'=> true,
            'stroke-linejoin'=> true,
            'stroke-dasharray'=> true,
            'stroke-opacity'=> true,
            'viewBox'       => true,
            'xmlns'         => true,
            'x'             => true,
            'y'             => true,
            'cx'            => true,
            'cy'            => true,
            'r'             => true,
            'width'         => true,
            'height'        => true,
            'rx'            => true,
            'ry'            => true,
            'd'             => true,
            'x1'            => true,
            'y1'            => true,
            'x2'            => true,
            'y2'            => true,
            'offset'        => true,
            'stop-color'    => true,
            'stop-opacity'  => true,
            'id'            => true,
        );

        return array(
            'svg'           => $attrs,
            'path'          => $attrs,
            'rect'          => $attrs,
            'circle'        => $attrs,
            'defs'          => $attrs,
            'linearGradient'=> $attrs,
            'stop'          => $attrs,
            'g'             => $attrs,
        );
    }
}
