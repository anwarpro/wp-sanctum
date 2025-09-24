<?php
if ( ! defined( 'ABSPATH' ) ) exit;


function wp_sanctum_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'sanctum_tokens';
}


function wp_sanctum_hash_token( $plain ) {
    return hash( 'sha256', $plain );
}


function wp_sanctum_current_abilities() {
    return isset( $GLOBALS['wp_sanctum_current_token_abilities'] ) ? $GLOBALS['wp_sanctum_current_token_abilities'] : null;
}