<?php

class WP_Sanctum_Admin
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }


    public function add_menu()
    {
        add_menu_page(
            'WP Sanctum',
            'WP Sanctum',
            'manage_options',
            'wp-sanctum',
            [$this, 'render_page'],
            'dashicons-shield-alt'
        );
    }


    public function enqueue_assets($hook)
    {
        if ($hook !== 'toplevel_page_wp-sanctum') return;
        wp_enqueue_style('wp-sanctum-admin', WP_SANCTUM_URL . 'public/css/admin.css');
        wp_enqueue_script('wp-sanctum-admin', WP_SANCTUM_URL . 'public/js/admin.js', ['jquery'], null, true);
        wp_localize_script('wp-sanctum-admin', 'wpsanctum', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sanctum_admin')
        ]);
    }

    public function render_page()
    {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'tokens';
        echo '<div class="wrap">';
        echo '<h1>WP Sanctum</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=wp-sanctum&tab=tokens" class="nav-tab ' . ($active_tab === 'tokens' ? 'nav-tab-active' : '') . '">Token Management</a>';
        echo '<a href="?page=wp-sanctum&tab=app_login" class="nav-tab ' . ($active_tab === 'app_login' ? 'nav-tab-active' : '') . '">App Login Settings</a>';
        echo '</h2>';

        if ($active_tab === 'tokens') {
            $this->render_tokens_tab();
        } else {
            $this->render_app_login_tab();
        }

        echo '</div>';
    }

    public function render_tokens_tab()
    {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $tokens = $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>WP Sanctum — Token Management</h1>
            <?php if (isset($_GET['revoked'])): ?>
                <div class="notice notice-success">Token revoked.</div>
            <?php endif; ?>
            <?php if (isset($_GET['revoked_all'])): ?>
                <div class="notice notice-success">All tokens for user revoked.</div>
            <?php endif; ?>

            <table class="widefat fixed striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Abilities</th>
                    <th>Created</th>
                    <th>Expires</th>
                    <th>Last Used</th>
                    <th>Last IP</th>
                    <th>UA</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($tokens): ?>
                    <?php foreach ($tokens as $t):
                        $user = get_userdata($t->user_id);
                        ?>
                        <tr>
                            <td><?php echo esc_html($t->id); ?></td>
                            <td><?php echo esc_html($user ? $user->user_login : 'Deleted'); ?></td>
                            <td><?php echo esc_html($t->abilities); ?></td>
                            <td><?php echo esc_html($t->created_at); ?></td>
                            <td><?php echo esc_html($t->expires_at ?: '—'); ?></td>
                            <td><?php echo esc_html($t->last_used_at ?: '—'); ?></td>
                            <td><?php echo esc_html($t->last_ip ?: '—'); ?></td>
                            <td><?php echo esc_html(isset($t->user_agent) ? wp_trim_words($t->user_agent, 10, '…') : '—'); ?></td>
                            <td style="white-space:nowrap;">
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                      style="display:inline;">
                                    <?php wp_nonce_field('wp_sanctum_revoke_' . $t->id); ?>
                                    <input type="hidden" name="action" value="wp_sanctum_revoke">
                                    <input type="hidden" name="token_id" value="<?php echo esc_attr($t->id); ?>">
                                    <button type="submit" class="button">Revoke</button>
                                </form>
                                <?php if ($user): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                          style="display:inline;margin-left:6px;">
                                        <?php wp_nonce_field('wp_sanctum_revoke_all_' . $user->ID); ?>
                                        <input type="hidden" name="action" value="wp_sanctum_revoke_all">
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                                        <button type="submit" class="button">Revoke All</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">No tokens found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_app_login_tab()
    {
        echo '<form method="post" action="options.php">';
        settings_fields('wp_sanctum_settings');
        do_settings_sections('wp-sanctum-app-login'); // Must match page slug in add_settings_section()
        submit_button();
        echo '</form>';
    }
}