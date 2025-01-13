<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['/'] = 'saas/pricing/index';
$route['default_controller'] = 'saas/pricing/index';
$route['404_override']         = 'saas/pricing/show_404';

hooks()->add_action('after_contact_login', function () {
    $CI = &get_instance();
    if (!$CI->session->has_userdata('red_url'))
        $CI->session->set_userdata([
            'red_url' => site_url('clients/'),
        ]);
});