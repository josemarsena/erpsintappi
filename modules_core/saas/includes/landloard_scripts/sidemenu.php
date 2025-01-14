<?php

if (is_admin()) {
    /*
     * Add tenants tab in client menu.
     */
    hooks()->add_filter('admin_init', function () {
      
        $CI = &get_instance();

        // Add settings tab
        if (SS_PERFEX_VERSION) {
            $CI->app->add_settings_section_child('other', 'saas', [
                'name'     => _l('saas_superadmin'),
                'view'     => SUPERADMIN_MODULE.'/settings/tenants',
                'position' => 50,
            ]);
        } else {
            $CI->app_tabs->add_settings_tab(SUPERADMIN_MODULE, [
                'name'     => _l('saas_superadmin'),
                'view'     => SUPERADMIN_MODULE.'/settings/tenants',
                'position' => 50,
            ]);
        }

        // Add client profile tab
        $CI->app_tabs->add_customer_profile_tab('tenants', [
            'name'     => _l('saas_tenant'),
            'view'     => SUPERADMIN_MODULE.'/tenants_stats/tenants_stats',
            'position' => 15,
            'icon'     => 'fa fa-building',
        ]);

        $userid = get_instance()->uri->segment(4);
        $client_plan = getClientPlan($userid);

        // Add client profile tab
        if(!empty($client_plan)) {
            $CI->app_tabs->add_customer_profile_tab('tenants_setting', [
              'name'     => _l('saas_tenant_setting'),
              'view'     => SUPERADMIN_MODULE.'/saas_tenants_settings',
              'position' => 15,
              'icon'     => 'fa fa-cogs',
            ]);
        }

        // Add saas side menu
        $CI->app_menu->add_sidebar_menu_item('saas', [
            'slug'     => 'saas_management',
            'name'     => _l('saas_management'),
            'icon'     => 'fa fa-building menu-icon',
            'position' => 30,
        ]);
        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'plans',
          'name'     => _l('plans'),
          'href'     => admin_url('saas/plans'),
          'position' => 1,
        ]);

        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'saas_setting',
          'name'     => _l('saas_superadmin_setting'),
          'href'     => admin_url('settings?group=saas'),
          'position' => 2,
        ]);

        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'saas_activity_log',
          'name'     => _l('saas_activity_log'),
          'href'     => admin_url('saas/saas_log_details'),
          'position' => 3,
        ]);

        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'saas_landing_page_editor',
          'name'     => _l('landing_page_editor'),
          'href'     => admin_url('saas/landing_page_editor'),
          'position' => 4,
        ]);

        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'saas_landing_page_builder',
          'name'     => _l('landing_page_builder'),
          'href'     => admin_url('saas/landing_page_builder/builder'),
          'position' => 5,
        ]);
    });
}

// Add links for client side
hooks()->add_action('clients_init', 'add_saas_client_header_tab');
function add_saas_client_header_tab()
{
    if (is_client_logged_in() && getClientPlan(get_client()->userid)) {
        get_instance()->app_menu->add_theme_item('saas', [
            'slug'     => 'saas',
            'name'     => _l('saas_tenant'),
            'href'     => site_url('saas/saas_tenants'),
            'position' => 5,
        ]);
    }
}

hooks()->add_action('module_deactivated', function($module_name) {
    if (SUPERADMIN_MODULE == $module_name['system_name']) {
        write_file(TEMP_FOLDER . basename(get_instance()->app_modules->get(SUPERADMIN_MODULE)['headers']['uri']) . '.lic', '');
        echo '<script>
            var _saascss = "' . basename(get_instance()->app_modules->get(SUPERADMIN_MODULE)['headers']['uri']) . '.lic"' . ';
            sessionStorage.setItem(_saascss, "");
        </script>';
    }
});
