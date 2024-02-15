<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Perfex SAAS
Description: Simple comprehensive module to convert Perfex CRM to SAAS, multi-tenancy or multi-company
Version: 0.1.2
Requires at least: 3.0.*
Author: ulutfa
Author URI: https://codecanyon.net/user/ulutfa
*/
defined('PERFEX_SAAS_VERSION_NUMBER') or define('PERFEX_SAAS_VERSION_NUMBER', '0.1.2');

// Global common module constants
require_once('config/constants.php');

$CI = &get_instance();

/**
 * Load models
 */
$CI->load->model(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_model');
$CI->load->model(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_cron_model');

/**
 * Load the module helper
 */
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME);
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_core');
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_setup');
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_usage_limit');


/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(PERFEX_SAAS_MODULE_NAME, [PERFEX_SAAS_MODULE_NAME]);


/**
 * Cron management
 */
register_cron_task('perfex_saas_cron');

hooks()->add_filter('cron_functions_execute_seconds', function ($seconds) {
    // Disable cron lock for tenant. This is neccessary as there is already parent lock by the top saas cron.
    if (perfex_saas_is_tenant()) define('APP_DISABLE_CRON_LOCK', true);
    return $seconds;
});
hooks()->add_filter('used_cron_features', function ($f) {
    $f[] = _l('perfex_saas_cron_feature_migration');
    return $f;
});

/**
 * Listen to any module activation and run the setup again.
 * This ensure new tables are prepared for saas.
 */
hooks()->add_action('module_activated', 'perfex_saas_trigger_module_install');
hooks()->add_action('module_deactivated', 'perfex_saas_trigger_module_install');

/**
 * Register activation module hook
 */
register_activation_hook(PERFEX_SAAS_MODULE_NAME, 'perfex_saas_module_activation_hook');

function perfex_saas_module_activation_hook()
{
    perfex_saas_install();
}

/**
 * Dactivation module hook
 */
register_deactivation_hook(PERFEX_SAAS_MODULE_NAME, 'perfex_saas_module_deactivation_hook');
function perfex_saas_module_deactivation_hook()
{
    perfex_saas_uninstall();
}

/**
 * Register admin footer hook - Common to both admin and instance
 * @todo Separate instance js customization from super admin
 */
hooks()->add_action('app_admin_footer', 'perfex_saas_admin_footer_hook');
function perfex_saas_admin_footer_hook()
{
    //load common admin asset
    $CI = &get_instance();
    $CI->load->view(PERFEX_SAAS_MODULE_NAME . '/includes/scripts');


    //load add user to package modal
    if (!perfex_saas_is_tenant() && $CI->router->fetch_class() == 'invoices')
        $CI->load->view(PERFEX_SAAS_MODULE_NAME . '/includes/add_user_to_package_modal');
}


/**
 * Init module menu items in setup in admin_init hook
 * @return null
 */
hooks()->add_action('admin_init', 'perfex_saas_module_init_menu_items');
function perfex_saas_module_init_menu_items()
{
    $CI = &get_instance();

    if (!perfex_saas_is_tenant() && (has_permission('perfex_saas_company', '', 'view') || has_permission('perfex_saas_package', '', 'view') || has_permission('perfex_saas_settings', '', 'view'))) {

        $CI->app_menu->add_sidebar_menu_item(PERFEX_SAAS_MODULE_NAME, [
            'name' => '<span class="tw-text-white tw-font-bold">' . _l('perfex_saas_menu_title') . '</span>',
            'icon' => 'fa fa-users tw-font-bold',
            'position' => 2,
            'href_attributes' => [
                'class' => 'bg-primary'
            ]
        ]);

        if (has_permission('perfex_saas_packages', '', 'view')) {
            $is_single_package_mode = perfex_saas_is_single_package_mode();
            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_packages',
                'name' => $is_single_package_mode ? _l('perfex_saas_pricing') : _l('perfex_saas_packages'),
                'icon' => 'fa fa-list',
                'href' => $is_single_package_mode ? admin_url('perfex_saas/pricing') : admin_url('perfex_saas/packages'),
                'position' => 2,
            ]);
        }

        if (has_permission('perfex_saas_packages', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_invoices',
                'name' => _l('perfex_saas_invoices'),
                'icon' => 'fa-solid fa-receipt',
                'href' => admin_url('invoices') . '?' . PERFEX_SAAS_FILTER_TAG,
                'position' => 3,
            ]);
        }

        if (has_permission('perfex_saas_company', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_company',
                'name' => _l('perfex_saas_tenants'),
                'icon' => 'fa fa-university',
                'href' => admin_url('perfex_saas/companies'),
                'position' => 2,
            ]);
        }

        if (has_permission('perfex_saas_settings', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_settings',
                'name' => _l('perfex_saas_settings'),
                'icon' => 'fa fa-cog',
                'href' => admin_url('settings?group=perfex_saas'),
                'position' => 9,
            ]);

            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_landingpage',
                'name' => _l('perfex_saas_landingpage_builder_menu'),
                'icon' => 'fa fa-desktop',
                'href' => admin_url('perfex_saas/landingpage/builder'),
                'position' => 10,
            ]);

            // SaaS tab on settings page
            $CI->app_tabs->add_settings_tab(PERFEX_SAAS_MODULE_NAME, [
                'name'     => _l('settings_group_' . PERFEX_SAAS_MODULE_NAME),
                'view'     => 'perfex_saas/settings/index',
                'position' => -5,
                'icon'     => 'fa fa-users',
            ]);
        }
    }

    if (perfex_saas_is_tenant()) {
        // Reserved routes
        $restricted_menus = ['modules'];
        foreach ($restricted_menus as $menu) {
            $CI->app_menu->add_setup_menu_item($menu, ['name' => '', 'href' => '', 'disabled' => true]);
        }
    }
}


/**
 * Common hook to filter dangerous file extension when updating settings
 */
hooks()->add_filter('before_settings_updated', 'perfex_saas_before_settings_updated_common_hook');
function perfex_saas_before_settings_updated_common_hook($data)
{
    if (isset($data['settings']['allowed_files'])) {

        $_exts = explode(',', $data['settings']['allowed_files']);
        if (count($_exts) > 100) throw new \Exception("Ext size too large: Error Processing Request", 1);

        $allowed_files = [];
        foreach ($_exts as $ext) {
            $ext = trim($ext);
            if (str_starts_with($ext, '.') && !in_array($ext, PERFEX_SAAS_DANGEROUS_EXTENSIONS)) {
                $allowed_files[] = $ext;
            }
        }

        $data['settings']['allowed_files'] = implode(',', $allowed_files);
    }

    return $data;
}



/********SAAS CLIENTS AND SUPER ADMIN HOOKS ******/
$is_tenant = perfex_saas_is_tenant();
$is_admin = is_admin();
$is_client = is_client_logged_in();

if (!$is_tenant) {

    // Log a selected plan id whenever we have it. I.e the copied package url
    if (!empty($package_slug = $CI->input->get('ps_plan', true))) {
        $CI->session->set_userdata(['ps_plan' => $package_slug]);
    }

    // Log package, subdomain and custom domain from registration form
    if (!is_client_logged_in()) {

        $company_slug = $CI->input->post('slug', true);
        if (!empty($company_slug)) {
            $CI->session->set_userdata(['ps_slug' => $company_slug]);
        }

        $custom_domain = $CI->input->post('custom_domain', true);
        if (!empty($custom_domain)) {
            $CI->session->set_userdata(['ps_custom_domain' => $custom_domain]);
        }

        // Get package slug from form input to ensure its not lost durring session id change after signup
        $package_slug = $CI->input->post_get('ps_plan', true);
        if (!empty($package_slug)) {
            $CI->session->set_userdata(['ps_plan' => $package_slug]);
        }
    }

    // Add custom domain and subdomain from session if any
    hooks()->add_filter('perfex_saas_create_instance_data', function ($data) use ($CI) {
        $company_slug = $CI->session->ps_slug;
        if (!empty($company_slug) && !isset($data['slug'])) {
            $data['slug'] = $company_slug;
        }

        $custom_domain = $CI->session->ps_custom_domain;
        if (!empty($custom_domain) && !isset($data['custom_domain']) && perfex_saas_is_valid_custom_domain($custom_domain)) {
            $data['custom_domain'] = $custom_domain;
        }

        return $data;
    });

    // Clear the session if present after success creating an instance
    hooks()->add_action('perfex_saas_after_client_create_instance', function ($id) use ($CI) {
        if ($id) {
            foreach (['ps_custom_domain', 'ps_slug', 'ps_plan'] as $key) {
                if ($CI->session->has_userdata($key))
                    $CI->session->unset_userdata($key);
            }
        }
    });



    // Client panel scripts and widgets
    hooks()->add_action('app_customers_head', function () {
        include_once(__DIR__ . '/views/client/scripts.php');
    });

    /******* SUPER CLIENT SPECIFIC HOOKS *********/
    if ($is_client) {

        // Auto subscribe to package
        perfex_saas_autosubscribe();

        /**********TENANT/CLIENT Portal specific HOOKS */
        hooks()->add_action('clients_init', 'perfex_saas_clients_area_menu_items');
        function perfex_saas_clients_area_menu_items()
        {
            if (is_client_logged_in()) {
                add_theme_menu_item('companies', [
                    'name' => '<span class="tw-font-bold">' . _l('perfex_saas_client_menu_companies') . '</span>',
                    'href' => site_url('clients/?companies'),
                    'position' => -2,
                    'href_attributes' => [
                        'class' => 'ps-spa',
                        'data-tab' => "#companies"
                    ]
                ]);
                add_theme_menu_item('subscription', [
                    'name' => _l('perfex_saas_client_menu_subscription'),
                    'href' => perfex_saas_is_single_package_mode() ? site_url('clients/my_account') : site_url('clients/?subscription'),
                    'position' => -1,
                    'href_attributes' => [
                        'class' => 'ps-spa',
                        'data-tab' => "#subscription"
                    ]
                ]);
            }
        }


        hooks()->add_action('client_area_after_project_overview', 'perfex_saas_show_client_home');
        function perfex_saas_show_client_home()
        {
            include_once(__DIR__ . '/views/client/home.php');
        }

        // Remove uneccessary menu item from client portal.
        // @todo Make this configurable from admin
        hooks()->add_filter('theme_menu_items', 'remove_menu_items');
        function remove_menu_items($items)
        {
            if (!perfex_saas_is_tenant() && get_option('perfex_saas_control_client_menu') == '1') {
                unset($items['projects']);
                unset($items['contracts']);
                unset($items['estimates']);
                unset($items['proposals']);
            }
            return $items;
        }
    }

    if ($is_admin || is_staff_member()) {
        /******* SUPER ADMIN PANEL SPECIFIC HOOKS *********/

        if ($is_admin) {
            /**
             * Register permissions
             */
            hooks()->add_action('admin_init', 'perfex_saas_permissions');
            function perfex_saas_permissions()
            {
                $capabilities = [];
                $capabilities['capabilities'] = [
                    'view' => _l('perfex_saas_permission_view'),
                ];
                register_staff_capabilities('perfex_saas_dashboard', $capabilities, _l('perfex_saas') . ' ' . _l('perfex_saas_dashboard'));

                $capabilities = [];
                $capabilities['capabilities'] = [
                    'view' => _l('perfex_saas_permission_view'),
                    'create' => _l('perfex_saas_permission_create'),
                    'edit' => _l('perfex_saas_permission_edit'),
                    'delete' => _l('perfex_saas_permission_delete'),
                ];
                register_staff_capabilities('perfex_saas_companies', $capabilities, _l('perfex_saas') . ' ' . _l('perfex_saas_companies'));

                $capabilities = [];
                $capabilities['capabilities'] = [
                    'view' => _l('perfex_saas_permission_view'),
                    'create' => _l('perfex_saas_permission_create'),
                    'edit' => _l('perfex_saas_permission_edit'),
                    'delete' => _l('perfex_saas_permission_delete'),
                ];
                register_staff_capabilities('perfex_saas_packages', $capabilities, _l('perfex_saas') . ' ' . _l('perfex_saas_packages'));

                $capabilities = [];
                $capabilities['capabilities'] = [
                    'view' => _l('perfex_saas_permission_view'),
                    'edit' => _l('perfex_saas_permission_edit'),
                ];
                register_staff_capabilities('perfex_saas_settings', $capabilities, _l('perfex_saas') . ' ' . _l('perfex_saas_settings'));
            }
        }

        //dashboard
        if (has_permission('perfex_saas_dashboard', '', 'view')) {
            hooks()->add_filter('get_dashboard_widgets', function ($widgets) {

                return array_merge([['path' => PERFEX_SAAS_MODULE_NAME . '/dashboard/overview_widget', 'container' => 'top-12']], $widgets);
            });

            hooks()->add_action('before_start_render_dashboard_content', 'perfex_saas_dashboard_hook');
            function perfex_saas_dashboard_hook()
            {
                get_instance()->load->view(PERFEX_SAAS_MODULE_NAME . '/dashboard/index', []);
            }
        }

        /** Invoice view hooks and filters */
        if (has_permission('perfex_saas_packages', '', 'view')) {
            // Add packageid column to the datatable column and hide
            hooks()->add_filter('invoices_table_columns', 'perfex_saas_invoices_table_columns');
            function perfex_saas_invoices_table_columns($cols)
            {
                $cols[perfex_saas_column('packageid')] = ['name' => perfex_saas_column('packageid'), 'th_attrs' => ['class' => 'not_visible']];
                return $cols;
            }

            // Add packageid to selected invoice fields
            hooks()->add_filter('invoices_table_sql_columns', 'perfex_saas_invoices_table_sql_columns');
            function perfex_saas_invoices_table_sql_columns($fields)
            {
                $fields[] = perfex_saas_column('packageid');
                return $fields;
            }

            // Add package name to recurring bill on invoices list
            hooks()->add_filter('invoices_table_row_data', 'perfex_saas_invoices_table_row_data', 10, 2);
            function perfex_saas_invoices_table_row_data($row, $data)
            {
                $label = _l('perfex_saas_invoice_recurring_indicator');
                $col = perfex_saas_column('packageid');
                if (!empty($data[$col])) {
                    $packageid = $data[$col];
                    $package_name = get_instance()->perfex_saas_model->packages($packageid)->name;
                    $row[0] = str_ireplace($label, $label . ' | ' . $package_name, $row[0]);
                }
                $row[] = '';
                return $row;
            }


            // Add package selection to invoice edit/create
            hooks()->add_action('before_render_invoice_template', 'perfex_saas_after_render_invoice_template_hook');
            function perfex_saas_after_render_invoice_template_hook($invoice)
            {
                $col_name = perfex_saas_column('packageid');
                if (empty($invoice->{$col_name})) return;
                $CI = &get_instance();
                $data = [
                    'packages' => $CI->perfex_saas_model->packages(),
                    'invoice' => $invoice,
                    'col_name' => $col_name,
                    'invoice_packageid' => $invoice->{$col_name}
                ];

                $CI->load->view(PERFEX_SAAS_MODULE_NAME . '/includes/select_package_invoice_template', $data);
            }
        }

        /************Settings */
        // Ensure perfex saas setting is use as default when no settings group is defined
        hooks()->add_action('before_settings_group_view', 'perfex_saas_before_settings_group_view_hook');
        function perfex_saas_before_settings_group_view_hook($tab)
        {

            if (empty(get_instance()->input->get('group'))) { //root settings

                redirect(admin_url('settings?group=' . PERFEX_SAAS_MODULE_NAME));
            }
        }

        // Get modules whitelabeling settings
        hooks()->add_filter('before_settings_updated', 'perfex_saas_before_settings_updated_hook');
        function perfex_saas_before_settings_updated_hook($data)
        {

            $perfex_saas_settings_array_fields = ['perfex_saas_custom_modules_name', 'perfex_saas_tenants_seed_tables', 'perfex_saas_sensitive_options', 'perfex_saas_modules_marketplace'];
            foreach ($perfex_saas_settings_array_fields as $key) {
                if (isset($data['settings'][$key]))
                    $data['settings'][$key] = json_encode($data['settings'][$key]);
            }

            $encrypted_fields = ['perfex_saas_cpanel_password'];
            $CI = &get_instance();
            foreach ($encrypted_fields as $key => $field) {
                if (isset($data['settings'][$field]))
                    $data['settings'][$field] = $CI->encryption->encrypt($data['settings'][$field]);
            }

            return $data;
        }
    }
}


/********OTHER SPECIFIC HOOKS ******/
$folder_path = __DIR__ . '/hooks/';
$feature_hook_files = glob($folder_path . '*.php');
foreach ($feature_hook_files as $file) {
    if (is_file($file)) {
        require_once $file;
    }
}


// Manual run test or cron for development purpose only
if (!empty($CI->input->get(PERFEX_SAAS_MODULE_NAME . '_dev'))) {

    // Only permit this in development mode and user should be logged in as admin.
    $is_developer = ENVIRONMENT === 'development' && !perfex_saas_is_tenant() && $is_admin;
    if (!$is_developer) {
        exit("This action can only be run in development mode");
    }

    $action = $CI->input->get('action');

    if ($action === 'test') {
        include_once(__DIR__ . '/test.php');
    }

    if ($action === 'cron') {
        perfex_saas_cron();
    }
    exit();
}
