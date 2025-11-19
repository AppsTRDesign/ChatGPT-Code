<?php
namespace NoaSoft\AiWoo\Helpers;

/**
 * Helper utilities for translation management.
 */
class Language_Helper {
    const OVERRIDE_OPTION = 'noasoft_ai_woo_translation_overrides';

    /**
     * Override cache.
     *
     * @var array
     */
    protected static $override_cache = array();

    /**
     * Base string cache.
     *
     * @var array
     */
    protected static $base_strings;

    /**
     * Bootstrap hooks.
     *
     * @return void
     */
    public static function bootstrap() {
        static $hooked = false;
        if ( $hooked ) {
            return;
        }

        add_filter( 'gettext', array( __CLASS__, 'filter_gettext' ), 20, 3 );
        add_filter( 'gettext_with_context', array( __CLASS__, 'filter_gettext_with_context' ), 20, 4 );
        $hooked = true;
    }

    /**
     * Locale choices.
     *
     * @return array
     */
    public static function get_locale_choices() {
        return array(
            'tr_TR' => __( 'Türkçe (tr_TR)', 'noasoft-ai-woocommerce' ),
            'en_US' => __( 'English (en_US)', 'noasoft-ai-woocommerce' ),
        );
    }

    /**
     * Is locale supported.
     *
     * @param string $locale Locale code.
     * @return bool
     */
    public static function is_supported_locale( $locale ) {
        return in_array( $locale, array_keys( self::get_locale_choices() ), true );
    }

    /**
     * Active locale considering plugin setting.
     *
     * @return string
     */
    public static function get_active_locale() {
        $selected = Options::get_plugin_locale();
        if ( 'default' === $selected ) {
            if ( function_exists( 'determine_locale' ) ) {
                $selected = determine_locale();
            } else {
                $selected = get_locale();
            }
        }

        return $selected;
    }

    /**
     * gettext override.
     *
     * @param string $translation Translation.
     * @param string $text        Original.
     * @param string $domain      Text domain.
     * @return string
     */
    public static function filter_gettext( $translation, $text, $domain ) {
        if ( 'noasoft-ai-woocommerce' !== $domain ) {
            return $translation;
        }

        $locale = self::get_active_locale();
        if ( ! self::is_supported_locale( $locale ) ) {
            return $translation;
        }

        $overrides = self::get_overrides( $locale );
        if ( isset( $overrides[ $text ] ) && '' !== $overrides[ $text ] ) {
            return $overrides[ $text ];
        }

        return $translation;
    }

    /**
     * Contextual gettext override.
     *
     * @param string $translation Translation.
     * @param string $text        Original.
     * @param string $context     Context.
     * @param string $domain      Domain.
     * @return string
     */
    public static function filter_gettext_with_context( $translation, $text, $context, $domain ) {
        unset( $context );
        return self::filter_gettext( $translation, $text, $domain );
    }

    /**
     * Get overrides.
     *
     * @param string $locale Locale.
     * @return array
     */
    public static function get_overrides( $locale ) {
        if ( isset( self::$override_cache[ $locale ] ) ) {
            return self::$override_cache[ $locale ];
        }

        $store = get_option( self::OVERRIDE_OPTION, array() );
        if ( ! is_array( $store ) ) {
            $store = array();
        }

        $overrides = array();
        if ( isset( $store[ $locale ] ) && is_array( $store[ $locale ] ) ) {
            foreach ( $store[ $locale ] as $original => $translation ) {
                $overrides[ $original ] = wp_kses_post( $translation );
            }
        }

        self::$override_cache[ $locale ] = $overrides;
        return $overrides;
    }

    /**
     * Save override entry.
     *
     * @param string $locale      Locale.
     * @param string $original    Original text.
     * @param string $translation Translation text.
     * @return void
     */
    public static function save_override( $locale, $original, $translation ) {
        if ( ! self::is_supported_locale( $locale ) ) {
            return;
        }

        $allowed = array_flip( self::get_base_strings() );
        if ( ! isset( $allowed[ $original ] ) ) {
            return;
        }

        $store = get_option( self::OVERRIDE_OPTION, array() );
        if ( ! is_array( $store ) ) {
            $store = array();
        }

        if ( ! isset( $store[ $locale ] ) || ! is_array( $store[ $locale ] ) ) {
            $store[ $locale ] = array();
        }

        $translation = wp_kses_post( $translation );
        if ( '' === trim( wp_strip_all_tags( $translation ) ) || self::is_default_translation( $locale, $original, $translation ) ) {
            unset( $store[ $locale ][ $original ] );
        } else {
            $store[ $locale ][ $original ] = $translation;
        }

        update_option( self::OVERRIDE_OPTION, $store );
        self::$override_cache[ $locale ] = $store[ $locale ];
    }

    /**
     * Replace overrides in bulk.
     *
     * @param string $locale Locale.
     * @param array  $map    Original => translation.
     * @return void
     */
    public static function replace_overrides( $locale, $map ) {
        if ( ! self::is_supported_locale( $locale ) ) {
            return;
        }

        $allowed = array_flip( self::get_base_strings() );
        $clean   = array();
        foreach ( (array) $map as $original => $translation ) {
            if ( ! isset( $allowed[ $original ] ) ) {
                continue;
            }

            $translation = wp_kses_post( $translation );
            if ( '' === trim( wp_strip_all_tags( $translation ) ) || self::is_default_translation( $locale, $original, $translation ) ) {
                continue;
            }

            $clean[ $original ] = $translation;
        }

        $store = get_option( self::OVERRIDE_OPTION, array() );
        if ( ! is_array( $store ) ) {
            $store = array();
        }

        $store[ $locale ] = $clean;
        update_option( self::OVERRIDE_OPTION, $store );
        self::$override_cache[ $locale ] = $clean;
    }

    /**
     * Catalog entries.
     *
     * @param string $locale Locale.
     * @return array
     */
    public static function get_catalog_entries( $locale ) {
        $base_strings = self::get_base_strings();
        sort( $base_strings );
        $json_map     = self::load_json_catalog( $locale );
        $overrides    = self::get_overrides( $locale );
        $entries      = array();

        foreach ( $base_strings as $original ) {
            $translation  = '';
            $has_override = false;

            if ( isset( $overrides[ $original ] ) ) {
                $translation  = $overrides[ $original ];
                $has_override = true;
            } elseif ( isset( $json_map[ $original ] ) ) {
                $translation = $json_map[ $original ];
            } elseif ( 'en_US' === $locale ) {
                $translation = $original;
            }

            $entries[] = array(
                'key'          => self::encode_key( $original ),
                'original'     => $original,
                'translation'  => $translation,
                'has_override' => $has_override,
            );
        }

        return $entries;
    }

    /**
     * Translation map for export.
     *
     * @param string $locale Locale.
     * @return array
     */
    public static function get_translation_map( $locale ) {
        $entries = self::get_catalog_entries( $locale );
        $map     = array();

        foreach ( $entries as $entry ) {
            $map[ $entry['original'] ] = $entry['translation'];
        }

        return $map;
    }

    /**
     * Search catalog.
     *
     * @param string $locale Locale.
     * @param string $query  Query.
     * @param int    $limit  Limit.
     * @return array
     */
    public static function search_catalog( $locale, $query = '', $limit = 40 ) {
        $entries = self::get_catalog_entries( $locale );
        if ( '' !== $query ) {
            $entries = array_filter(
                $entries,
                function ( $entry ) use ( $query ) {
                    return false !== stripos( $entry['original'], $query ) || false !== stripos( $entry['translation'], $query );
                }
            );
        }

        $entries = array_slice( array_values( $entries ), 0, max( 1, (int) $limit ) );
        foreach ( $entries as &$entry ) {
            $entry['original']    = wp_kses_post( $entry['original'] );
            $entry['translation'] = wp_kses_post( $entry['translation'] );
        }

        return $entries;
    }

    /**
     * JSON export string.
     *
     * @param string $locale Locale.
     * @return string
     */
    public static function generate_json_export( $locale ) {
        return wp_json_encode( self::get_translation_map( $locale ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    }

    /**
     * PO export string.
     *
     * @param string $locale Locale.
     * @return string
     */
    public static function generate_po_export( $locale ) {
        $map   = self::get_translation_map( $locale );
        $lines = array(
            'msgid ""',
            'msgstr ""',
            '"Project-Id-Version: NoaSoft AI WooCommerce\\n"',
            '"POT-Creation-Date: ' . gmdate( 'Y-m-d H:i:s+00:00' ) . '\\n"',
            '"Language: ' . $locale . '\\n"',
            '"Content-Type: text/plain; charset=UTF-8\\n"',
            '"Content-Transfer-Encoding: 8bit\\n"',
            '"X-Generator: NoaSoft Language Manager\\n"',
            '',
        );

        foreach ( $map as $original => $translation ) {
            $lines[] = 'msgid "' . self::escape_po( $original ) . '"';
            $lines[] = 'msgstr "' . self::escape_po( $translation ) . '"';
            $lines[] = '';
        }

        return implode( "\n", $lines );
    }

    /**
     * Import PO.
     *
     * @param string $locale  Locale.
     * @param string $content Content.
     * @return int
     */
    public static function import_po_content( $locale, $content ) {
        $map = self::parse_po_content( $content );
        if ( empty( $map ) ) {
            return 0;
        }

        self::replace_overrides( $locale, $map );
        return count( $map );
    }

    /**
     * Import JSON.
     *
     * @param string $locale  Locale.
     * @param string $content JSON.
     * @return int
     */
    public static function import_json_content( $locale, $content ) {
        $decoded = json_decode( $content, true );
        if ( ! is_array( $decoded ) || empty( $decoded ) ) {
            return 0;
        }

        self::replace_overrides( $locale, $decoded );
        return count( $decoded );
    }

    /**
     * Locale stats.
     *
     * @return array
     */
    public static function get_locale_stats() {
        $stats        = array();
        $base_strings = self::get_base_strings();
        $total        = count( $base_strings );
        $choices      = self::get_locale_choices();

        foreach ( $choices as $locale => $label ) {
            $overrides = self::get_overrides( $locale );
            $stats[ $locale ] = array(
                'label'     => $label,
                'total'     => $total,
                'overrides' => count( $overrides ),
            );
        }

        return $stats;
    }

    /**
     * Encode entry key.
     *
     * @param string $text Text.
     * @return string
     */
    public static function encode_key( $text ) {
        return base64_encode( rawurlencode( $text ) );
    }

    /**
     * Decode entry key.
     *
     * @param string $encoded Encoded string.
     * @return string
     */
    public static function decode_key( $encoded ) {
        $decoded = base64_decode( $encoded, true );
        if ( false === $decoded ) {
            return '';
        }

        return rawurldecode( $decoded );
    }

    /**
     * Load JSON catalog file.
     *
     * @param string $locale Locale.
     * @return array
     */
    protected static function load_json_catalog( $locale ) {
        $path = NOASOFT_AI_WOO_PLUGIN_DIR . 'languages/noasoft-ai-woocommerce-' . $locale . '.json';
        if ( ! file_exists( $path ) ) {
            return array();
        }

        $contents = file_get_contents( $path );
        if ( false === $contents ) {
            return array();
        }

        $decoded = json_decode( $contents, true );
        if ( ! is_array( $decoded ) ) {
            return array();
        }

        $clean = array();
        foreach ( $decoded as $original => $translation ) {
            $clean[ $original ] = wp_kses_post( $translation );
        }

        return $clean;
    }

    /**
     * Check if translation equals bundled default.
     *
     * @param string $locale Locale.
     * @param string $original Original string.
     * @param string $translation Translation value.
     * @return bool
     */
    protected static function is_default_translation( $locale, $original, $translation ) {
        $defaults = self::load_json_catalog( $locale );
        if ( isset( $defaults[ $original ] ) ) {
            return $defaults[ $original ] === $translation;
        }

        if ( 'en_US' === $locale ) {
            return $original === $translation;
        }

        return false;
    }

    /**
     * Parse PO content.
     *
     * @param string $content Content.
     * @return array
     */
    protected static function parse_po_content( $content ) {
        $lines   = preg_split( '/\r?\n/', (string) $content );
        $entries = array();
        $msgid   = null;
        $msgstr  = null;
        $state   = '';

        foreach ( $lines as $line ) {
            $trim = trim( $line );
            if ( 0 === strpos( $trim, 'msgid ' ) ) {
                $msgid = stripcslashes( trim( substr( $trim, 6 ), '"' ) );
                if ( '' === $msgid ) {
                    $msgid = null;
                }
                $state = 'msgid';
                continue;
            }

            if ( 0 === strpos( $trim, 'msgstr ' ) ) {
                $msgstr = stripcslashes( trim( substr( $trim, 7 ), '"' ) );
                $state  = 'msgstr';
            } elseif ( 0 === strpos( $trim, '"' ) && 'msgid' === $state && null !== $msgid ) {
                $msgid .= stripcslashes( trim( $trim, '"' ) );
            } elseif ( 0 === strpos( $trim, '"' ) && 'msgstr' === $state && null !== $msgid ) {
                $msgstr .= stripcslashes( trim( $trim, '"' ) );
            }

            if ( 'msgstr' === $state && null !== $msgid && null !== $msgstr ) {
                $entries[ $msgid ] = $msgstr;
                $msgid             = null;
                $msgstr            = null;
                $state             = '';
            }
        }

        return $entries;
    }

    /**
     * Escape PO string.
     *
     * @param string $text Text.
     * @return string
     */
    protected static function escape_po( $text ) {
        return addcslashes( $text, "\0\n\r\t\f\v\"\\" );
    }

    /**
     * Base string list.
     *
     * @return array
     */
    protected static function get_base_strings() {
        if ( null !== self::$base_strings ) {
            return self::$base_strings;
        }

        $pot_path = NOASOFT_AI_WOO_PLUGIN_DIR . 'languages/noasoft-woo-ai.pot';
        $strings  = array();
        if ( file_exists( $pot_path ) ) {
            $contents = file_get_contents( $pot_path );
            if ( false !== $contents ) {
                if ( preg_match_all( '/msgid "(.*?)"/s', $contents, $matches ) ) {
                    foreach ( $matches[1] as $raw ) {
                        $text = stripcslashes( $raw );
                        if ( '' !== $text ) {
                            $strings[] = $text;
                        }
                    }
                }
            }
        }

        if ( empty( $strings ) ) {
            foreach ( array_keys( self::get_locale_choices() ) as $locale ) {
                $strings = array_merge( $strings, array_keys( self::load_json_catalog( $locale ) ) );
            }
        }

        $strings                = array_values( array_unique( $strings ) );
        self::$base_strings     = $strings;
        return self::$base_strings;
    }
}

