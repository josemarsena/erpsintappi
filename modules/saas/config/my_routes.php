<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once('my_hooks.php');

if (perfex_saas_is_tenant()) {

    $tenant = perfex_saas_tenant();

    $route['api/update_storage_size'] = 'perfex_saas/api/update_tenant_storage_size';
    $route['admin/billing/my_account'] = 'perfex_saas/admin/companies/client_portal_bridge';

    $route['billing/my_account/magic_auth'] = 'perfex_saas/authentication/tenant_admin_magic_auth';

    // Ensure this custom routes is defined if the tenant is identified by request uri segment
    if ($tenant->http_identification_type === PERFEX_SAAS_TENANT_MODE_PATH) {
        $tenant_slug = $tenant->slug;
        $tenant_route_sig = perfex_saas_tenant_url_signature($tenant_slug); //i.e $tenant_route_sig

        // Clone existing static routes with saas id prefix
        foreach ($route as $key => $value) {
            $new_key = perfex_saas_tenant_url_signature($tenant_slug) . "/" . ($key == '/' ? '' : $key);
            $route[$new_key] = $value;
        }

        // Make catch-all static route for all the controllers method and modules using max of 7 levels.
        // Based on latest research perfex v3.4 7 level is more than sufficient (can increase with needs)
        $route["$tenant_route_sig/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)"]   = '$1/$2/$3/$4/$5/$6/$7';
        $route["$tenant_route_sig/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)"]   = '$1/$2/$3/$4/$5/$6';
        $route["$tenant_route_sig/(:any)/(:any)/(:any)/(:any)/(:any)"]   = '$1/$2/$3/$4/$5';
        $route["$tenant_route_sig/(:any)/(:any)/(:any)/(:any)"]   = '$1/$2/$3/$4';
        $route["$tenant_route_sig/(:any)/(:any)/(:any)"]   = '$1/$2/$3';
        $route["$tenant_route_sig/(:any)/(:any)"]   = '$1/$2';
        $route["$tenant_route_sig/(:any)"]   = '$1';
        $route["$tenant_route_sig"]   = 'clients';
    }
}

if (!perfex_saas_is_tenant()) {

    /** Landing page handling */
    $landing_options = perfex_saas_get_options(['perfex_saas_landing_page_theme', 'perfex_saas_landing_page_url']);
    $landing_page_theme = $landing_options['perfex_saas_landing_page_theme'] ?? '';
    $landing_page_url = $landing_options['perfex_saas_landing_page_url'] ?? '';
    if ($landing_page_url || $landing_page_theme) {
        $method = filter_var($landing_page_url, FILTER_VALIDATE_URL) ? 'proxy' : 'index';
        $route['/'] = 'perfex_saas/landing/' . $method;
        $route['default_controller'] = 'perfex_saas/landing/' . $method;
        $route['404_override']         = 'perfex_saas/landing/show_404';

        // ensure the user is redirected to client portal after logging in and not landing page
        hooks()->add_action('after_contact_login', function () {
            $CI = &get_instance();
            if (!$CI->session->has_userdata('red_url'))
                $CI->session->set_userdata([
                    'red_url' => site_url('clients/'),
                ]);
        });
    }
    /** Ends Landing page handling */

    // Admin perefex saas routes i.e pacakages and companies/instances management
    $route['admin/perfex_saas/pricing'] = 'perfex_saas/admin/packages/pricing';
    $route['admin/perfex_saas/(:any)'] = 'perfex_saas/admin/$1';
    $route['admin/perfex_saas/(:any)/(:any)'] = 'perfex_saas/admin/$1/$2';
    $route['admin/perfex_saas/(:any)/(:any)/(:any)'] = 'perfex_saas/admin/$1/$2/$3';
    $route['admin/perfex_saas/(:any)/(:any)/(:any)/(:any)'] = 'perfex_saas/admin/$1/$2/$3/$4';


    // Client routes
    $route['clients/packages/(:any)/select'] = 'perfex_saas/perfex_saas_client/subscribe/$1';
    $route['clients/my_account'] = 'perfex_saas/perfex_saas_client/my_account';
    $route['clients/companies'] = 'perfex_saas/perfex_saas_client/companies';
    $route['clients/companies/(:any)'] = 'perfex_saas/perfex_saas_client/$1';
    $route['clients/companies/(:any)/(:any)'] = 'perfex_saas/perfex_saas_client/$1/$2';

    $route['clients/ps_magic/(:any)'] = 'perfex_saas/authentication/magic_auth/$1';
    $route['clients/ps_magic/(:any)/(:any)'] = 'perfex_saas/authentication/magic_auth/$1/$2';

    $route['billing/my_account/magic_auth'] = 'perfex_saas/authentication/client_magic_auth';
}
