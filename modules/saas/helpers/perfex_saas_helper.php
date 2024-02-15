<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get the base URL for a tenant or instance.
 *
 * This function constructs the base URL for a tenant/ It uses the `perfex_saas_tenant_url_signature()` function
 * to generate the URL signature or use subdomain or custom domain base on the method passed.
 *
 * @param object $tenant     The tenant object.
 * @param string $endpoint Optional. The endpoint to append to the base URL. Default is an empty string.
 * @param string $method Optional. The type of url needed. 'path' to use req_uri scheme, 
 * 'auto' to autodetect base on settings and custom domain and all to get all possible addresses
 *
 * @return string|array The base URL for the tenant or array of all possible path when method === 'all'
 */
function perfex_saas_tenant_base_url($tenant, $endpoint = '', $method = 'auto')
{
    $slug = $tenant->slug;
    $default_url = APP_BASE_URL_DEFAULT . perfex_saas_tenant_url_signature($slug) . "/$endpoint";
    $subdomain = "";
    $custom_domain = "";

    if ($method == 'path') {
        return $default_url;
    }

    $package = $tenant->package_invoice ?? null;

    if (!$package && !perfex_saas_is_tenant()) {
        $CI = &get_instance();
        $package = $CI->perfex_saas_model->get_company_invoice($tenant->clientid);
    }

    $can_use_custom_domain = $package->metadata->enable_custom_domain ?? false;

    // If has custom domain, and available for use
    if (!empty($tenant->custom_domain) && $can_use_custom_domain) {
        $custom_domain =  perfex_saas_prep_url($tenant->custom_domain . '/' . $endpoint);
        if ($method === 'auto') return $custom_domain;
    }

    // If subdomain is enabled on package, use subdomain
    $can_use_subdomain = $package->metadata->enable_subdomain ?? false;
    if ($can_use_subdomain) {
        $subdomain = perfex_saas_prep_url($slug . '.' . perfex_saas_get_saas_default_host() . '/' . $endpoint);
        if ($method === 'auto') return $subdomain;
    }

    if ($method === 'all') return [
        'path' => $default_url,
        'subdomain' => $subdomain,
        'custom_domain' => $custom_domain,
    ];

    return $default_url;
}

/**
 * Get the admin URL for a tenant or instance.
 *
 * This function constructs the admin URL for a tenant by appending It uses the `perfex_saas_tenant_base_url()` function
 * to generate the base URL for the tenant.
 *
 * @param object $tenant     The  tenant object
 * @param string $endpoint Optional. The endpoint to append to the admin URL. Default is an empty string.
 * @param string $method Optional. The type of url needed. 'path' to use req_uri scheme, 'auto' to autodetect base on settings and custom domain. 
 * @return string The admin URL for the tenant.
 */
function perfex_saas_tenant_admin_url($tenant, $endpoint = '', $method = 'auto')
{
    return perfex_saas_tenant_base_url($tenant, "admin/$endpoint", $method);
}

/**
 * Custom CI Prep URL
 *
 * Simply adds the https:// part if running on https
 *
 * @param	string	the URL
 * @return	string
 */
function perfex_saas_prep_url($str = '')
{
    $url = prep_url($str);

    if (str_starts_with($url, 'http://') && is_https())
        $url = str_ireplace('http://', 'https://', $url);

    return $url;
}

/**
 * Generate a unique slug.
 *
 * This function generates a unique slug based on the provided string. It ensures that the slug
 * is not already used in the specified table and is not in the reserved list of slugs. If the
 * generated slug is not unique or is reserved, it appends a random number and recursively calls
 * itself to generate a new slug until a unique one is found.
 *
 * @param string $str    The string to generate the slug from.
 * @param string $table  The table name to check for existing slugs.
 * @param string $id     Optional. The ID of the record to exclude from the check. Default is an empty string.
 * @param int $reps  The current number of trial. Will giveup after 20th trial of generating unique string
 *
 * @return string The generated unique slug.
 * @throws Exception After 20 trials
 */
function perfex_saas_generate_unique_slug(string $str, string $table, string $id = '', $reps = 0)
{
    $CI = &get_instance();

    $str = strtolower($CI->security->xss_clean(urldecode($str)));

    if (!perfex_saas_str_starts_with_alpha($str)) {
        $CI->load->helper('string');
        $str = random_string('alpha', 4) . '_' . $str;
    }

    $str = substr($str, 0, PERFEX_SAAS_MAX_SLUG_LENGTH);

    // Ensure its table prefix equivalent not taken also
    $str = perfex_saas_str_to_valid_table_name($str);

    if ($id != '') {
        $CI->db->where('id !=', $id);
    }

    // Ensure uniqueness
    if (
        $CI->db->where('slug', $str)->get(perfex_saas_table($table), 1)->num_rows() > 0 ||
        !perfex_saas_slug_is_valid($str)
    ) {
        // Give up
        if ($reps > 20) {
            throw new \Exception("Giveup: Erorr generating a unique ID", 1);
        }

        $str = substr($str, 0, 10) . random_int(10, 999);

        return perfex_saas_generate_unique_slug($str, $table, $id, $reps + 1);
    }

    return slug_it($str);
}

/**
 * Check if a string slug is valid or not.
 * Will confirm slug start with string and not in reserved list and has lenght within 3 and PERFEX_SAAS_MAX_SLUG_LENGTH.
 *
 * @param string $slug
 * @return bool
 */
function perfex_saas_slug_is_valid($slug)
{
    $slug = strtolower(str_ireplace('-', '', $slug));

    if (empty($slug) || is_numeric($slug)) return false;

    if (in_array($slug, perfex_saas_reserved_slugs())) return false;

    if (strlen($slug) > PERFEX_SAAS_MAX_SLUG_LENGTH || strlen($slug) < 3) return false;

    // Must start with alphabet
    return perfex_saas_str_starts_with_alpha($slug);
}

/**
 * Check is a string starts with alphabet.
 *
 * @param string $slug
 * @return bool
 */
function perfex_saas_str_starts_with_alpha($slug)
{
    return preg_match('/^[a-z]/', strtolower($slug)) === 1;
}

/**
 * Get the list of reserved slugs including system reserved.
 *
 * @return string[]
 */
function perfex_saas_reserved_slugs()
{
    $reserved_list = explode(',', strtolower(get_option('perfex_saas_reserved_slugs')));
    $reserved_list = array_merge([perfex_saas_master_tenant_slug(), 'app', 'main', 'www', 'ww3', 'mail', 'cname', 'web', 'admin', 'customer', 'base', 'contact'], $reserved_list);
    return $reserved_list;
}

/**
 * Get the DSN (Database Source Name) for a company.
 *
 * This function retrieves the DSN for a company, which is used to establish a database connection.
 * It checks if the company already has a DSN assigned. If not, it checks the company's invoice and
 * package details to determine the appropriate DSN. The function handles different database
 * deployment schemes, such as multitenancy, single database per company, and sharding.
 *
 * @param object|null $company The company object for which to get the DSN. Can be null.
 * @param object|null $invoice The invoice object associated with the company. Can be null.
 *
 * @return array The DSN details as an associative array.
 *
 * @throws \Exception When a valid data center cannot be found.
 */
function perfex_saas_get_company_dsn($company = null, $invoice = null)
{
    $default_dsn = perfex_saas_master_dsn();
    $CI = &get_instance();

    if (!empty($company->dsn)) {
        $dsn = perfex_saas_parse_dsn($company->dsn);

        // If no user, set the default user
        if (!empty($dsn['host']) && $dsn['host'] === $default_dsn['host'] && empty($dsn['user'])) {
            $dsn['user'] = empty($dsn['user']) ? $default_dsn['user'] : $dsn['user'];
            $dsn['password'] = empty($dsn['password']) ? $default_dsn['password'] : $dsn['password'];
        }

        if (perfex_saas_is_valid_dsn($dsn) === true) {
            return $dsn;
        }

        if (isset($dsn['dbname']) && $dsn['dbname'] == APP_DB_NAME && empty($dsn['password'])) {
            return $default_dsn;
        }

        if (isset($dsn['dbname']) && $dsn['dbname'] == perfex_saas_db($company->slug)) {
            $default_dsn['dbname'] = $dsn['dbname'];
            return $default_dsn;
        }
    }

    if (empty($company->dsn) && !empty($invoice)) {
        $invoice = is_null($invoice) ? $CI->perfex_saas_model->get_company_invoice($company->clientid) : $invoice;

        if (isset($invoice->db_scheme) && !empty($invoice->db_scheme)) {
            $db_scheme = $invoice->db_scheme;

            if ($db_scheme == 'multitenancy') {
                return $default_dsn;
            }

            if ($db_scheme == 'single') {
                $default_dsn['dbname'] = perfex_saas_db($company->slug);
                return $default_dsn;
            }

            $packageid = $invoice->{perfex_saas_column('packageid')};

            list($populations, $pools) = !empty($invoice->db_pools) && !is_string($invoice->db_pools) ?  $CI->perfex_saas_model->get_db_pools_population((array)$invoice->db_pools) :
                $CI->perfex_saas_model->get_db_pools_population_by_packgeid($packageid);

            asort($populations);

            $selected_pool = [];
            if ($db_scheme == 'single_pool') {
                if (((int)array_values($populations)[0]) != 0) {
                    $admin = perfex_saas_get_super_admin();
                    $staffid = $admin->staffid;

                    // Notify the super admin about the database exhaustion.
                    if (add_notification([
                        'touserid' => $staffid,
                        'description' => 'perfex_saas_not_package_db_list_exhausted',
                        'link' => PERFEX_SAAS_MODULE_NAME . '/packages/edit/' . $packageid,
                        'additional_data' => serialize([$invoice->name])
                    ])) {
                        pusher_trigger_notification([$staffid]);
                    }
                } else {
                    $selected_pool = $pools[array_keys($populations)[0]];
                }
            }

            if ($db_scheme == 'shard') {
                $selected_pool = $pools[array_keys($populations)[0]];
            }

            $filter = hooks()->apply_filters('perfex_saas_module_maybe_create_tenant_dsn', ['dsn' => $selected_pool, 'tenant' => $company, 'invoice' => $invoice]);
            $selected_pool = $filter['dsn'];

            if (!empty($selected_pool)) {
                $selected_pool['source'] = 'pool';
                return $selected_pool;
            }
        }
    }

    throw new \Exception(_l('perfex_saas_error_finding_valid_datacenter'), 1);
}

/**
 * Deploy companies.
 *
 * This function deploys companies by updating their status to 'inactive' and then attempting to deploy
 * each company using the `perfex_saas_deploy_company()` function. If the deployment is successful, the
 * company's status is updated to 'active'. If any errors occur during deployment, they are logged and
 * the company is removed and deleted from the database.
 *
 * @param string $company_id The ID of the company to deploy. Can be empty.
 * @param string $clientid The ID of the client associated with the company. Can be empty.
 * @param int $limit The maximum number of companies to deploy at a time.
 *
 * @return array An array containing the total number of companies, the errors encountered during deployment,
 *               and the total number of successfully deployed companies.
 */
function perfex_saas_deployer($company_id = '', $clientid = '', $limit = 5)
{
    if (perfex_saas_is_tenant()) return;

    $CI = &get_instance();
    $CI->db->where('status', 'pending');
    $CI->db->limit($limit);

    if (!empty($clientid)) {
        $CI->db->where('clientid', $clientid);
    }

    $pending_companies = $CI->perfex_saas_model->companies($company_id);

    if (!empty($company_id)) {
        $pending_companies = [$pending_companies];
    }

    $errors = [];
    $total_deployed = 0;

    foreach ($pending_companies as $company) {
        try {
            if (empty($company)) continue;

            // check it the company have primary contact with verified email
            $contact = perfex_saas_get_primary_contact($company->clientid);
            if (!$contact || ($contact && is_null($contact->email_verified_at))) {
                log_message("debug", "Skipping deploy for $company->slug till a verified primary contact is linked");
                continue;
            }

            // Set to invactive
            $CI->perfex_saas_model->add_or_update('companies', ['id' => $company->id, 'status' => 'inactive']);

            // Attempt deploy
            $deploy = perfex_saas_deploy_company($company);

            if ($deploy !== true) throw new \Exception($deploy, 1);

            $CI->perfex_saas_model->add_or_update('companies', ['id' => $company->id, 'status' => 'active']);
            $total_deployed += 1;
        } catch (\Exception $e) {

            // Rollback deployment and creation of the instance
            $error = "$company->name deploy error: " . $e->getMessage();
            $errors[] = $error;
            log_message('error', $error);

            try {
                // Re-fetch from db incase of update to DSN.
                $company = $CI->perfex_saas_model->companies($company->id);
                perfex_saas_remove_company($company);
            } catch (\Throwable $th) {
            }

            $CI->perfex_saas_model->delete('companies', $company->id);
        }
    }

    set_alert('danger', implode("\n", $errors));

    return ['total' => count($pending_companies), 'errors' => $errors, 'total_success' => $total_deployed];
}




/**
 * Deploy a company.
 *
 * This function is responsible for deploying a company by performing various steps
 * such as detecting the appropriate data center, validating the data center, importing
 * SQL seed file, setting up the data center, securing installation settings, registering
 * the first administrative user, and sending notifications.
 *
 * @param object $company   The company object containing information about the company to be deployed.
 * @return bool|string      Returns true if the deployment is successful, otherwise returns an error message.
 * @throws \Throwable       Throws an exception if there is an error during the deployment process.
 */
function perfex_saas_deploy_company($company)
{

    try {

        $CI = &get_instance();
        $invoice = $CI->perfex_saas_model->get_company_invoice($company->clientid);

        // Get data center
        perfex_saas_deploy_step(_l('perfex_saas_detecting_appropriate_datacenter'));
        $dsn = perfex_saas_get_company_dsn($company, $invoice);

        perfex_saas_deploy_step(_l('perfex_saas_validating_datacenter'));
        if (!perfex_saas_is_valid_dsn($dsn, true))
            throw new \Exception(_l('perfex_saas_invalid_datacenter'), 1);

        // Save the DSN if it is obtained from the package pool
        // This is necessary to keep company data intact in case of a package update on database pools
        if (isset($dsn['source']) && $dsn['source'] == 'pool') {

            $data = ['id' => $company->id, 'dsn' => $CI->encryption->encrypt(perfex_saas_dsn_to_string($dsn))];
            $CI->perfex_saas_model->add_or_update('companies', $data);
        }

        perfex_saas_deploy_step(_l('perfex_saas_preparing_datacenter_for_installation'));
        perfex_saas_setup_dsn($dsn, $company->slug);


        perfex_saas_deploy_step(_l('perfex_saas_deploying_seed_to_datacenter'));
        perfex_saas_setup_seed($company, $dsn);

        perfex_saas_deploy_step(_l('perfex_saas_securing_installation_settings'));
        perfex_saas_clear_sensitive_data($company, $dsn, $invoice);


        perfex_saas_deploy_step(_l('perfex_saas_registering_first_administrative_user'));
        perfex_saas_setup_tenant_admin($company, $dsn);


        perfex_saas_deploy_step(_l('perfex_saas_preparing_push_notifications'));

        $notifiedUsers = [];

        // Notify supper admin
        $admin = perfex_saas_get_super_admin();
        $staffid = $admin->staffid;
        if (add_notification([
            'touserid' => $staffid,
            'description' => 'perfex_saas_not_customer_create_instance',
            'link' => 'clients/client/' . $company->clientid,
            'additional_data' => serialize([$company->name])
        ])) {
            array_push($notifiedUsers, $staffid);
        }

        perfex_saas_deploy_step(_l('perfex_saas_sending_push_notification_to_the_company_and_superadmin'));
        pusher_trigger_notification($notifiedUsers);

        perfex_saas_deploy_step(_l('perfex_saas_sending_email_notification_to_the_company_contact_and_superadmin'));

        // Send email to customer about deployment
        $contact = perfex_saas_get_primary_contact($company->clientid);

        if (!empty($contact->email)) {
            send_mail_template('customer_deployed_instance', PERFEX_SAAS_MODULE_NAME, $contact->email, $company->clientid, $contact->id, $company);
        }

        // Send email to admin about the removal
        if (!empty($admin->email)) {
            send_mail_template('customer_deployed_instance_for_admin', PERFEX_SAAS_MODULE_NAME, $admin->email, $company->clientid, $contact->id, $company);
        }

        perfex_saas_deploy_step(_l('perfex_saas_complete'));

        hooks()->do_action('perfex_saas_module_tenant_deployed', ['tenant' => $company, 'invoice' => $invoice]);

        return true;
    } catch (\Throwable $th) {

        try {
            // Noify supper admin
            $admin = perfex_saas_get_super_admin();
            $staffid = $admin->staffid;

            $notifiedUsers = [];
            if (add_notification([
                'touserid' => $staffid,
                'description' => 'perfex_saas_not_customer_create_instance_failed',
                'link' => 'clients/client/' . $company->clientid,
                'additional_data' => serialize([$company->name, $th->getMessage()])
            ])) {
                array_push($notifiedUsers, $staffid);
            }

            pusher_trigger_notification($notifiedUsers);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
        }

        log_message("error", $th->getMessage());

        hooks()->do_action('perfex_saas_module_tenant_deploy_failed', $company);

        if (ENVIRONMENT == 'development') throw $th;

        return $th->getMessage();
    }
    return false;
}

/**
 * Set session for the active step in company deployment.
 * 
 * This will be helpful to improve user UX by showing progress of the deployment.
 *
 * @param string $step The description text for the step.
 * @return void
 */
function perfex_saas_deploy_step(string $step)
{
    $_SESSION['perfex_saas_deploy_step'] = $step;
}

/**
 * Remove crm instance for a company.
 * 
 * This function attempt to remove the instance data and delete the instance from the database.
 * It send email notification to both the admin and user about the removal of the instance.
 *
 * @param object $company The company instance to delete
 * @return boolean|string True when successful or string stating the error encountered.
 */
function perfex_saas_remove_company($company)
{

    try {

        if (perfex_saas_is_tenant()) return;

        if (!isset($company->slug) || empty($company->slug))
            throw new \Exception(_l('perfex_saas_company_slug_is_missing_!'), 1);

        $slug = $company->slug;

        $CI = &get_instance();
        $invoice = $CI->perfex_saas_model->get_company_invoice($company->clientid);

        // Get the data center
        perfex_saas_deploy_step(_l('perfex_saas_detecting_appropriate_datacenter'));
        $dsn = perfex_saas_get_company_dsn($company, $invoice);

        perfex_saas_deploy_step(_l('perfex_saas_validating_datacenter'));
        if (!perfex_saas_is_valid_dsn($dsn, true))
            throw new \Exception(_l('perfex_saas_invalid_datacenter'), 1);

        perfex_saas_deploy_step(_l('perfex_saas_removing_data_from_datacenter'));

        $tenant_dbprefix = perfex_saas_tenant_db_prefix($slug);
        $db = perfex_saas_load_ci_db_from_dsn($dsn, ['dbprefix' => $tenant_dbprefix]);

        // Get all table list
        $tables = $db->list_tables(); //neutral global query, wont fail
        $deleted_tables = 0;

        // Loop through all and remove all data with tenant column of the company slug
        foreach ($tables as $table) {
            if (str_starts_with($table, $tenant_dbprefix)) {
                perfex_saas_raw_query("DROP TABLE $table", $dsn, false, false, null, true);
                $deleted_tables++;
            }
        }

        // Check tenant owns db and table is empty
        if (
            $dsn['dbname'] === perfex_saas_db($slug) &&
            count($tables) === $deleted_tables
        ) {
            try {
                // Drop database if using single and not other tenant on the db
                perfex_saas_raw_query("DROP DATABASE `" . $dsn['dbname'] . '`', $dsn, false, false, null, true);
            } catch (\Throwable $th) {
                log_message("error", $th->getMessage());
            }
        }

        // Storage clean: Clear tenant general file uploads
        $tenant_upload_folder = perfex_saas_tenant_upload_base_path($company);
        perfex_saas_remove_dir($tenant_upload_folder);

        // Storage clean: Clear tenants media upload
        $tenant_media_folder =  perfex_saas_tenant_media_base_path($company);
        perfex_saas_remove_dir($tenant_media_folder);

        perfex_saas_deploy_step(_l('perfex_saas_preparing_push_notifications'));

        $notifiedUsers = [];

        // Notify supper admin
        $admin = perfex_saas_get_super_admin();
        $staffid = $admin->staffid;
        if (add_notification([
            'touserid' => $staffid,
            'description' => 'perfex_saas_not_customer_instance_removed',
            'link' => 'clients/client/' . $company->clientid,
            'additional_data' => serialize([$company->name])
        ])) {
            array_push($notifiedUsers, $staffid);
        }

        perfex_saas_deploy_step(_l('perfex_saas_sending_push_notification_to_the_company_and_superadmin'));
        pusher_trigger_notification($notifiedUsers);

        perfex_saas_deploy_step(_l('perfex_saas_sending_email_notification_to_the_company_contact_and_superadmin'));
        // Send email to customer about removal
        $contact = perfex_saas_get_primary_contact($company->clientid);
        if (!empty($contact->email)) {
            send_mail_template('customer_removed_instance', PERFEX_SAAS_MODULE_NAME, $contact->email, $company->clientid, $contact->id, $company);
        }

        // Send email to admin about the removal
        if (!empty($contact->id) && !empty($admin->email)) {
            send_mail_template('customer_removed_instance_for_admin', PERFEX_SAAS_MODULE_NAME, $admin->email, $company->clientid, $contact->id, $company);
        }

        perfex_saas_deploy_step(_l('perfex_saas_complete'));

        hooks()->do_action('perfex_saas_module_tenant_removed', $company);

        return true;
    } catch (\Throwable $th) {

        try {
            // Notify supper admin
            $admin = perfex_saas_get_super_admin();
            $staffid = $admin->staffid;

            $notifiedUsers = [];
            if (add_notification([
                'touserid' => $staffid,
                'description' => 'perfex_saas_not_customer_create_instance_failed',
                'link' => 'clients/client/' . $company->clientid,
                'additional_data' => serialize([$company->name, $th->getMessage()])
            ])) {
                array_push($notifiedUsers, $staffid);
            }

            pusher_trigger_notification($notifiedUsers);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
        }

        log_message("error", $th->getMessage());

        hooks()->do_action('perfex_saas_module_tenant_removal_failed', $company);

        if (ENVIRONMENT == 'development') throw $th;

        return $th->getMessage();
    }
    return false;
}

/**
 * Retrieves the primary contact associated with a user ID from the master DB.
 *
 * @param int $userid The ID of the user
 * @return mixed The primary contact row object if found, otherwise false
 */
function perfex_saas_get_primary_contact($userid)
{
    $CI = &get_instance();
    $CI->db->where('userid', $userid);
    $CI->db->where('is_primary', 1);
    $row = $CI->db->get(perfex_saas_master_db_prefix() . 'contacts')->row();

    if ($row) {
        return $row;
    }

    return false;
}

/**
 * Sets up tables on a dsn from a given data source name (DSN).
 * The data source table should start with master db prefix i.e 'tbl'
 * 
 * @param array $dsn The data source name (DSN) configuration
 * @param string $slug The db prefix for the tenant
 * @param array $source_dsn The master source dsn to be used as template
 * @return void
 */
function perfex_saas_setup_dsn($dsn, $slug, $source_dsn = [])
{
    $dbprefix = perfex_saas_master_db_prefix();

    // List all tables from master 
    $sql_tables = "SHOW TABLES LIKE '" . $dbprefix . "%'";
    $master_tables = perfex_saas_raw_query($sql_tables, $source_dsn, true);

    $show_create_queries = [];
    $col = array_key_first((array)$master_tables[0]);

    foreach ($master_tables as $row) {

        $table = $row->{$col};

        // skip saas tables and not perfex tables
        if (str_starts_with($table, perfex_saas_table('')) || !str_starts_with($table, $dbprefix)) continue;

        $query = "SHOW CREATE TABLE `$table` -- create tables";
        $row = perfex_saas_raw_query_row($query, $source_dsn, true);
        $new_table =  perfex_saas_tenant_db_prefix($slug, $table);

        $sql = $row->{"Create Table"};
        if (stripos($sql, 'CREATE TABLE IF NOT EXISTS') === false)
            $sql = str_ireplace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $sql);

        $table_without_prefix =  str_ireplace($dbprefix, '', $table);
        $new_table_without_master_prefix = str_ireplace($dbprefix, '', $new_table);

        // Rename table and foreign key
        $sql = str_ireplace(
            [
                $table,
                'KEY `' . $table_without_prefix,
                'CONSTRAINT `' . $table_without_prefix
            ],
            [
                $new_table,
                'KEY `' . $new_table_without_master_prefix,
                'CONSTRAINT `' . $new_table_without_master_prefix
            ],
            $sql
        );

        $show_create_queries[] =  $sql;
    }

    $sql_commands_to_run =  implode('*;*', $show_create_queries);

    // Loop through all table and replace table name in query with new table.
    foreach ($master_tables as $row) {
        $table = $row->{$col};
        $new_table =  perfex_saas_tenant_db_prefix($slug, $table);

        $table_without_prefix =  str_ireplace($dbprefix, '', $table);
        $new_table_without_master_prefix = str_ireplace($dbprefix, '', $new_table);

        // Rename table and foreign key
        $sql_commands_to_run = str_ireplace(
            [
                '`' . $table,
                'KEY `' . $table_without_prefix,
                'CONSTRAINT `' . $table_without_prefix
            ],
            [
                '`' . $new_table,
                'KEY `' . $new_table_without_master_prefix,
                'CONSTRAINT `' . $new_table_without_master_prefix
            ],
            $sql_commands_to_run
        );
    }

    $sql_commands_to_run = explode('*;*', $sql_commands_to_run);

    return perfex_saas_raw_query($sql_commands_to_run, $dsn, false, false, null, true, true);
}

/**
 * Sets up seed data for a company by populating specific tables with default values.
 *
 * @param object $company The company object containing information about the company
 * @param array $dsn The data source name (DSN) configuration for the company's database
 * @return array
 */
function perfex_saas_default_seed_tables()
{
    $dbprefix = perfex_saas_master_db_prefix();

    $table_selectors = [
        'emailtemplates' => ['type', 'slug', 'language'],
        'leads_email_integration' => ['id'],
        'leads_sources' => ['name'],
        'leads_status' => ['name'],
        'options' => ['name'],
        'roles' => ['name'],
        'tickets_priorities' => ['name'],
        'tickets_status' => ['name'],
        'countries' => ['short_name', 'calling_code'],
        'currencies' => ['name', 'symbol'],
        'migrations' => ['version'],
        'customfields' => ['id', 'slug']
    ];

    $tables = [];
    foreach ($table_selectors as $table => $selectors) {
        $tables[$dbprefix . $table] = $selectors;
    }

    return $tables;
}

function perfex_saas_setup_seed($company, $dsn)
{
    $CI = &get_instance();

    $slug = $company->slug;
    $tenant_dbprefix = perfex_saas_tenant_db_prefix($slug);
    $dbprefix = perfex_saas_master_db_prefix();

    // Define the table selectors that specify the columns to be selected from each table
    $default_table_selectors = perfex_saas_default_seed_tables();
    $seed_tables = get_option('perfex_saas_tenants_seed_tables');
    $seed_tables = empty($seed_tables) ? array_keys($default_table_selectors) : array_merge((array)json_decode($seed_tables), [$dbprefix . 'options', $dbprefix . 'migrations']);

    $queries = [];

    // Set db prefix for insert string generation
    $db = clone ($CI->db);
    $db->dbprefix = $tenant_dbprefix;

    // Loop through each table selector
    foreach ($seed_tables as $table) {

        $tenant_table = str_replace_first($dbprefix, $tenant_dbprefix, $table);
        $selectors = $default_table_selectors[$table] ?? [];

        $q = "SELECT * FROM $table";

        $primary_col = '';

        // Retrieve rows from the master database
        $rows = perfex_saas_raw_query($q, [], true, false);

        foreach ($rows as $row) {

            $row = (array)$row;

            if (empty($primary_col)) {
                $primary_col = array_keys($row)[0];
            }

            $where = empty($selectors) ? ["`$primary_col` = " . $db->escape($row[$primary_col])] : [];
            foreach ($selectors as $selector) {
                $value = $row[$selector];
                $where[] = "`$selector`=" . $db->escape($value);
            }

            if ($table === $dbprefix . 'leads_email_integration') {
                $row['id'] = 1;
            }

            // Check if the row already exists in the company's database
            $result = perfex_saas_raw_query("SELECT * FROM $tenant_table WHERE " . implode(' AND ', $where) . ' LIMIT 1', $dsn, true);
            if (!$result || count($result) == 0) {
                // Insert the row into the company's database
                $queries[] = $db->insert_string($tenant_table, $row);
            }
        }

        $primary_col = '';
    }

    // Execute the queries to insert the seed data into the company's database
    perfex_saas_raw_query($queries, $dsn);
}

/**
 * Retrieves the super admin for a specific company or the master tenant.
 *
 * @param string $slug (Optional) The slug of the company. Defaults to the master tenant slug.
 * @param array $dsn (Optional) The data source name (DSN) configuration. Defaults to an empty array.
 * @return object|false The super admin object if found, false otherwise.
 */
function perfex_saas_get_super_admin($slug = '', $dsn = [])
{
    $dbprefix = empty($slug) ? perfex_saas_master_db_prefix() : perfex_saas_tenant_db_prefix($slug);
    $table = $dbprefix . "staff";

    // Retrieve the super admin from the database
    return perfex_saas_raw_query_row("SELECT * FROM $table WHERE `admin`='1' AND `active`='1' LIMIT 1", $dsn, true);
}

/**
 * Function to add admin login credential to an instance setup.
 * 
 * Will not run if admin already exist on the DB.
 *
 * @param object $tenant The company instance object containing information about the company.
 * @param array $dsn
 * @return void
 */
function perfex_saas_setup_tenant_admin($tenant, $dsn)
{
    $CI = &get_instance();

    $tenant_dbprefix = perfex_saas_tenant_db_prefix($tenant->slug);
    $table = $tenant_dbprefix . "staff";

    $result = perfex_saas_get_super_admin($tenant->slug, $dsn);
    if (isset($result->email)) return true; //already exist

    // Get contact from customer
    $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($tenant->clientid));

    // Fallback to the active staff
    if (!$contact && is_staff_logged_in())
        $contact = get_staff(get_staff_user_id());

    if (!$contact) throw new \Exception(_l('perfex_saas_error_getting_contact_to_be_used_as_administrator_on_the_new_instance'), 1);

    // Insert admin login to the instance
    $data = [];
    $data['firstname']   = $contact->firstname;
    $data['lastname']    = $contact->lastname;
    $data['email']       = $contact->email;
    $data['phonenumber'] = $contact->phonenumber;
    $data['password'] = $contact->password;
    $data['admin']  = 1;
    $data['active'] = 1;
    $data['datecreated'] = date('Y-m-d H:i:s');

    // Set db prefix for insert string generation
    $db = clone ($CI->db);
    $db->dbprefix = $tenant_dbprefix;
    $admin_insert_query = $db->insert_string($table, $data);

    return perfex_saas_raw_query($admin_insert_query, $dsn);
}

/**
 * Clears sensitive data and sahred data from the company instance.
 *
 * @param object $company The company object containing information about the company.
 * @param array $dsn_array The data source name (DSN) configuration.
 * @param object|null $invoice (Optional) The invoice object. Defaults to null.
 * @return void
 */
function perfex_saas_clear_sensitive_data($company, $dsn_array, $invoice = null)
{
    $CI = &get_instance();

    $slug = $company->slug;
    $tenant_dbprefix = perfex_saas_tenant_db_prefix($slug);
    $options_table = $tenant_dbprefix . "options";
    $emailtemplate_table = $tenant_dbprefix . "emailtemplates";

    // Check if installation has already been secured
    $r = perfex_saas_raw_query("SELECT `value` FROM $options_table WHERE `name`='perfex_saas_installation_secured'", $dsn_array, true);
    if (count($r) > 0) {
        return;
    }

    $where = "WHERE ";

    // Clean mask and shared fields
    if ($invoice) {
        if (!empty($invoice->metadata->shared_settings)) {
            $shared_settings = $invoice->metadata->shared_settings;
            $_secret_fields = (array)(empty($shared_settings->masked) ? [] : $shared_settings->masked);
            $_shared_fields = (array)(empty($shared_settings->shared) ? [] : $shared_settings->shared);

            $shared_fields = "'" . implode("','", $_shared_fields) . "'";
            $mask_fields = "'" . implode("','", $_secret_fields) . "'";

            $queries = [];
            if (!empty($_shared_fields)) {
                // Empty shared options so they can always be taken from the master tenant
                $queries[] = "UPDATE `" . $options_table . "` SET `value`='' $where (`name` IN ($shared_fields))";
            }

            if (!empty($_secret_fields)) {
                // Empty secret fields
                $queries[] = "UPDATE `" . $options_table . "` SET `value`='' $where (`name` IN ($mask_fields))";
            }
        }
    }

    // Reset general sensitive options in the new template
    $sensitive_fields = get_option('perfex_saas_sensitive_options');
    if (empty($sensitive_fields)) {
        $sensitive_options = $CI->perfex_saas_model->sensitive_shared_options();
        $sensitive_fields = array_column($sensitive_options, 'key');
    } else {
        $sensitive_fields = (array) json_decode($sensitive_fields);
    }
    $sensitive_fields = "'" . implode("','", $sensitive_fields) . "'";
    $queries[] = "UPDATE `" . $options_table . "` SET `value`='' $where (`name` IN ($sensitive_fields) )";

    // Remove saas releated settings
    $queries[] = "DELETE FROM `" . $options_table . "` WHERE `name` LIKE 'perfex_saas%'";

    // Update company name
    $queries[] = "UPDATE `" . $options_table . "` SET `value`='$company->name' $where `name` = 'companyname'";

    // Remove SAAS email templates
    $queries[] = "DELETE FROM $emailtemplate_table $where `slug` LIKE 'company-instance%'";

    // Run all queries in a single transaction
    perfex_saas_raw_query($queries, $dsn_array, false, true);

    // Insert installation secured flag
    $flag_query = "INSERT INTO `" . $options_table . "` (`id`, `name`, `value`, `autoload`) VALUES (NULL, 'perfex_saas_installation_secured', 'true', '0')";
    perfex_saas_raw_query($flag_query, $dsn_array, false, true);
}

/**
 * Simulate modules installation for tenants. 
 * This is neccessary so that module installation data is seed properly into each tenants.
 *
 * @return void
 */
function perfex_saas_setup_modules_for_tenant($verbose = true)
{
    $CI = &get_instance();
    if (!perfex_saas_is_tenant()) throw new \Exception("Error Processing Request: Invalid context for perfex_saas_setup_modules_for_tenant", 1);

    $tenant = perfex_saas_tenant();
    $modules = perfex_saas_tenant_modules($tenant, false);
    if ($verbose)
        echo "Setting up modules for $tenant->slug<br/>";
    foreach ($modules as $key => $name) {
        try {
            if ($verbose) {
                echo "Start Running for: $name <br/>";
                echo "activating<br/>";
            }
            $CI->app_modules->activate($name);
            if ($verbose)
                echo "upgrading db <br/>";
            $CI->app_modules->upgrade_database($name);
            if ($CI->app_modules->new_version_available($name)) {
                if ($verbose)
                    echo "updating version<br/>";
                $CI->app_modules->update_to_new_version($name);
            }
            if ($verbose)
                echo "End Running for: $name <br/><br/>";
        } catch (\Throwable $th) {
            if ($verbose)
                echo "Error installing module $name:" . $th->getMessage();
            log_message('error', "Error installing module $name:" . $th->getMessage());
        }
    }

    // Deactive disabled modules
    $disabled_modules = array_diff(perfex_saas_tenant_modules($tenant, false, true, true), $modules);
    foreach ($disabled_modules as $key => $name) {
        try {
            if ($verbose) {
                echo "Start Running for: $name <br/>";
                echo "deactivating<br/>";
            }
            $CI->app_modules->deactivate($name);
            if ($verbose)
                echo "End Running for: $name <br/><br/>";
        } catch (\Throwable $th) {
            if ($verbose)
                echo "Error deactivating module $name:" . $th->getMessage();
            log_message('error', "Error deactivating module $name:" . $th->getMessage());
        }
    }
}

/**
 * Send domain request notification if need.
 * Notification wont be sent if the package is on auto approve.
 *
 * @param object $company
 * @param object $invoice
 * @return void
 */
function perfex_saas_send_customdomain_request_notice($company, $custom_domain, $package)
{
    // Notify supper admin on domain update
    $autoapprove = (int)($package->metadata->autoapprove_custom_domain ?? 0);
    if (!$autoapprove) {
        if ($custom_domain !== $company->custom_domain) {
            // Notify supper admin
            $notifiedUsers = [];
            $admin = perfex_saas_get_super_admin();
            $staffid = $admin->staffid;
            if (add_notification([
                'touserid' => $staffid,
                'description' => _l('perfex_saas_not_domain_request', $custom_domain),
                'link' => 'perfex_saas/companies/edit/' . $company->id,
                'additional_data' => serialize([$company->name])
            ])) {
                array_push($notifiedUsers, $staffid);
            }
            pusher_trigger_notification($notifiedUsers);
        }
    }
}

/**
 * Share package shared settings with the active current tenant.
 * 
 * This method will get master shared settings and inject into app instance.
 * It replaces the settings when it is empty on the instance or the instance has the masked value.
 *
 * @return void
 */
function perfex_saas_init_shared_options()
{
    if (perfex_saas_is_tenant()) {

        $CI = &get_instance();

        $tenant = perfex_saas_tenant();
        if (empty($tenant->package_invoice)) return; // wont share any settings

        $sharing_smtp_email = false;

        $instance_settings = $CI->app->get_options();

        $package_shared_fields = [];
        $enforced_shared_fields = array_merge(PERFEX_SAAS_ENFORCED_SHARED_FIELDS, (array) ($tenant->package_invoice->metadata->shared_settings->enforced ?? []));

        //return if no shared fields
        if (!empty($tenant->package_invoice->metadata->shared_settings->shared)) {

            $package_shared_fields = (array)$tenant->package_invoice->metadata->shared_settings->shared;
        }

        $shared_fields = array_unique(array_merge($package_shared_fields, $enforced_shared_fields));
        $shared_master_settings = perfex_saas_master_shared_settings($shared_fields);

        foreach ($shared_master_settings as $setting) {

            $field_name = $setting->name;
            $master_value = $setting->value; // Master value
            $tenant_value = $instance_settings[$field_name] ?? $CI->app->get_option($field_name);
            $should_force = in_array($field_name, $enforced_shared_fields) && $tenant_value !== $master_value;

            // Override if empty or value is the masked value of the master settings
            if (empty($tenant_value) || perfex_saas_get_starred_string($master_value) == $tenant_value || $should_force) {
                if ($field_name === 'smtp_email')
                    $sharing_smtp_email = true;

                $instance_settings[$field_name] = $master_value;
            }
        }

        // Always set this to 0 to hide menu from users
        $instance_settings['show_help_on_setup_menu'] = 0;

        // Ensure the language is always set.
        if (!isset($instance_settings['active_language']) || empty($instance_settings['active_language'])) {
            $instance_settings['active_language'] = 'english';
        }

        // Use ReflectionClass to update the private app property
        $reflectionClass = new ReflectionClass($CI->app);
        $property = $reflectionClass->getProperty('options');
        $property->setAccessible(true);
        $property->setValue($CI->app, $instance_settings);

        /**
         * Email config use options from database, the options are used to initalize email library i.e $CI->email
         * We need to force reload the config email now since the setting option is overriden,
         * then re-instantiate the email to use the latest email config.
         * As of Perfex 3.0.6 30-Jul-2023
         */
        $config = [];
        require(APPPATH . 'config/email.php');
        foreach ($config as $key => $value) {
            $CI->config->set_item($key, $value);
        }
        $CI->email->initialize($config);

        // If sharing smpt email, we want to set the reply_to email and from email to the tenant primary contact email address.
        if ($sharing_smtp_email) {

            hooks()->add_filter('after_parse_email_template_message', function ($template) {
                $tenant_contact_email = perfex_saas_tenant()->package_invoice->email ?? '';
                if (!empty($tenant_contact_email)) {
                    $template->reply_to = empty($template->reply_to) ? $tenant_contact_email : $template->reply_to;
                    $template->fromemail = empty($template->fromemail) ? $tenant_contact_email : $template->fromemail;
                }
                return $template;
            });
        }
    }
}

/**
 * Mask secret values in the contents.
 *
 * * This function mask the field value marked as secret on shared setting list.
 * It attempt to prevent revealing of the share fields with sensitive value.
 * 
 * @param string $contents   The input contents.
 * @return string            The contents with masked secret values.
 */
function perfex_saas_mask_secret_values(string $contents)
{
    $tenant = perfex_saas_tenant();
    $CI = &get_instance();

    // If masked fields are not specified in the package metadata, return the contents as-is
    if (empty($tenant->package_invoice->metadata->shared_settings->masked)) {
        return $contents;
    }

    $package = $tenant->package_invoice;
    $masked_fields = (array) $package->metadata->shared_settings->masked;

    // Get shared secret master settings based on the masked fields
    $shared_secret_master_settings = perfex_saas_master_shared_settings($masked_fields);

    foreach ($shared_secret_master_settings as $row) {
        $value = $row->value;
        if (($decrypted_value = $CI->encryption->decrypt($row->value)) !== false) {
            // Replace the decrypted value with a starred version in the contents
            $value = $decrypted_value;
        }

        // Replace the value with a starred version in the contents
        // @todo Improve match with only wrap words
        if (!in_array($value, ['0', '1', 'yes', 'no', '-']) && $value !== $tenant->slug && strlen($row->setting_value) > 2)
            $contents = str_ireplace_whole_word($value, perfex_saas_get_starred_string($value), $contents);
    }

    return $contents;
}

/**
 * Get shared secret master settings.
 *
 * @param array $fields     The masked fields.
 * @return array            The shared secret master settings.
 */
function perfex_saas_master_shared_settings(array $fields)
{
    return perfex_saas_get_options($fields, false);
}

/**
 * Get a starred version of a string.
 * 
 * Masked part of string with the provided mask
 *
 * @param string $str          The input string.
 * @param int    $prefix_len   The length of the prefix to keep as-is.
 * @param int    $suffix_len   The length of the suffix to keep as-is.
 * @param string $mask         The character to use for stars.
 * @return string              The starred version of the string.
 */
function perfex_saas_get_starred_string($str, $prefix_len = 1, $suffix_len = 1, $mask = '*')
{
    if (empty($str)) {
        return $str;
    }

    $len = strlen($str);

    // Ensure prefix length is within a reasonable range
    if ($prefix_len > ($len / 2)) {
        $prefix_len = (int) ($len / 3);
    }

    // Ensure suffix length is within a reasonable range
    if ($suffix_len > ($len / 2)) {
        $suffix_len = (int) ($len / 3);
    }

    // Get the prefix and suffix substrings
    $prefix = substr($str, 0, $prefix_len);
    $suffix = $suffix_len > 0 ? substr($str, -1 * $suffix_len) : '';

    $repeat = $len - ($prefix_len + $suffix_len);

    // Create the starred string by repeating the star character
    return $prefix . str_repeat($mask, $repeat) . $suffix;
}


/**
 * Impersonate a tenant instance.
 *
 * This function give you the ability to run some come (callback) in the context of the company instance.
 * Its advice to call this function at the end of the flow to ensure safety.
 * 
 * @param object   $company   The company object to impersonate.
 * @param callable $callback  The callback function to execute while impersonating the instance.
 * @return mixed              The result of the callback function.
 * @throws Exception         Throws an exception if there are any errors during impersonation.
 */
function perfex_saas_impersonate_instance($company, $callback)
{
    // Only allow impersonation from the master instance
    if (perfex_saas_is_tenant()) {
        throw new \Exception(_l('perfex_saas_can_not_impersonate_within_another_slave_instnace'), 1);
    }

    if (!is_callable($callback)) {
        throw new \Exception(_l('perfex_saas_invalid_callback_passed_to_impersonate'), 1);
    }

    $CI = &get_instance();
    $OLD_DB = $CI->db;
    $slug = $company->slug;

    // Attempt to define necessary variables to imitate a normal tenant instance context

    // Check if impersonation in the current session is unique to a company
    if (defined('PERFEX_SAAS_TENANT_SLUG') && PERFEX_SAAS_TENANT_SLUG !== $slug) {
        throw new \Exception("Error Processing Request: impersonation in a session must be unique i.e for only a company only", 1);
    }

    $tenant_dbprefix = perfex_saas_tenant_db_prefix($slug);

    defined('PERFEX_SAAS_TENANT_BASE_URL') or define('PERFEX_SAAS_TENANT_BASE_URL', perfex_saas_tenant_base_url($company));
    defined('PERFEX_SAAS_TENANT_SLUG') or define('PERFEX_SAAS_TENANT_SLUG', $slug);
    define('APP_DB_PREFIX', $tenant_dbprefix);
    $GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant'] = $company;



    $dsn = perfex_saas_get_company_dsn($company);
    $db = perfex_saas_load_ci_db_from_dsn($dsn, ['dbprefix' => $tenant_dbprefix]);
    if ($db === FALSE) {
        throw new \Exception(_l('perfex_saas_error_loading_instance_datacenter_during_impersonate'), 1);
    }
    $CI->db = $db;

    // Test if impersonation works by running a query
    $test_sql = $CI->db->select()->from($tenant_dbprefix . 'staff')->get_compiled_select();
    $test_sql = perfex_saas_db_query($test_sql);

    if (
        perfex_saas_tenant()->slug !== $slug ||
        !stripos($test_sql, $tenant_dbprefix . 'staff')
    ) {
        throw new \Exception(_l('perfex_saas_error_ensuring_impersonation_works'), 1);
    }

    // Call user callback
    $callback_result = call_user_func($callback);

    // End impersonation by unsetting the tenant constant
    unset($GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant']);

    // Confirm the end of impersonation
    if (perfex_saas_tenant_slug()) {
        throw new \Exception(_l('perfex_saas_error_ending_tenant_impersonation'), 1);
    }

    $CI->db = $OLD_DB;

    return $callback_result;
}

/**
 * Perform cron tasks for the Saas application.
 * This method should only be run from the master instance.
 * 
 * Run cron for each instance in a resumeable way so that it can be resumed from where it left off when timeout occurs
 */
function perfex_saas_cron()
{
    $CI = &get_instance();
    $CI->perfex_saas_cron_model->init();
}

/**
 * Perform auto-subscription for clients.
 * This method is triggered when a client is logged in and has not subscription or company.
 */
function perfex_saas_autosubscribe()
{
    if (is_client_logged_in()) {

        $CI = &get_instance();
        $package_slug = $CI->session->ps_plan ?? '';

        if (get_option('perfex_saas_enable_auto_trial') == '1' || !empty($package_slug)) {

            // Get invoice
            if (!str_starts_with($CI->uri->uri_string(), 'clients/packages/')) {

                // Ensure the client has not existing subscription
                $invoice = $CI->perfex_saas_model->get_company_invoice(get_client_user_id(), ['include_cancelled' => true, 'skip_children' => true]);
                if (!isset($invoice->id)) {

                    // Check if we have selected plan in session
                    if (empty($package_slug)) {
                        // Get default package
                        $CI->db->where('is_default', 1);
                        $default_package = $CI->perfex_saas_model->packages();
                        $package_slug = empty($default_package) ? '' : $default_package[0]->slug;
                    };

                    // Subscribe
                    if (!empty($package_slug)) {
                        redirect(site_url("clients/packages/$package_slug/select"));
                        exit();
                    }
                }
            }
        }
    }
}

/**
 * Generate a form label hint.
 *
 * @param string $hint_lang_key  The language key for the hint text.
 * @param string|string[] $params The language key sprint_f variables.
 * @return string                The HTML code for the form label hint.
 */
function perfex_saas_form_label_hint($hint_lang_key, $params = null)
{
    return '<span class="tw-ml-2" data-toggle="tooltip" data-title="' . _l($hint_lang_key, $params) . '"><i class="fa fa-question-circle"></i></span>';
}

/**
 * Remove directory recursively including hidder directories and files.
 * This is preferable to perfex delete_dir function as that does not handle hidden directories well.
 *
 * @param      string  $target  The directory to remove
 * @return     bool
 */
function perfex_saas_remove_dir($target)
{
    try {
        if (is_dir($target)) {
            $dir = new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS);
            foreach (new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST) as $filename => $file) {
                if (is_file($filename)) {
                    unlink($filename);
                } else {
                    perfex_saas_remove_dir($filename);
                }
            }
            return rmdir($target); // Now remove target folder
        }
    } catch (\Exception $e) {
    }
    return false;
}


/**
 * Get the path and url of the theme.
 * Path first the http url.
 *
 * @return array Path first the http url.
 */
function perfex_saas_get_theme_path_url()
{
    $path = get_instance()->app->get_media_folder() . '/public/landingpage/themes';
    $themePath = FCPATH . $path;
    $themeUrl = base_url($path);
    return [$themePath, $themeUrl];
}

/**
 * Get all html pages in the landing pages theme and custom theme folder.
 * The use can select which page to use as the landing page.
 *
 * @return array
 */
function perfex_saas_get_landing_pages()
{
    $pages = [];
    list($themePath, $themeUrl) = perfex_saas_get_theme_path_url();

    $htmlFiles = [];
    $patterns = [$themePath . '/*/*.html', $themePath . '/*.html'];
    // Get all files matching the patterns
    foreach ($patterns as $pattern) {
        $htmlFiles = array_merge($htmlFiles, glob($pattern));
    }

    $activeTheme = get_option('perfex_saas_landing_page_theme');
    $activeThemeIndex = 0;
    foreach ($htmlFiles as $index => $file) {

        if (stripos($file, 'new-page-blank-template.html') !== false) continue; //skip template files
        $pathInfo = pathinfo($file);
        $extension = $pathInfo['extension'];
        if ($extension !== 'html') continue;

        $basePath = str_ireplace($themePath, '', $pathInfo['dirname']);
        $realFilename = $filename = $pathInfo['filename'];
        $folder = preg_replace('@/.+?$@', '', $basePath);
        $subfolder = preg_replace('@^.+?/@', '', $pathInfo['dirname']);
        if ($subfolder) {
            if ($filename == 'index')
                $filename = basename($subfolder);
            else if ($folder !== basename($subfolder))
                $filename = basename($subfolder) . '/' . $filename;
        }


        $url = str_ireplace($themePath, $themeUrl, $pathInfo['dirname'] . '/' . $pathInfo['basename']);

        $page = [
            "name" => ucfirst($filename),
            "file" => str_ireplace($themePath, '', $file),
            "title" => ucfirst($filename),
            "url" => $url,
            "folder" => empty($folder) ? 'themes' : $folder,
            "base_path_url" => str_ireplace(basename($realFilename) . '.' . $extension, '', $url)
        ];
        $pages[$index] = $page;

        if ($activeTheme == $page['file'])
            $activeThemeIndex = $index;
    }

    if ($activeThemeIndex) {
        // sort make acitve theme first one 
        $activeTheme = $pages[$activeThemeIndex];
        unset($pages[$activeThemeIndex]);
        $pages = array_merge([$activeTheme], $pages);
    }

    return $pages;
}

/**
 * Load CI DB instance from dsn array
 *
 * @param array $dsn
 * @param array $extra Extra configuration options i.e dbprefix e.t.c
 * @return mixed
 */
function perfex_saas_load_ci_db_from_dsn($dsn, $extra = [])
{

    $base_config = [
        'dbdriver'     => APP_DB_DRIVER,
        'char_set'     => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
        'dbcollat'     => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
    ];

    $config = array_merge($base_config, [
        'hostname'     => $dsn['host'],
        'username'     => $dsn['user'],
        'password'     => $dsn['password'],
        'database'     => $dsn['dbname'],
    ], $extra);

    if (!isset($config['dbprefix'])) throw new \Exception("DB Prefix required for this configuration", 1);

    $CI = &get_instance();
    return $CI->load->database($config, TRUE);
}

/**
 * Check if single price mode pricing is activated or not
 *
 * @return bool
 */
function perfex_saas_is_single_package_mode()
{
    return get_option('perfex_saas_enable_single_package_mode') === '1';
}

/**
 * Generate a URL for a module's asset (e.g., JavaScript or CSS file) with a version number.
 *
 * @param string $asset The asset filename or path.
 * @return string The URL to the asset with a version number appended.
 */
function perfex_saas_asset_url($asset)
{
    // Construct the URL for the asset with a version number
    return module_dir_url(PERFEX_SAAS_MODULE_NAME, $asset . '?v=' . PERFEX_SAAS_VERSION_NUMBER);
}

/**
 * Trigger module activation. For whole tenant or a particular tenant.
 *
 * @param string $module_name
 * @param string $tenant_slug
 * @return void
 */
function perfex_saas_trigger_module_install($module_name, $tenant_slug = '')
{
    // set module install requirement trigger
    $key = 'new_module_activation';
    if (!empty($tenant_slug))
        $key = $key . '_' . $tenant_slug;

    $settings = ["$key" => '1'];
    get_instance()->perfex_saas_cron_model->save_settings($settings);
}



/**
 * Generate a one time http auth code
 *
 * @param mixed $clientid
 * @return string|null
 */
function generate_magic_auth_code($clientid)
{
    if (empty($clientid)) return null;

    // Generate a random authentication code
    $auth_code = implode('|~|', [random_int(1111, 99999), time(), $clientid]);
    $auth_code = get_instance()->encryption->encrypt($auth_code);

    // Save the authentication code in the client's metadata
    if (perfex_saas_get_or_save_client_metadata($clientid, ['magic_code' => $auth_code]))
        return $auth_code;

    return null;
}


/**
 * Function to autologin as admin into the active tenant.
 * This should be called from instance context or impersonation
 *
 * @return bool
 */
function perfex_saas_tenant_admin_autologin()
{
    if (!perfex_saas_is_tenant()) throw new \Exception("This function can only be used from an instance context", 1);

    $CI = &get_instance();
    $CI->load->helper('cookie');

    $staff = $CI->db->select('staffid')->where('admin', 1)->get(db_prefix() . 'staff')->row();

    if (!$staff)
        perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied'), _l('perfex_saas_instance_does_not_have_any_staff'), 500);

    $user_id = $staff->staffid;

    $cookie_path = '/';
    $tenant_url_sig = perfex_saas_tenant_url_signature(perfex_saas_tenant_slug());
    if (stripos(PERFEX_SAAS_TENANT_BASE_URL, $tenant_url_sig))
        $cookie_path = '/' . $tenant_url_sig . '/';

    // Harness the perfex inbuilt auto login
    // @Ref: models/Authentication_model.php
    $staff = true;
    $key = substr(md5(uniqid(rand() . get_cookie($CI->config->item('sess_cookie_name')))), 0, 16);
    $CI->user_autologin->delete($user_id, $key, $staff);
    if ($CI->user_autologin->set($user_id, md5($key), $staff)) {
        set_cookie([
            'name'  => 'autologin',
            'value' => serialize([
                'user_id' => $user_id,
                'key'     => $key,
            ]),
            'expire' => 5000, // 5secs
            'path' => $cookie_path,
            'httponly' => true,
        ]);
        return true;
    }
}


/**
 * Calculate the number of days left until a specified time.
 *
 * @param string $time The time to compare to (in a valid DateTime format).
 *
 * @return int The number of days left. Returns 1 if there are minutes left, and 0 if less than a minute remains.
 */
function perfex_saas_get_days_until($time)
{
    $factory = \Carbon\Carbon::parse($time);

    $now = \Carbon\Carbon::parse();
    if ($now->greaterThan($factory)) return 0;

    $days_left = (int)$factory->diffInDays();

    // Ensure the lower limit
    if ($days_left === 0 && $factory->diffInMinutes() > 0) {
        $days_left = 1;
    }

    return $days_left;
}
