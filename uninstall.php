<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}


global $wpdb;
$table = $wpdb->prefix . 'sanctum_tokens';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );