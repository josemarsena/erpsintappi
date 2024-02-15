<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        exit("OK");
    }

    /**
     * Open endpoint to check if a domain is recognized by the system.
     * This is intented for On demand TLS with servers like caddy for auto SSL generation for tenants.
     * 
     * Return 404 if no match, 200 (OK) if same as base domain and 200(Matched) when a match found (subdomain or custom domain)
     *
     * i.e http://perfexdomain.com/perfex_saas/api/caddy_domain_check?domain=ulutfa.crm.com
     * @return void
     */
    public function caddy_domain_check()
    {
        // Get the domain or subdomain and validate
        $domain = $this->input->get("domain", true);
        if (empty($domain)) {
            set_status_header(400);
            echo 'No domain provided';
            return;
        }

        if (perfex_saas_get_saas_default_host() === $domain) {
            set_status_header(200);
            echo "OK";
            return;
        }

        // Detect info if using subdomain or custom domain. Will return non empty 'slug' if subdomain other 'custom_domain' or false
        $tenant_info = perfex_saas_get_tenant_info_by_host($domain);
        if ($tenant_info) {
            $identified_by_slug = !empty($tenant_info['slug']);
            $field = $identified_by_slug ? 'slug' : 'custom_domain';
            $value = $identified_by_slug ? $tenant_info['slug'] : $tenant_info['custom_domain'];
            $tenant = perfex_saas_search_tenant_by_field($field, $value);
            if ($tenant) {
                set_status_header(200);
                echo "Matched";
                return;
            }
        }

        // Set 404
        set_status_header(404);
    }

    /**
     * Check for slug availability
     *
     * @param string $slug
     * @return string
     */
    public function is_slug_available($slug)
    {
        $is_available = true;

        if (!perfex_saas_slug_is_valid($slug))
            $is_available = false;

        if ($is_available)
            $is_available = perfex_saas_generate_unique_slug($slug, 'companies') === $slug;

        return $this->response_json(['available' => $is_available]);
    }


    /**
     * Pricing list
     *
     * @param string $id Optional price id
     * @return void
     */
    public function plans($id = '')
    {

        $_packages = $this->perfex_saas_model->packages((int)$id);
        if (!is_array($_packages)) $_packages = [$_packages];

        $package_list = [];
        foreach ($_packages as $key => $package) {
            $package_list[] = [
                "id" => $package->id,
                "name" => $package->name,
                "description" => $package->description,
                "slug" => $package->slug,
                "price" => $package->price,
                "trial_period" => $package->trial_period,
                "is_default" => $package->is_default,
                "is_private" => $package->is_private,
                "db_scheme" => $package->db_scheme,
                "status" => $package->status,
                "modules" => $package->modules,
                "metadata" => [
                    "invoice" => $package->metadata->invoice,
                    "max_instance_limit" => $package->metadata->max_instance_limit,
                    "limitations" => $package->metadata->limitations,
                    "enable_subdomain" => $package->metadata->enable_subdomain,
                    "enable_custom_domain" => $package->metadata->enable_custom_domain,
                    "shared_settings" => $package->metadata->shared_settings ?? []
                ],
            ];
        }

        return $this->response_json($package_list);
    }


    /**
     * echo response as json
     *
     * @param array $data
     * @return mixed
     */
    private function response_json(array $data)
    {
        echo json_encode($data);
        exit();
    }


    /*** TENANTS SPECIFIC SERVICES */
    public function update_tenant_storage_size()
    {
        if (!perfex_saas_is_tenant()) return;

        perfex_saas_update_tenant_storage_size(perfex_saas_tenant());
        return $this->response_json(['success' => true]);
    }
}
