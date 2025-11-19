<?php
class NoaSoft_AI_Installer {
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $events_table = $wpdb->prefix . 'noasoft_ai_events';
        $reports_table = $wpdb->prefix . 'noasoft_ai_reports';

        $schema = "CREATE TABLE {$events_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT 0,
            session_id varchar(64) NOT NULL,
            product_id bigint(20) unsigned DEFAULT 0,
            event_type varchar(40) NOT NULL,
            payload longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY event_type (event_type)
        ) {$charset_collate};";

        $schema_reports = "CREATE TABLE {$reports_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(191) NOT NULL,
            data longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $schema );
        dbDelta( $schema_reports );

        add_option( 'noasoft_ai_settings', NoaSoft_AI_Settings::get_default_settings() );
    }
}
