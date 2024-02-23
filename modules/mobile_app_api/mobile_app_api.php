<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Perfex mobile app api
Description: An API module for perfex mobile app
Version: 1.0.0
Author: Divesh Ahuja
Author URI: https://codecanyon.net/item/perfex-android-app-lead-management-app/41921554
Requires at least: 2.3.2
*/

const PERFEX_MOBILE_APP_API = 'perfex_mobile_app_api';

/*
    Defined required table names
*/
define('TABLE_API_LOGS', db_prefix() . 'api_logs');


$CI = &get_instance();

/**
 * Register the activation for module
 */
register_activation_hook(PERFEX_MOBILE_APP_API, 'perfex_mobile_api_activation_hook');
register_deactivation_hook(PERFEX_MOBILE_APP_API, 'perfex_mobile_api_deactivation_hook');
register_uninstall_hook(PERFEX_MOBILE_APP_API, 'perfex_mobile_api_deactivation_hook');
/**
 * The activation function
 */
function perfex_mobile_api_activation_hook()
{
    require(__DIR__ . '/install.php');
}

function perfex_mobile_api_deactivation_hook()
{
    require(__DIR__ . '/uninstall.php');
}

/**
 * Register PERFEX_MOBILE_APP_API language files
 */
register_language_files(PERFEX_MOBILE_APP_API, ['perfex_mobile_app_api']);

/**
 * Load the PERFEX_MOBILE_APP_API helper
 */
$CI->load->helper(PERFEX_MOBILE_APP_API . "/perfex_mobile_app_api");
