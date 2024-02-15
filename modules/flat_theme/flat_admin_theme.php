<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Tema Admin Plano
Description: Estética Plana para o BackEnd I3Flex
Version: 1.0
Author: Themesic Interactive
Author URI: https://themesic.com
Requires at least: 2.3.2
*/

define('FLAT_ADMIN_THEME_MODULE_NAME', 'flat_admin_theme');
define('FLAT_ADMIN_THEME_CSS', module_dir_path(FLAT_ADMIN_THEME_MODULE_NAME, 'assets/css/main-style.css'));

$CI = &get_instance();

/**
 * Register the activation chat
 */
register_activation_hook(FLAT_ADMIN_THEME_MODULE_NAME, 'flat_admin_theme_activation_hook');

/**
 * The activation function
 */
function flat_admin_theme_activation_hook()
{
	require(__DIR__ . '/install.php');
}

/**
 * Register chat language files
 */
register_language_files(FLAT_ADMIN_THEME_MODULE_NAME, ['flat_admin_theme']);

/**
 * Load the chat helper
 */
$CI->load->helper(FLAT_ADMIN_THEME_MODULE_NAME . '/flat_admin_theme');
