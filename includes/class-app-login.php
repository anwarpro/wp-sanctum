<?php
if (!defined('ABSPATH')) exit;

class WP_Sanctum_App_Login
{

    private $option_name = 'wp_sanctum_app_login_enabled';
    private $redirect_option = 'wp_sanctum_redirect_uris';

    public function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);

        /**
         * Add hidden fields to wp-login.php form.
         */
        add_action('login_form', function () {
            if (isset($_GET['redirect_uri'])) {
//                error_log('WP Sanctum: Adding hidden field redirect_uri: ' . $_GET['redirect_uri'], 3, 'php://stderr');
                echo '<input type="hidden" name="redirect_uri" value="' . esc_attr($_GET['redirect_uri']) . '">';
            }
            if (isset($_GET['abilities'])) {
                echo '<input type="hidden" name="abilities" value="' . esc_attr($_GET['abilities']) . '">';
            }
        });

        add_filter('wp_authenticate_user', [$this, 'maybe_redirect_after_auth'], 20, 2);
    }

    public function register_settings()
    {
        // Register options
        register_setting('wp_sanctum_settings', $this->option_name);
        register_setting('wp_sanctum_settings', $this->redirect_option);

        // Add a section to hold App Login settings
        add_settings_section(
            'wp_sanctum_app_login_section', // Section ID
            'App Login Settings',           // Title displayed
            function () {
                echo '<p>Configure App Login Redirect feature.</p>';
            },
            'wp-sanctum-app-login'          // Page slug
        );

        // Add Enable checkbox
        add_settings_field(
            $this->option_name,
            'Enable App Login Redirect',
            function () {
                $enabled = get_option($this->option_name, 0);
                echo '<input type="checkbox" name="' . $this->option_name . '" value="1" ' . checked(1, $enabled, false) . ' />';
            },
            'wp-sanctum-app-login',        // Page slug
            'wp_sanctum_app_login_section' // Section ID
        );

        // Add Allowed Redirect URIs textarea
        add_settings_field(
            $this->redirect_option,
            'Allowed Redirect URIs',
            function () {
                $uris = get_option($this->redirect_option, '');
                echo '<textarea name="' . $this->redirect_option . '" rows="5" cols="50">' . esc_textarea($uris) . '</textarea>';
            },
            'wp-sanctum-app-login',
            'wp_sanctum_app_login_section'
        );
    }

    public function maybe_redirect_after_auth($user, $password)
    {
        // Only run if App Login redirect is enabled
        if (!get_option($this->option_name, 0)) return $user;


        if (empty($_POST['redirect_uri'])) return $user;

        $redirect_uri = sanitize_text_field($_POST['redirect_uri']);
        $allowed_uris = array_map('trim', explode("\n", get_option($this->redirect_option, '')));

        if (in_array($redirect_uri, $allowed_uris)) {
            $token = WP_Sanctum_Token::create($user->ID);

            // Redirect safely and exit
            $redirect_url = add_query_arg('token', $token, $redirect_uri);
            wp_redirect($redirect_url);
            exit;
        }
    }
}