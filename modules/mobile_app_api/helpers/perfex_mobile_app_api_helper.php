<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Perfex mobile app api
Description: An API module for perfex mobile app
Version: 1.0.0
Author: Divesh Ahuja
Author URI: https://codecanyon.net/item/perfex-android-app-lead-management-app/41921554
Requires at least: 2.3.2
*/

/*
 * Check if can have permissions then apply new tab in settings
 */
if (is_admin() && get_option(PERFEX_MOBILE_APP_API . '_enabled') == '1') {
    hooks()->add_action('admin_init', PERFEX_MOBILE_APP_API . '_settings_tab');
}

function perfex_mobile_app_api_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab(PERFEX_MOBILE_APP_API . '-settings', [
        'name' => _l(PERFEX_MOBILE_APP_API . '_settings_name'),
        'view' => PERFEX_MOBILE_APP_API . '/settings',
        'position' => 37,
        'icon' => 'fa-mobile',
    ]);
}

if (!function_exists('prd')) {
    function prd(...$array)
    {
        echo '<pre>';
        foreach ($array as $data) {
            print_r($data) . "<br/>";;
        }
        die;
    }
}