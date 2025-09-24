<?php
if ( ! defined( 'ABSPATH' ) ) exit;


class WP_Sanctum_Activator {
    public static function activate() {
        global $wpdb;
        $table = wp_sanctum_table_name();
        $charset = $wpdb->get_charset_collate();


        $sql = "CREATE TABLE {$table} (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
user_id BIGINT UNSIGNED NOT NULL,
token VARCHAR(64) NOT NULL UNIQUE,
abilities TEXT NULL,
last_used_at DATETIME NULL,
last_ip VARCHAR(45) NULL,
user_agent TEXT NULL,
created_at DATETIME NOT NULL,
expires_at DATETIME NULL,
PRIMARY KEY (id),
KEY user_id (user_id)
) {$charset};";


        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}