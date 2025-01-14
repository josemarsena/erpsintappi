<?php

/*
 * Inject css file for superadmin module
*/
hooks()->add_action('app_admin_head', 'superadmin_add_head_components');
function superadmin_add_head_components()
{
    // check module is enable or not (refer install.php)
    if ('1' == get_option('superadmin_enabled')) {
        $CI = &get_instance();
        echo '<link href="'.module_dir_url(SUPERADMIN_MODULE, 'assets/css/superadmin.css').'?v='.$CI->app_scripts->core_version().'"  rel="stylesheet" type="text/css" />';
        $saasOptions = get_saas_activated_domain_list();
        echo '<script>
            var saas_r = ' . json_encode(base_url() . 'temp/'. basename(get_instance()->app_modules->get(SUPERADMIN_MODULE)['headers']['uri'])) . ';
            var saas_g = ' . json_encode($saasOptions['sub_domain'] ?? '') .';  
            var saas_b = ' . json_encode($saasOptions['main_domain'] ?? '') . ';
            var saas_a = ' . json_encode($saasOptions['superadmin']) . ';
        </script>';
    }
}

/*
 * Inject Javascript file for superadmin module
*/
hooks()->add_action('app_admin_footer', 'tenants_load_js');
function tenants_load_js()
{
    if ('1' == get_option('superadmin_enabled')) {
        $CI = &get_instance();
        echo '<script src="'.module_dir_url(SUPERADMIN_MODULE, 'assets/js/saas.bundle.js').'?v='.$CI->app_scripts->core_version().'"></script>';
    }
}
