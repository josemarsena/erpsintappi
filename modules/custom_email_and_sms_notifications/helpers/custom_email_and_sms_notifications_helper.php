<?php

defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_action('admin_init', 'custom_email_and_sms_menuitem');

function custom_email_and_sms_menuitem()
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('custom-email-and-sms', [
            'slug'     => 'main-menu-options',
            'name'     => 'Custom Email & SMS',
            'href'     => admin_url('custom_email_and_sms_notifications/email_sms/email_or_sms'),
            'position' => 65,
            'icon'     => 'fa fa-envelope'
    ]);
}