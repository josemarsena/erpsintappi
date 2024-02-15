<?php

use Symfony\Component\Translation\Provider\Dsn;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Map of limitation filters and their corresponding tables.
 */
const PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP = [
    'before_create_staff_member' => 'staff',
    'before_client_added' => 'clients',
    'before_create_contact' => 'contacts',
    'before_contract_added' => 'contracts',
    'before_invoice_added' => 'invoices',
    'before_estimate_added' => 'estimates',
    'before_create_credit_note' => 'creditnotes',
    'before_create_proposal' => 'proposals',
    'before_add_project' => 'projects',
    'before_add_task' => 'tasks',
    'before_ticket_created' => 'tickets', // has two option $data and $admin
    'before_lead_added' => 'leads',
    'before_item_created' => 'items',
];

/**
 * Register the limitation filters and their corresponding validation function.
 */
function perfex_saas_register_limitation_filters()
{
    foreach (PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP as $event => $table) {
        // Set priority to 0 as we want this to run before any other attached hooks to the filter.
        hooks()->add_filter($event, 'perfex_saas_validate_limits', 0);
    }

    // Check tenant storage limits
    $storage_validation_handler = function () {
        $tenant = perfex_saas_tenant();
        load_admin_language();
        perfex_saas_limit_reached_middleware(
            'storage',
            _l('perfex_saas_storage_exhausted', perfex_saas_tenant_used_storage($tenant, true) . '/' . perfex_saas_tenant_storage_limit($tenant)),
            _l('perfex_saas_quota_exhausted_cron', [$tenant->slug, "storage"])
        );
    };
    if (!empty($uploaded_files = $_FILES)) {
        if (!perfex_saas_tenant_has_enough_storage(perfex_saas_tenant(), $uploaded_files)) {
            $storage_validation_handler();
        }
    }
    hooks()->add_action('before_make_backup', function () use ($storage_validation_handler) { // Backup folder limit
        if (!perfex_saas_tenant_has_enough_storage(perfex_saas_tenant())) {
            $storage_validation_handler();
        }
    });
}

/**
 * Validate the limits for a specific event.
 * i.e check limit for invoices.
 *
 * @param mixed $data - The data passed to the filter hook.
 * @param mixed $admin - Optional. The admin data passed to the filter hook.
 * @return mixed - The filtered data.
 * @throws \Exception - When an unsupported limitation filter is encountered.
 */
function perfex_saas_validate_limits($data, $admin = null)
{
    // Get the active filter
    $filter = hooks()->current_filter();

    // Get the filter table
    $limit_name = PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP[$filter];

    // Ensure we have a table for the filter
    if (empty($limit_name)) {
        throw new \Exception("Unsupported limitation filter: $filter", 1);
    }

    // Get the tenant 
    $tenant = perfex_saas_tenant();

    // Get tenant details and get package limit
    $quota = perfex_saas_tenant_resources_quota($tenant, $limit_name);

    // Ulimited pass
    if ($quota === -1) return $data;

    // Get count for the active tenant from table and match against package
    $usage = perfex_saas_get_tenant_quota_usage($tenant, [$limit_name])[$limit_name];

    // If quota is exceeded, set flash and redirect back
    $reached_limit = $quota <= $usage;

    if ($reached_limit) {
        $msg_key = 'perfex_saas_quota_exhausted';
        return perfex_saas_limit_reached_middleware(
            $limit_name,
            _l($msg_key, $limit_name),
            _l($msg_key . '_cron', [$tenant->slug, $limit_name])
        );
    }

    return $data;
}

/**
 * Get the usage of tenant quotas.
 *
 * @param mixed $tenant - The tenant object.
 * @param string[] $limits - Optional. The list of limits to retrieve usage for. Will use global list when empty.
 * @param mixed $package - Optional. The tenant package for detecting dsn
 * @return array - The usage list for each limit.
 */
function perfex_saas_get_tenant_quota_usage($tenant, $limits = [], $package = null)
{
    $tenant_slug = $tenant->slug;

    /** The tenant db prefix */
    $dbprefix = perfex_saas_tenant_db_prefix($tenant_slug);

    $usage_list = [];
    $queries = [];

    $limits = empty($limits) ? array_values(PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP) : $limits;
    $dsn = perfex_saas_get_company_dsn($tenant, $package);

    foreach ($limits as $limit) {
        $table = $dbprefix . $limit;
        // Get count for the active tenant from table and match against package.
        $queries[$limit] = "SELECT COUNT(*) as total FROM $table";
    }

    // Run queries and set limits
    $usages = perfex_saas_raw_query($queries, $dsn, true);
    foreach ($usages as $limit => $usage) {
        $usage_list[$limit] = (int)$usage[0]->total;
    }

    return $usage_list;
}


/**
 * Exit the program with a limitation reached message.
 * The function attempt to handle base on varying scenario of request.
 *
 * @param object $tenant
 * @param string $limit_name
 * @return void
 */
function perfex_saas_limit_reached_middleware($limit_name, $msg, $cron_msg)
{
    if (!defined('CRON')) {
        set_alert('danger', $msg);

        // Handle ajax requests
        if (get_instance()->input->is_ajax_request()) {
            header('HTTP/1.0 400 Bad error');
            echo $limit_name === 'tasks' || ($limit_name == 'storage' && stripos(uri_string(), 'tasks/task')) ? json_encode($msg) : $msg;
            exit;
        }

        perfex_saas_redirect_back();
    } else {
        log_message('info', $cron_msg ?? $msg);
    }
    exit;
}

/**
 * Get the tenant quota for a resources
 *
 * @param object $tenant
 * @param string $resources
 * @return int
 */
function perfex_saas_tenant_resources_quota($tenant, $resources)
{

    // Get tenant details and get package limit
    $quota = (int)($tenant->package_invoice->metadata->limitations->{$resources} ?? -1);

    // Ulimited pass
    if ($quota === -1) return $quota;

    // Add extra purchased limits
    $extra_quota = (int)($tenant->package_invoice->custom_limits->{$resources} ?? 0);
    $quota = $extra_quota + $quota;

    return $quota;
}
