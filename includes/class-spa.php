<?php
class WP_Sanctum_SPA {


    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_spa_routes' ] );
    }


    public function register_spa_routes() {
        register_rest_route( 'wp-sanctum/v1', '/spa-login', [
            'methods' => 'POST',
            'callback' => [ $this, 'spa_login' ],
            'permission_callback' => '__return_true',
        ]);


        register_rest_route( 'wp-sanctum/v1', '/spa-logout', [
            'methods' => 'POST',
            'callback' => [ $this, 'spa_logout' ],
            'permission_callback' => '__return_true',
        ]);
    }


    public function spa_login( WP_REST_Request $request ) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');


        $user = wp_authenticate( $username, $password );
        if ( is_wp_error( $user ) ) {
            return new WP_Error( 'invalid_credentials', 'Invalid login details.', [ 'status' => 401 ] );
        }


        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );


        $csrf_token = bin2hex(random_bytes(16));
        setcookie( 'XSRF-TOKEN', $csrf_token, time() + 3600, '/', COOKIE_DOMAIN, is_ssl(), false );


        return [ 'message' => 'SPA login successful', 'csrf_token' => $csrf_token ];
    }


    public function spa_logout( WP_REST_Request $request ) {
        wp_logout();
        setcookie( 'XSRF-TOKEN', '', time() - 3600, '/', COOKIE_DOMAIN );
        return [ 'message' => 'SPA logout successful' ];
    }
}