<?php
/*
 * Plugin Name: WP Sanctum
 * Description: A lightweight authentication system for WordPress SPAs and APIs, inspired by Laravel Sanctum.
 * Version: 1.1.2
 * Author: Mohammad Anwar
 * License: MIT
 * Requires PHP: 7.4
 */

/**
 * Load Composer autoloader for firebase/php-jwt.
 */
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

/**
 * Plugin activation: Create the token table.
 */
register_activation_hook(__FILE__, 'wp_sanctum_install');
function wp_sanctum_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sanctum_tokens';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        token varchar(255) NOT NULL,
        abilities text DEFAULT NULL,
        redirect_uri varchar(255) DEFAULT NULL,
        expires_at datetime DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        UNIQUE KEY token (token)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Generate default JWT secret if not set
    if (!get_option('wp_sanctum_jwt_secret')) {
        update_option('wp_sanctum_jwt_secret', bin2hex(random_bytes(32)));
    }
}

/**
 * Register custom login page for browser-based authentication.
 */
add_action('init', 'wp_sanctum_register_login_page');
function wp_sanctum_register_login_page() {
    add_rewrite_rule(
        'sanctum-login/?$',
        'index.php?sanctum_login=1',
        'top'
    );
}

/**
 * Add sanctum_login query variable.
 */
add_filter('query_vars', function ($vars) {
    $vars[] = 'sanctum_login';
    return $vars;
});

/**
 * Handle login page rendering and redirection.
 */
add_action('template_redirect', function () {
    if (get_query_var('sanctum_login')) {
        if (is_user_logged_in()) {
            $redirect_uri = isset($_GET['redirect_uri']) ? sanitize_text_field($_GET['redirect_uri']) : '';
            if (!$redirect_uri || !wp_sanctum_validate_redirect_uri($redirect_uri)) {
                wp_die('Invalid redirect URI');
            }
            $sanctum = new WP_Sanctum_API();
            $abilities = isset($_GET['abilities']) ? array_map('sanitize_text_field', explode(',', $_GET['abilities'])) : [];
            $token = $sanctum->generate_token(get_current_user_id(), $redirect_uri, $abilities);
            wp_redirect(add_query_arg('token', $token, $redirect_uri));
            exit;
        }

        // Display login form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>WP Sanctum Login</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; }
                h1 { text-align: center; }
                form { display: flex; flex-direction: column; gap: 15px; }
                label { font-weight: bold; }
                input { padding: 8px; font-size: 16px; }
                button { padding: 10px; background: #0073aa; color: white; border: none; cursor: pointer; }
                button:hover { background: #005177; }
            </style>
        </head>
        <body>
        <h1>Login</h1>
        <form method="POST" action="<?php echo esc_url(rest_url('wp-sanctum/v1/mobile-login')); ?>">
            <input type="hidden" name="redirect_uri" value="<?php echo esc_attr($_GET['redirect_uri']); ?>">
            <input type="hidden" name="abilities" value="<?php echo esc_attr(isset($_GET['abilities']) ? $_GET['abilities'] : ''); ?>">
            <p>
                <label>Username or Email</label>
                <input type="text" name="username" required>
            </p>
            <p>
                <label>Password</label>
                <input type="password" name="password" required>
            </p>
            <p>
                <button type="submit">Login</button>
            </p>
        </form>
        </body>
        </html>
        <?php
        exit;
    }
});

/**
 * Validate redirect URI against allowed list.
 */
function wp_sanctum_validate_redirect_uri($uri) {
    $allowed_uris = get_option('wp_sanctum_allowed_redirect_uris', '');
    if (empty($allowed_uris)) {
        return preg_match('/^[a-zA-Z0-9]+:\/\//', $uri); // Allow any valid scheme if none specified
    }
    $allowed_array = array_map('trim', explode("\n", $allowed_uris));
    return in_array($uri, $allowed_array, true);
}

/**
 * Load settings and API classes.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-sanctum-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wp-sanctum-api.php';

/**
 * Initialize settings and API.
 */
add_action('init', function () {
    $settings = new WP_Sanctum_Settings();
    $settings->init();
});
add_action('rest_api_init', function () {
    $api = new WP_Sanctum_API();
    $api->register_routes();
});
?>