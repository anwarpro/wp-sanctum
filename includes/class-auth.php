<?php
if ( ! defined( 'ABSPATH' ) ) exit;


class WP_Sanctum_Auth {
    public function __construct() {
        add_filter( 'determine_current_user', array( $this, 'maybe_authenticate' ), 20 );
    }


    public function maybe_authenticate( $user_id ) {
        if ( ! empty( $user_id ) ) return $user_id; // already authenticated


        $token = $this->get_bearer_token();
        if ( ! $token ) return null;


        $row = WP_Sanctum_Token::find_by_plain( $token );
        if ( ! $row ) return null;


        // enforce expiry
        if ( ! empty( $row->expires_at ) && strtotime( $row->expires_at ) < time() ) {
            WP_Sanctum_Token::revoke_by_id( $row->id );
            return null;
        }


        // update metadata
        WP_Sanctum_Token::update_last_used( $row->id );


        // store abilities for request
        $abilities = $row->abilities ? json_decode( $row->abilities, true ) : array( '*' );
        $GLOBALS['wp_sanctum_current_token_abilities'] = $abilities;


        return (int) $row->user_id;
    }


    private function get_bearer_token() {
        $header = null;
        if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) $header = trim( $_SERVER['HTTP_AUTHORIZATION'] );
        elseif ( function_exists('apache_request_headers') ) {
            $headers = apache_request_headers();
            if ( isset( $headers['Authorization'] ) ) $header = trim( $headers['Authorization'] );
        }


        if ( $header && preg_match( '/Bearer\s+(.*)$/i', $header, $matches ) ) return $matches[1];
        return null;
    }


    public static function token_has_ability( $ability ) {
        if ( empty( $GLOBALS['wp_sanctum_current_token_abilities'] ) ) return false;
        $abilities = $GLOBALS['wp_sanctum_current_token_abilities'];
        if ( in_array( '*', $abilities, true ) ) return true;
        return in_array( $ability, $abilities, true );
    }
}