<?php
if (!defined('ABSPATH')) exit;


class WP_Sanctum_Abilities
{
    /**
     * Register a REST route with ability enforcement.
     *
     * @param string $namespace
     * @param string $route
     * @param array $args
     * @param array $abilities
     */
    public static function register_route($namespace, $route, $args, $abilities = ['*'])
    {
        $original_permission = $args['permission_callback'] ?? '__return_true';

        $args['permission_callback'] = function ($request) use ($original_permission, $abilities) {
            // check login/auth first
            $auth = new WP_Sanctum_Auth();
            if (!$auth->check()) return false;


            // check original permission
            if (is_callable($original_permission) && !call_user_func($original_permission, $request)) return false;


            // check token abilities
            foreach ($abilities as $ability) {
                if (WP_Sanctum_Auth::token_has_ability($ability)) return true;
            }
            return false;
        };


        register_rest_route($namespace, $route, $args);
    }


    /**
     * Convenience wrapper to register GET route with ability.
     */
    public static function get($namespace, $route, $callback, $abilities = ['*'])
    {
        self::register_route($namespace, $route, ['methods' => 'GET', 'callback' => $callback], $abilities);
    }


    /**
     * Convenience wrapper to register POST route with ability.
     */
    public static function post($namespace, $route, $callback, $abilities = ['*'])
    {
        self::register_route($namespace, $route, ['methods' => 'POST', 'callback' => $callback], $abilities);
    }
}