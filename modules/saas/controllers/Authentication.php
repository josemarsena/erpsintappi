<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This is a common class for managing magic authentications
 */
class Authentication extends ClientsController
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Method to login into an instance magically from the client dashboard.
     * It create auto login cookie (used by perfex core) and redirect to the company admin address.
     * Perfex pick the cookie and authorized. The cookie is localized to the company address only and inserted into db using the instance context.
     * Also when retrieving the cookie from db, the db_simple_query restrict the selection to the instance.
     *
     * @param string $slug
     * @return void
     */
    public function magic_auth($slug, $urlmode = 'path')
    {
        // Ensure we have an authenticated client
        if (!is_client_logged_in() || perfex_saas_is_tenant()) {
            perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied'), _l('perfex_saas_authentication_required_for_magic_login'), 404);
        }

        $company = $this->perfex_saas_model->get_entity_by_slug('companies', $slug, 'parse_company');
        if (!$company) {
            perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied'), _l('perfex_saas_page_not_found'), 404);
        }

        // Ensure the company belongs to the logged in client
        if ($company->clientid !== get_client_user_id()) {
            perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied'), '');
        }

        $auth_code = generate_magic_auth_code($company->clientid);

        $support_custom_domain_magic_login = get_option('perfex_saas_enable_cross_domain_bridge') == "1";
        $links = perfex_saas_tenant_base_url($company, '', 'all');
        if ($urlmode == 'all' || !isset($links[$urlmode])) {
            $urlmode = 'subdomain';
            if (!empty($links['custom_domain']) && $support_custom_domain_magic_login)
                $urlmode = 'custom_domain';
        }
        $redirect = empty($links[$urlmode]) ? $links['path'] : $links[$urlmode];

        $query = 'billing/my_account/magic_auth?auth_code=' . urlencode($auth_code);
        return redirect($redirect . $query);
    }

    /**
     * Authenticate a tenant admin into the Saas client portal.
     *
     * This method signs in a tenant admin into the Saas client portal, allowing them to access client-specific features from the instance.
     *
     * @return void
     */
    public function client_magic_auth()
    {

        try {
            // Check if the user is a tenant or if the client bridge is not enabled
            if (perfex_saas_is_tenant() || get_option('perfex_saas_enable_client_bridge') !== "1") {
                throw new \Exception(_l('perfex_saas_permission_denied'), 1);
            }

            // Determine the redirect URL or use a default if not provided
            $redirect = $this->input->get('redirect', true) ?? 'clients/my_account';

            // If the client is already logged in with a magic code, redirect
            if (is_client_logged_in() && $this->session->has_userdata('magic_code')) {
                return redirect($redirect);
            }

            // Validate and authorize the magic authentication code
            $clientid = $this->validate_and_authorize_magic_auth_code();
            $contact = perfex_saas_get_primary_contact($clientid);

            if (!$contact) {
                throw new \Exception(_l('perfex_saas_error_finding_primary_contact'), 1);
            }

            // Sign in as a client in Saas (i.e., superclient)
            login_as_client($contact->userid);
            $user_data = [
                'magic_auth'       => [
                    'cross_domain' => (int)$this->input->get('cross_domain', true),
                    'source_url' => $this->input->get('source_url', true)
                ],
            ];
            $this->session->set_userdata($user_data);

            return redirect($redirect);
        } catch (\Throwable $th) {
            perfex_saas_show_tenant_error(_l('perfex_saas_authentication_error'), $th->getMessage());
        }
    }

    /**
     * Auto-magic login into the current tenant instance as an admin using a magic code set from another instance.
     *
     * This method allows switching to another instance from the current one by automatically signing in as a tenant admin.
     *
     * @return void
     */
    public function tenant_admin_magic_auth()
    {
        try {

            // Check if the user is not a tenant or if instance switching is not enabled
            if (!perfex_saas_is_tenant()) {
                throw new \Exception(_l('perfex_saas_permission_denied'), 1);
            }

            // If the user is already an admin, redirect to the admin dashboard
            if (is_admin()) {
                return redirect(admin_url());
            }

            // Validate and authorize the magic authentication code
            $clientid = $this->validate_and_authorize_magic_auth_code();

            // Ensure that the client matches the current tenant instance
            if ((int)perfex_saas_tenant()->clientid !== $clientid) {
                throw new \Exception(_l('perfex_saas_permission_denied'), 1);
            }

            // Sign in as the current tenant instance's admin
            perfex_saas_tenant_admin_autologin();

            return redirect(admin_url() . '?_magic_auth_session');
        } catch (\Throwable $th) {
            perfex_saas_show_tenant_error(_l('perfex_saas_authentication_error'), $th->getMessage());
        }
    }


    /**
     * Ensure a magic code is passed and is valid
     *
     * @return int The clientid
     * @throws Exception when code is not valid.
     */
    private function validate_and_authorize_magic_auth_code()
    {
        $_code = $this->input->get('auth_code', true);
        $code = $this->encryption->decrypt($_code);
        if (!$code) {
            throw new \Exception(_l('perfex_saas_auth_code_parse_error'), 1);
        }

        $code = explode('|~|', $code);
        if (!$code || count($code) !== 3) {
            throw new \Exception(_l('perfex_saas_invalid_auth_code'), 1);
        }

        $hash = $code[0];
        $time = (int)$code[1];
        $clientid = $code[2];

        $metadata = perfex_saas_get_or_save_client_metadata($clientid);
        if (empty($metadata['magic_code']))
            throw new \Exception(_l('perfex_saas_auth_code_cannot_be_identified'), 1);

        if ($metadata['magic_code'] !== $_code) {
            throw new \Exception(_l('perfex_saas_unknown_auth_code'), 1);
        }

        if ((time() - $time) > 20) {
            throw new \Exception(_l('perfex_saas_auth_code_expired'), 1);
        }

        return (int)$clientid;
    }
}
