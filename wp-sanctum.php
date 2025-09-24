<?php
/**
 * Plugin Name: WP Sanctum
 * Description: Laravel Sanctum–style token authentication for WordPress (modular).
 * Version: 1.0.0
 * Author: Mohammad Anwar
 * License: GPL2
 */


if (!defined('ABSPATH')) exit;


define('WP_SANCTUM_VERSION', '1.0.0');
define('WP_SANCTUM_PATH', plugin_dir_path(__FILE__));
define('WP_SANCTUM_URL', plugin_dir_url(__FILE__));


// Includes
require_once WP_SANCTUM_PATH . 'includes/helpers.php';
require_once WP_SANCTUM_PATH . 'includes/class-activator.php';
require_once WP_SANCTUM_PATH . 'includes/class-deactivator.php';
require_once WP_SANCTUM_PATH . 'includes/class-token.php';
require_once WP_SANCTUM_PATH . 'includes/class-auth.php';
require_once WP_SANCTUM_PATH . 'includes/class-rest.php';
require_once WP_SANCTUM_PATH . 'includes/class-app-login.php';
require_once WP_SANCTUM_PATH . 'includes/class-admin.php';
require_once WP_SANCTUM_PATH . 'includes/class-spa.php';
require_once WP_SANCTUM_PATH . 'includes/class-abilities.php';


// Activation hooks
register_activation_hook(__FILE__, array('WP_Sanctum_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('WP_Sanctum_Deactivator', 'deactivate'));


// Initialize
add_action('plugins_loaded', function () {
    // instantiate core components
    new WP_Sanctum_App_Login();
    new WP_Sanctum_Auth();
    new WP_Sanctum_REST();
    new WP_Sanctum_Admin();
    new WP_Sanctum_SPA();
});