<?php

defined('BASEPATH') or exit('No direct script access allowed');

/********TENANT/INSTANCE SPECIFIC HOOKS ******/
if ($is_tenant) {
    // Call the function to register the limitation filters
    perfex_saas_register_limitation_filters();

    // Override instant options with shared option in package where applicable
    perfex_saas_init_shared_options();

    /**
     * Hook for tenant instance settings page.
     * It attempt to mask settings and  removes upgrade and system info links.
     *
     * @return void
     */
    hooks()->add_action('settings_group_end', 'perfex_saas_settings_group_end_hook');
    function perfex_saas_settings_group_end_hook()
    {
        // Output buffer contents
        $output = ob_get_contents();
        ob_end_clean();

        // Remove anchors with URLs ending in '?group=update' or '?group=info'
        $pattern = '/<a[^>]*(group=update|group=info)[^>]*>(.*?)<\/a>/is';
        $replacement = '';
        $output = preg_replace($pattern, $replacement, $output);

        // Parse and mask secret value from tenant instances
        $output = perfex_saas_mask_secret_values($output);

        // Start a new output buffer and send the modified output
        ob_start();

        // Output the modified content
        echo $output;
    }

    // Prevent saving enforced shared fields by overriding with master value
    hooks()->add_filter('before_settings_updated', 'perfex_saas_before_settings_updated_hook');
    function perfex_saas_before_settings_updated_hook($data)
    {
        $tenant = perfex_saas_tenant();

        // Enforced fields
        $enforced_fields = array_merge(PERFEX_SAAS_ENFORCED_SHARED_FIELDS, (array) ($tenant->package_invoice->metadata->shared_settings->enforced ?? []));
        $enforced_settings = perfex_saas_master_shared_settings($enforced_fields);
        foreach ($enforced_settings as $setting) {
            // Override with master value
            $data['settings'][$setting->name] = $setting->value;
        }

        // Prevent saving masked string of setting values
        $masked_fields = (array) ($tenant->package_invoice->metadata->shared_settings->masked ?? []);
        $shared_secret_master_settings = perfex_saas_master_shared_settings($masked_fields);
        foreach ($shared_secret_master_settings as $setting) {
            if (
                isset($data['settings'][$setting->name]) &&
                perfex_saas_get_starred_string($setting->value) === perfex_saas_get_starred_string($data['settings'][$setting->name])
            ) {
                $data['settings'][$setting->name] = "";
            }
        }

        return $data;
    }

    // Add limitation statistic widget to dashboard
    hooks()->add_action('before_start_render_dashboard_content', 'perfex_saas_dashboard_hook', 8);
    function perfex_saas_dashboard_hook()
    {
        $CI = &get_instance();
        if (is_admin()) {
            $CI->load->model('invoices_model');
            $invoice = perfex_saas_tenant()->package_invoice;
            $on_trial = isset($invoice->status) && $invoice->status == Invoices_model::STATUS_DRAFT;
            $days_left = $on_trial ? (int)perfex_saas_get_days_until($invoice->duedate) : '';
            $invoice_days_left = $invoice ? perfex_saas_get_days_until($invoice->duedate) : '';

            $CI->load->view(
                PERFEX_SAAS_MODULE_NAME . '/client/includes/invoice_notification',
                [
                    'invoice' => $invoice,
                    'days_left' => $days_left,
                    'on_trial' => $on_trial,
                    'invoice_days_left' => $invoice_days_left
                ]
            );
        }
    }
    hooks()->add_filter('get_dashboard_widgets', function ($widgets) {
        return array_merge([['path' => PERFEX_SAAS_MODULE_NAME . '/includes/quota_stats', 'container' => 'top-12']], $widgets);
    });

    // Load custom css
    hooks()->add_action('app_admin_assets_added', function () use ($CI) {
        if (is_admin())
            $CI->app_css->add('saas-admin', perfex_saas_asset_url('assets/css/tenant-admin.css'));
    });
}
