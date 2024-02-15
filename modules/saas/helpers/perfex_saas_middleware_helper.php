<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tenant Middleware function to handle tenant-related checks.
 *
 * This function performs various checks and validations for the tenant.
 * It checks if the tenant has an unpaid invoice, if the tenant is active,
 * if the requested module is allowed for the tenant, and if the requested controller
 * is restricted for the tenant. It also handles restricted routes in the settings controller.
 *
 * @throws Exception Throws an exception if an error occurs or if access is denied.
 */
function perfex_saas_tenant_middleware()
{
    if (perfex_saas_is_tenant()) {
        $tenant = perfex_saas_tenant();
        if ($tenant) {
            $invoice = isset($tenant->package_invoice) ? $tenant->package_invoice : null;

            // Check for an unpaid invoice
            if ($invoice && !$invoice->is_private && !in_array($invoice->status, ["6", "2"])) {
                $payment_url = APP_BASE_URL_DEFAULT . "invoice/$invoice->id/$invoice->hash";
                if (isset($_GET['paying_outstanding'])) {
                    header("Location: $payment_url");
                    exit;
                }
                perfex_saas_show_tenant_error(_l('perfex_saas_clear_unpaid_invoice_mid'), _l('perfex_saas_clear_unpaid_invoice_message_mid') . '<br/>' . '<a class="tw-my-5 text-white bg-red-600 py-2 px-2 my-2 inline-block rounded-lg" href="' . $payment_url . '">' . _l('perfex_saas_clear_invoice_btn') . '</a>', 400);
            }

            $on_trial = isset($invoice) && $invoice->status == "6";
            if ($on_trial) {
                if ((int)perfex_saas_get_days_until($invoice->duedate) <= 0) {
                    perfex_saas_show_tenant_error(_l('perfex_saas_trial_invoice_over_not_mid'), _l('perfex_saas_trial_invoice_over_not_mid_body', [$invoice->name]) . '<br/>' . '<a class="tw-my-5 text-white bg-red-600 py-2 px-2 my-2 inline-block rounded-lg" href="' . APP_BASE_URL_DEFAULT . '/clients/?subscription">' . _l('perfex_saas_click_here_to_subscribe') . '</a>', 403);
                }
            }

            // Check if the tenant is active
            if ($tenant->status != 'active') {
                perfex_saas_show_tenant_error(ucfirst(_l('perfex_saas' . $tenant->status)), _l('perfex_saas_company_not_active_mid') . ' <a class="text-blue-600 text-bold tw-font-bold" href="' . APP_BASE_URL_DEFAULT . 'clients/tickets">' . _l('perfex_saas_clich_here') . '</a>', 400);
            }

            // Get the current CodeIgniter instance
            $ci = &get_instance();

            // Get the list of modules allowed for the tenant
            $modules = perfex_saas_tenant_modules($tenant, false);

            // Get the active module and controller
            $activeModule = $ci->router->fetch_module();
            $controller = $ci->router->fetch_class();
            $method = $ci->router->fetch_method();

            // Check if the controller is 'settings'
            if ($controller === 'settings') {
                // Disable route for update|info from tenant setting
                if (in_array($ci->input->get('group'), ['update', 'info'])) {
                    perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied_mid'), _l('perfex_saas_restricted_settings_group_mid'));
                }
            }

            // Check if the active module is allowed for the tenant
            $exempted = ($activeModule === PERFEX_SAAS_MODULE_NAME &&
                (
                    ($controller === 'api' && $method = 'update_tenant_storage_size') ||
                    ($controller === 'companies' && $method === 'client_portal_bridge' && (perfex_saas_tenant_is_enabled('client_bridge') || perfex_saas_tenant_is_enabled('instance_switch'))) ||
                    ($controller === 'authentication' && $method === 'tenant_admin_magic_auth')
                )
            );

            if ($activeModule && !in_array($activeModule, $modules) && !$exempted) {
                perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied_mid'), _l('perfex_saas_restricted_module_mid'));
            }

            // Check if the controller is restricted
            $restricted_classes = ['mods'];
            if (in_array($controller, $restricted_classes)) {
                $ci->session->set_flashdata('message-danger', _l('perfex_saas_permission_denied_mid'));
                perfex_saas_redirect_back();
                exit();
            }

            // Check if the default module is allowed for the tenant
            $disabled_default_modules = perfex_saas_tenant_disabled_default_modules($tenant);
            if ($controller === 'reports' && $method === 'knowledge_base_articles') {
                $method = 'knowledge_base';
            }
            if (in_array($controller, $disabled_default_modules) || ($controller === 'clients' && in_array($method, $disabled_default_modules)) || ($controller === 'reports' && in_array($method, $disabled_default_modules))) {
                perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied_mid'), _l('perfex_saas_restricted_module_mid'));
            }
        }
    }
}

/**
 * Function to dynamically load tenant-specific modules.
 *
 * This function loads the modules that are allowed for the tenant.
 * It iterates through the tenant modules and includes the module PHP file
 * if it exists. This allows for dynamic loading of tenant-specific modules.
 * @deprecated 0.1.0 In favour of standard module activation for tenant through cron job. Investivage
 */
function perfex_saas_load_tenant_modules()
{
    // Get the current tenant
    $tenant = perfex_saas_tenant();

    if ($tenant && !empty($tenant->slug)) {
        // Get the list of modules allowed for the tenant
        $tenant_modules = perfex_saas_tenant_modules($tenant);

        foreach ($tenant_modules as $module) {
            $file = APP_MODULES_PATH . $module . '/' . $module . '.php';

            // Check if the module file exists
            if (file_exists($file)) {
                // Include the module PHP file
                require_once($file);
            }
        }

        try {
            if (stripos($_SERVER['REQUEST_URI'], '/cron') !== false) {
                // @todo Find additional clause to limit the call of the function here i.e query ?safe-mode e.t.c
                perfex_saas_setup_modules_for_tenant();
            }
        } catch (\Throwable $th) {
            log_message("error", $th->getTraceAsString());
        }
    }
}


/**
 * Attach Hooks function to register and attach hooks for specific actions.
 *
 * This function registers hooks for various actions and attaches the corresponding
 * middleware or module loading functions to those hooks.
 */
function perfex_saas_attach_hooks()
{
    // Register hooks for middleware
    hooks()->add_action('app_init',  'perfex_saas_tenant_middleware');

    // Register hook for module loading
    hooks()->add_action('modules_loaded', 'perfex_saas_load_tenant_modules');
}


/**
 * Perfex SAAS Middleware function.
 *
 * This function serves as a middleware entry point for Perfex SAAS. It calls the
 * `perfex_saas_attach_hooks()` function to register and attach hooks for various actions.
 */
function perfex_saas_middleware()
{
    perfex_saas_attach_hooks();

    // Ensure db prefix constant defined
    if (perfex_saas_is_tenant() && !defined('APP_DB_PREFIX')) {
        perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied_mid'), "Invalid initialization");
    }
}
