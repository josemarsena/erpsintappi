<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name:API for Synglia
Description: Uma API para SyngliaCRM adaptado
Version: 1.0.0
Author: Divesh Ahuja modificado por JRS
Author URI:
Requires at least: 2.3.2
*/

const SYNGLIA_APP_API = 'synglia_app_api';

/*
    Defined required table names
*/
define('TABLE_API_LOGS', db_prefix() . 'api_logs');


$CI = &get_instance();

/**
 * Register the activation for module
 */
register_activation_hook(SYNGLIA_APP_API, 'synglia_api_activation_hook');
register_deactivation_hook(SYNGLIA_APP_API, 'synglia_api_deactivation_hook');
register_uninstall_hook(SYNGLIA_APP_API, 'synglia_api_deactivation_hook');
/**
 * The activation function
 */
function synglia_api_activation_hook()
{
    require(__DIR__ . '/install.php');
}

function synglia_api_deactivation_hook()
{
    require(__DIR__ . '/uninstall.php');
}

/**
 * Register SYNGLIA_APP_API language files
 */
register_language_files(SYNGLIA_APP_API, ['synglia_app_api']);

/**
 * Load the SYNGLIA_APP_API helper
 */
$CI->load->helper(SYNGLIA_APP_API . "/synglia_app_api");
