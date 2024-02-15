<?php

defined('BASEPATH') or exit('No direct script access allowed');

$client_bridge = $is_tenant ? perfex_saas_tenant_is_enabled('client_bridge') : (int)perfex_saas_get_options('perfex_saas_enable_client_bridge');
if ($client_bridge) {

    if ($is_tenant) {
        // Add account menu for saas management. This is a bridge to the client portal from tenant.
        hooks()->add_action('admin_init', function () use ($CI) {
            if (is_admin()) {

                $CI->app_menu->add_setup_menu_item('my_account', [
                    'collapse' => true,
                    'name'     => _l('perfex_saas_my_account'),
                    'position' => PHP_INT_MAX,
                    'badge'    => [],
                ]);
                $CI->app_menu->add_setup_children_item('my_account', [
                    'slug'     => 'overview',
                    'name'     => _l('perfex_saas_client_menu_overview'),
                    'href'     => admin_url('billing/my_account?redirect=clients/?companies'),
                    'position' => 1,
                    'badge'    => [],
                ]);
                $CI->app_menu->add_setup_children_item('my_account', [
                    'slug'     => 'subscription',
                    'name'     => _l('perfex_saas_client_menu_subscription'),
                    'href'     => admin_url('billing/my_account?redirect=clients/?subscription'),
                    'position' => 2,
                    'badge'    => [],
                ]);
                $CI->app_menu->add_setup_children_item('my_account', [
                    'slug'     => 'invoices',
                    'name'     => _l('clients_nav_invoices'),
                    'href'     => admin_url('billing/my_account?redirect=clients/invoices'),
                    'position' => 3,
                    'badge'    => [],
                ]);
                $CI->app_menu->add_setup_children_item('my_account', [
                    'slug'     => 'tickets',
                    'name'     => _l('clients_nav_support'),
                    'href'     => admin_url('billing/my_account?redirect=clients/tickets'),
                    'position' => 4,
                    'badge'    => [],
                ]);
            }
        });


        hooks()->add_action('app_admin_footer', function () {
            $support_custom_domain_magic_login = perfex_saas_tenant_is_enabled('cross_domain_bridge');
            if (perfex_saas_tenant()->http_identification_type === PERFEX_SAAS_TENANT_MODE_DOMAIN && !$support_custom_domain_magic_login) {
                echo '
                        <script>
                            // Loop through each link and add the target="_blank" attribute
                            const links = document.querySelectorAll(".menu-item-my_account .collapse a");
                            if(links)
                            links.forEach(link => {
                                link.setAttribute("target", "_blank");
                            });
                        </script>
                        ';
            }
        });
    }


    if (!$is_tenant && $is_client) {

        $invoice = $CI->perfex_saas_model->get_company_invoice(get_client_user_id());
        $has_outstanding = !$invoice || !in_array($invoice->status, ["6", "2"]);
        $GLOBALS['has_outstanding'] = $has_outstanding;
        // Disable nav and footer when using magic_auth from one of the tenant instance. i.e client preview
        hooks()->add_action('clients_init', function () use ($has_outstanding) {
            $CI = &get_instance();
            $is_magic_auth = $CI->session->has_userdata('magic_auth');
            if ($is_magic_auth) {
                $cross_domain = $CI->session->userdata('magic_auth')['cross_domain'] ?? false;
                if (!$cross_domain && !$has_outstanding) {
                    $CI->disableNavigation();
                    $CI->disableSubMenu();
                    $CI->disableFooter();
                }
            }
        });


        // Redirect to the primary instance and Auto login is not is saas client and not running in magic auth
        if ($CI->router->fetch_class() !== 'authentication' && !$CI->session->has_userdata('magic_auth') && !$has_outstanding) {
            // Get primary instance
            $primary_active_instance = null;
            $companies = $CI->perfex_saas_model->companies();
            foreach ($companies as $key => $company) {
                if ($company->status === 'active') {
                    $primary_active_instance = $company;
                }
            }

            // Auto signin and redirect
            if ($primary_active_instance) {
                redirect(site_url('clients/ps_magic/' . $primary_active_instance->slug . '/auto'));
                exit;
            }
        }
    }

    // Redirect to saas login after logging out from tenant
    if ($is_tenant) {

        $is_magic_auth = false;
        $CI->load->helper('cookie');

        // Track magic auth session
        if (isset($_GET['_magic_auth_session'])) {
            set_cookie([
                'name'  => 'magic_auth',
                'value' => '1',
                'httponly' => true,
                'expire' => 0
            ]);

            // Remove the signature from the url and redirect
            $current_url = $_SERVER['REQUEST_URI'];
            $url_components = parse_url($current_url);
            parse_str($url_components['query'], $query_params);
            unset($query_params['_magic_auth_session']);
            $new_query_string = http_build_query($query_params);
            $new_url = $url_components['path'];
            if (!empty($new_query_string)) $new_url .= '?' . $new_query_string;
            redirect($new_url);
        }

        // Determine if active use is admin or not be it logs out
        hooks()->add_action('before_staff_logout', function () use (&$is_admin, &$is_magic_auth) {
            $is_admin = is_admin();
            $is_magic_auth = (int)get_cookie('magic_auth') === 1;
        });
        // Redirect to saas client logout page if magic auth and redirect back otherwise.
        // The ensure the saas client session is also terminated.
        hooks()->add_action('after_user_logout', function () use (&$is_admin, &$is_magic_auth) {
            if ($is_admin) {
                $query = $is_magic_auth ? '' : '?sso_redirect=' . admin_url();
                delete_cookie('magic_auth');
                delete_cookie('autologin');
                redirect(APP_BASE_URL_DEFAULT . 'authentication/logout' . $query, []);
            }
        });
    } else {

        // Remove any magic auth session after every standard proper login
        hooks()->add_action('after_contact_login', function () use ($CI) {
            $CI->session->unset_userdata('magic_auth');
        });

        // Handle redirection back to instance admin after logging out from saas client portal
        hooks()->add_action('after_client_logout', function () use ($CI) {
            $redirect = $CI->input->get('sso_redirect');
            $CI->session->unset_userdata('magic_auth');
            if (!empty($redirect)) {
                redirect($redirect);
            }
        });
    }
}
