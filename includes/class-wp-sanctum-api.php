<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class WP_Sanctum_API {
    /**
     * Register REST API routes.
     */
    public function register_routes() {
        register_rest_route('wp-sanctum/v1', '/login', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_login'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wp-sanctum/v1', '/mobile-login', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_mobile_login'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wp-sanctum/v1', '/logout', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_logout'],
            'permission_callback' => [$this, 'auth_middleware'],
        ]);

        register_rest_route('wp-sanctum/v1', '/user', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user'],
            'permission_callback' => [$this, 'auth_middleware'],
        ]);
    }

    /**
     * Handle standard login (returns JWT in response).
     */
    public function handle_login($request) {
        $username = sanitize_text_field($request['username']);
        $password = $request['password'];
        $abilities = isset($request['abilities']) ? array_map('sanitize_text_field', (array)$request['abilities']) : [];

        $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', 'Invalid credentials', ['status' => 401]);
        }

        $token = $this->generate_token($user->ID, null, $abilities);
        return rest_ensure_response([
            'token' => $token,
            'user' => [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
            ],
        ]);
    }

    /**
     * Handle mobile login (redirects with JWT).
     */
    public function handle_mobile_login($request) {
        $username = sanitize_text_field($request['username']);
        $password = $request['password'];
        $redirect_uri = sanitize_text_field($request['redirect_uri']);
        $abilities = isset($request['abilities']) ? array_map('sanitize_text_field', (array)$request['abilities']) : [];

        if (!$redirect_uri || !wp_sanctum_validate_redirect_uri($redirect_uri)) {
            return new WP_Error('invalid_redirect_uri', 'Invalid redirect URI', ['status' => 400]);
        }

        $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', 'Invalid credentials', ['status' => 401]);
        }

        $token = $this->generate_token($user->ID, $redirect_uri, $abilities);
        $redirect_url = add_query_arg('token', $token, $redirect_uri);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handle logout (deletes token).
     */
    public function handle_logout($request) {
        $token = $this->get_token_from_request($request);
        if ($token) {
            global $wpdb;
            $wpdb->delete($wpdb->prefix . 'sanctum_tokens', ['token' => hash('sha256', $token)]);
            return rest_ensure_response(['message' => 'Logged out']);
        }
        return new WP_Error('invalid_token', 'Invalid token', ['status' => 401]);
    }

    /**
     * Get authenticated user data.
     */
    public function get_user($request) {
        $user = wp_get_current_user();
        if ($user->ID === 0) {
            return new WP_Error('unauthorized', 'Unauthorized', ['status' => 401]);
        }
        return rest_ensure_response([
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
        ]);
    }

    /**
     * Authentication middleware for JWT or session-based validation.
     */
    public function auth_middleware($request) {
        // Check for token-based authentication
        $token = $this->get_token_from_request($request);
        if ($token) {
            try {
                $secret_key = get_option('wp_sanctum_jwt_secret');
                $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

                global $wpdb;
                $table_name = $wpdb->prefix . 'sanctum_tokens';
                $token_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE token = %s AND user_id = %d",
                    hash('sha256', $token),
                    $decoded->sub
                ));

                if ($token_data && (!$token_data->expires_at || current_time('mysql') <= $token_data->expires_at)) {
                    wp_set_current_user($token_data->user_id);
                    return true;
                }
            } catch (Exception $e) {
                return new WP_Error('invalid_token', 'Invalid or expired token: ' . $e->getMessage(), ['status' => 401]);
            }
        }

        // Fallback to session-based authentication
        if (wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            $user = wp_get_current_user();
            if ($user->ID !== 0) {
                return true;
            }
        }

        return new WP_Error('unauthorized', 'Unauthorized', ['status' => 401]);
    }

    /**
     * Generate a JWT.
     */
    public function generate_token($user_id, $redirect_uri = null, $abilities = []) {
        $secret_key = get_option('wp_sanctum_jwt_secret');
        $expiration = get_option('wp_sanctum_token_expiration', 7 * DAY_IN_SECONDS);
        $issued_at = time();
        $expires_at = $expiration ? $issued_at + $expiration : null;

        $payload = [
            'iss' => get_site_url(),
            'sub' => $user_id,
            'iat' => $issued_at,
            'exp' => $expires_at,
            'abilities' => $abilities,
        ];

        $token = JWT::encode($payload, $secret_key, 'HS256');

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'sanctum_tokens', [
            'user_id' => $user_id,
            'token' => hash('sha256', $token), // Store hashed token for security
            'redirect_uri' => $redirect_uri,
            'abilities' => !empty($abilities) ? implode(',', $abilities) : null,
            'created_at' => current_time('mysql'),
            'expires_at' => $expires_at ? date('Y-m-d H:i:s', $expires_at) : null,
        ]);

        return $token;
    }

    /**
     * Extract token from Authorization header.
     */
    public function get_token_from_request($request) {
        $auth_header = $request->get_header('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
?>