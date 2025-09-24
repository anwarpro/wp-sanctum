<?php
if ( ! defined( 'ABSPATH' ) ) exit;


class WP_Sanctum_Token {


    public static function create( $user_id, $abilities = array('*'), $expires_at = null ) {
        global $wpdb;
        $plain = bin2hex( random_bytes( 32 ) );
        $hash = wp_sanctum_hash_token( $plain );


        if ( $expires_at ) {
            $ts = strtotime( $expires_at );
            if ( $ts === false ) $expires_at = null;
            else $expires_at = date( 'Y-m-d H:i:s', $ts );
        }


        $wpdb->insert( wp_sanctum_table_name(), array(
            'user_id' => $user_id,
            'token' => $hash,
            'abilities' => wp_json_encode( $abilities ),
            'last_used_at' => null,
            'last_ip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : null,
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : null,
            'created_at' => current_time( 'mysql' ),
            'expires_at' => $expires_at,
        ), array( '%d','%s','%s','%s','%s','%s','%s' ) );


        return array( 'id' => (int) $wpdb->insert_id, 'token' => $plain );
    }


    public static function find_by_plain( $plain ) {
        global $wpdb;
        $hash = wp_sanctum_hash_token( $plain );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . wp_sanctum_table_name() . " WHERE token = %s", $hash ) );
        return $row;
    }


    public static function find_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . wp_sanctum_table_name() . " WHERE id = %d", $id ), ARRAY_A );
    }


    public static function revoke_by_id( $id ) {
        global $wpdb;
        return (bool) $wpdb->delete( wp_sanctum_table_name(), array( 'id' => $id ), array( '%d' ) );
    }


    public static function revoke_all_for_user( $user_id ) {
        global $wpdb;
        return (bool) $wpdb->delete( wp_sanctum_table_name(), array( 'user_id' => $user_id ), array( '%d' ) );
    }


    public static function update_last_used( $id ) {
        global $wpdb;
        return $wpdb->update( wp_sanctum_table_name(), array(
            'last_used_at' => current_time( 'mysql' ),
            'last_ip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : null,
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : null,
        ), array( 'id' => $id ), array( '%s','%s','%s' ), array( '%d' ) );
    }
}