<?php

class WP_Sanctum_REST
{


    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }


    public function register_routes()
    {
        register_rest_route('wp-sanctum/v1', '/token', [
            'methods' => 'POST',
            'callback' => [$this, 'issue_token'],
            'permission_callback' => '__return_true',
        ]);


        register_rest_route('wp-sanctum/v1', '/user', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user'],
            'permission_callback' => [$this, 'auth_guard'],
        ]);


        register_rest_route('wp-sanctum/v1', '/logout', [
            'methods' => 'POST',
            'callback' => [$this, 'logout'],
            'permission_callback' => [$this, 'auth_guard'],
        ]);


        register_rest_route('wp-sanctum/v1', '/csrf-cookie', [
            'methods' => 'GET',
            'callback' => [$this, 'issue_csrf_cookie'],
            'permission_callback' => '__return_true',
        ]);
    }


    public function issue_token(WP_REST_Request $request)
    {
        $username = $request->get_param('username');
        $password = $request->get_param('password');


        $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', 'Invalid login details.', ['status' => 401]);
        }


        $token = WP_Sanctum_Token::create($user->ID);
        return ['token' => $token];
    }


    public function get_user(WP_REST_Request $request)
    {
        $user = wp_get_current_user();
        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
        ];
    }


    public function logout(WP_REST_Request $request)
    {
        $auth = new WP_Sanctum_Auth();
        $token = $auth->get_bearer_token();
        if ($token) {
            WP_Sanctum_Token::revoke($token);
        }
        return ['message' => 'Logged out successfully.'];
    }


    public function auth_guard()
    {
        $auth = new WP_Sanctum_Auth();
        return $auth->check();
    }


    public function issue_csrf_cookie()
    {
        $token = bin2hex(random_bytes(16));
        setcookie('XSRF-TOKEN', $token, time() + 3600, '/', COOKIE_DOMAIN, is_ssl(), false);
        return ['csrf_token' => $token];
    }
}