<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * This file is responsible for handling the perfex mobile app api deactivation
 */
$CI = &get_instance();
delete_option(PERFEX_MOBILE_APP_API . '_enabled');

if (table_exists(db_prefix() . 'api_logs'))
    $CI->db->query('DROP table ' . db_prefix() . 'api_logs;');

removeExceptionForCSRF();

function removeExceptionForCSRF()
{

}
