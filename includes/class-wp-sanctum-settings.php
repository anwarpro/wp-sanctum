<?php
class WP_Sanctum_Settings {
    /**
     * Initialize settings page.
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add settings page under Settings menu.
     */
    public function add_settings_page() {
        add_options_page(
            'WP Sanctum Settings',
            'WP Sanctum',
            'manage_options',
            'wp-sanctum-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings fields.
     */
    public function register_settings() {
        register_setting('wp_sanctum_settings_group', 'wp_sanctum_allowed_redirect_uris', [
            'sanitize_callback' => [$this, 'sanitize_textarea'],
        ]);
        register_setting('wp_sanctum_settings_group', 'wp_sanctum_token_expiration', [
            'sanitize_callback' => 'intval',
        ]);
        register_setting('wp_sanctum_settings_group', 'wp_sanctum_jwt_secret', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_section(
            'wp_sanctum_main_section',
            'WP Sanctum Configuration',
            null,
            'wp-sanctum-settings'
        );

        add_settings_field(
            'wp_sanctum_allowed_redirect_uris',
            'Allowed Redirect URIs',
            [$this, 'render_redirect_uris_field'],
            'wp-sanctum-settings',
            'wp_sanctum_main_section'
        );

        add_settings_field(
            'wp_sanctum_token_expiration',
            'Token Expiration (days)',
            [$this, 'render_token_expiration_field'],
            'wp-sanctum-settings',
            'wp_sanctum_main_section'
        );

        add_settings_field(
            'wp_sanctum_jwt_secret',
            'JWT Secret Key',
            [$this, 'render_jwt_secret_field'],
            'wp-sanctum-settings',
            'wp_sanctum_main_section'
        );
    }

    /**
     * Sanitize textarea input for redirect URIs.
     */
    public function sanitize_textarea($input) {
        error_log('WP Sanctum: Raw redirect URIs input: ' . print_r($input, true));
        $lines = explode("\n", trim($input));
        $sanitized = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line && preg_match('/^[a-zA-Z0-9]+:\/\//', $line)) {
                $sanitized[] = sanitize_text_field($line);
            }
        }
        error_log('WP Sanctum: Sanitized redirect URIs: ' . print_r($sanitized, true));
        return implode("\n", array_filter($sanitized));
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>WP Sanctum Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_sanctum_settings_group');
                do_settings_sections('wp-sanctum-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render redirect URIs field.
     */
    public function render_redirect_uris_field() {
        $value = get_option('wp_sanctum_allowed_redirect_uris', '');
        ?>
        <textarea name="wp_sanctum_allowed_redirect_uris" rows="5" cols="50"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Enter allowed redirect URIs (one per line, e.g., app://login, https://example.com/callback). Leave empty to allow any valid URI with a scheme (e.g., app://, https://).</p>
        <?php
    }

    /**
     * Render token expiration field.
     */
    public function render_token_expiration_field() {
        $value = get_option('wp_sanctum_token_expiration', 7);
        ?>
        <input type="number" name="wp_sanctum_token_expiration" value="<?php echo esc_attr($value); ?>" min="0">
        <p class="description">Set token expiration in days (0 for no expiration).</p>
        <?php
    }

    /**
     * Render JWT secret key field.
     */
    public function render_jwt_secret_field() {
        $value = get_option('wp_sanctum_jwt_secret', '');
        ?>
        <input type="text" name="wp_sanctum_jwt_secret" value="<?php echo esc_attr($value); ?>" size="50">
        <p class="description">Enter a secure JWT secret key (at least 32 characters). Generated automatically on plugin activation if empty.</p>
        <?php
    }
}
?>