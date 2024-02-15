<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This file contain core helper functions for the module.
 * All core function for boostraping are defined here along side important constant.
 * CI get_instance() or any other core function of codeigniter as app are not fully loaded and should be avoided in this helper
 */

require(__DIR__ . '/../config/constants.php');
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/perfex_saas_php8_polyfill_helper.php');
require(__DIR__ . '/perfex_saas_storage_helper.php');

use PHPSQLParser\PHPSQLParser;

/**
 * Initializes the Perfex SAAS module.
 * Sets up the SAAS environment based on the requested tenant.
 *
 * @return void
 */
function perfex_saas_init()
{
    try {
        if (isset($_SERVER['REQUEST_URI'])) {

            $request_uri = $_SERVER['REQUEST_URI'];
            $host  = perfex_saas_http_host($_SERVER);

            // Can identify tenant by url segment and or host (subdomain or cname/custom domain)
            $tenant_info = perfex_saas_get_tenant_info_by_http($request_uri, $host);
            $options = perfex_saas_get_options(['perfex_saas_enable_client_bridge', 'perfex_saas_enable_cross_domain_bridge', 'perfex_saas_enable_instance_switch'], true);

            if ($tenant_info) {
                $tenant_path_id = $tenant_info['path_id'];
                $tenant_id = $tenant_info['slug'];
                $tenancy_access_mode = $tenant_info['mode'];
                $field =  $tenancy_access_mode == PERFEX_SAAS_TENANT_MODE_DOMAIN ? 'custom_domain' : 'slug'; // path and subdomain mode use slug for search

                if ($field == 'custom_domain') {
                    $tenant_id = $tenant_info['custom_domain'];
                    $field = 'custom_domain';
                }

                // Determine the tenant base URL
                $tenant_base_url = perfex_saas_url_origin($_SERVER) . '/';
                if (!empty($tenant_path_id)) {
                    $tenant_base_url .= "$tenant_path_id/";
                }

                if (!$tenant_id)
                    perfex_saas_show_tenant_error("Invalid Tenant", "We could not find the requested instance.", 404);

                // Check if tenant exists
                $tenant = perfex_saas_search_tenant_by_field($field, $tenant_id);
                if (!$tenant) {
                    perfex_saas_show_tenant_error("Invalid Tenant", "The requested tenant does not exist.", 404);
                }

                // Get package and invoice details
                $package_invoice = perfex_saas_get_client_package_invoice($tenant->clientid);

                // Decode metadata and package/invoice details
                $tenant->metadata = (object)json_decode($tenant->metadata);
                if ($package_invoice) {

                    $tenant->package_invoice = $package_invoice;
                }

                $tenant->saas_options = $options;

                // Add the identity mode
                $tenant->http_identification_type = $tenancy_access_mode;

                // @todo Determine if we should check package for permission to use custom domain if the tenant is recognized by custom domain.

                // Set global variable for the tenant
                $GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant'] = $tenant;

                // Tenant is gotten from request uri match i.e tenant_slug/ps
                if (!empty($tenant_path_id)) {
                    // Replace repeated $id segment
                    if (stripos($request_uri, "/$tenant_path_id/$tenant_path_id") !== false) {
                        $_SERVER['REQUEST_URI'] = str_ireplace("/$tenant_path_id/$tenant_path_id", "/$tenant_path_id", $request_uri);
                        if (empty($_POST)) {
                            $url = perfex_saas_full_url($_SERVER);
                            header("Location: $url");
                            exit;
                        }
                    }

                    // Serve static files
                    if (!empty($_SERVER['QUERY_STRING']))
                        $request_uri = str_ireplace('?' . $_SERVER['QUERY_STRING'], '', $request_uri);

                    if (stripos($request_uri, ".") && stripos($request_uri, "://") === false && stripos($request_uri, ".php") === false && stripos($request_uri, $tenant_path_id . '/' . PERFEX_SAAS_UPLOAD_BASE_DIR) === false) {
                        $url = str_ireplace("/$tenant_path_id", '', perfex_saas_full_url($_SERVER));
                        header("Location: $url");
                        exit;
                    }
                }

                // Define constants for the tenants. If any of these have been defined earlier, an error should be thrown,
                // as it is important that the user of the module has not defined these custom constants.
                define('PERFEX_SAAS_TENANT_SLUG', $tenant->slug);
                define('PERFEX_SAAS_TENANT_BASE_URL', $tenant_base_url);
                define('APP_SESSION_COOKIE_NAME', $tenant->slug . '_sp_session');
                define('APP_COOKIE_PREFIX', $tenant->slug);
                define('APP_CSRF_TOKEN_NAME', $tenant->slug . '_csrf_token_name');
                define('APP_CSRF_COOKIE_NAME', $tenant->slug . '_csrf_cookie_name');
            }
        }

        // If using custom domain it neccessary we set cookie same site to none 
        // for tenant brige/cross login and iframe loading from other domain (i.e custom domain) to work
        $cookie_same_site =   defined('APP_SESSION_COOKIE_SAME_SITE_DEFAULT') ? APP_SESSION_COOKIE_SAME_SITE_DEFAULT : 'Lax';
        if (!$tenant_info && ($options['perfex_saas_enable_cross_domain_bridge'] ?? '0') == '1') {

            $is_admin = str_starts_with(ltrim($request_uri, '/'), 'admin') !== false;
            $is_in_local = perfex_saas_host_is_local(perfex_saas_get_saas_default_host(), true);
            if (!$is_admin && !$is_in_local) {
                $cookie_same_site = 'none';
                defined('APP_COOKIE_SECURE') or define('APP_COOKIE_SECURE', true);
                defined('APP_COOKIE_HTTPONLY') or define('APP_COOKIE_HTTPONLY', true);
            }
        }
        // Ensure we always have APP_SESSION_COOKIE_SAME_SITE defined is tenant or not
        defined('APP_SESSION_COOKIE_SAME_SITE') or define('APP_SESSION_COOKIE_SAME_SITE', $cookie_same_site);
    } catch (\Throwable $th) {
        // Handle any exceptions that occur during initialization
        perfex_saas_show_tenant_error("Initialization Error", $th->getMessage(), 500);
    }

    // Define APP_BASE_URL based on the tenant's base URL, fallback to the default base URL if not available
    define('APP_BASE_URL', defined('PERFEX_SAAS_TENANT_BASE_URL') ? PERFEX_SAAS_TENANT_BASE_URL : APP_BASE_URL_DEFAULT);
}


/**
 * Get tenant information based on the provided HTTP request URI and host.
 * If the returned array contain non empty slug, then tenant match/search should be made by 'slug' field otherwise 'custom_domain'.
 * The returned array also contain 'mode' key which can either be 'domain' - custom domain, 'subdomain' or 'path', depending on how the 
 * tenant is recognized.
 *
 * @param string $request_uri The request URI.
 * @param string $host The HTTP host.
 * @return array|false Tenant information if found, otherwise false.
 * @throws Exception If invalid input is provided or no tenant is found.
 */
function perfex_saas_get_tenant_info_by_http($request_uri, $host)
{
    // Validate and sanitize input
    if (!is_string($request_uri) || !is_string($host)) {
        throw new Exception('Invalid input provided.');
    }

    $tenant_info = false;
    $mode = PERFEX_SAAS_TENANT_MODE_DOMAIN;

    // Try by subdomain or domain first before url match
    if (!empty($host) && !perfex_saas_host_is_local($host)) {
        $tenant_info = perfex_saas_get_tenant_info_by_host($host);
        if (!empty($tenant_info['slug']))
            $mode = PERFEX_SAAS_TENANT_MODE_SUBDOMAIN;
    }

    if (!$tenant_info) {
        // Get tenant information from request URI
        $tenant_info = perfex_saas_get_tenant_info_by_request_uri($request_uri);
        if ($tenant_info)
            $mode = PERFEX_SAAS_TENANT_MODE_PATH;
    }

    if (!$tenant_info) {
        return false;
    }

    return [
        'path_id' => $tenant_info['path_id'] ?? '',
        'slug' => $tenant_info['slug'] ?? '',
        'custom_domain' => $tenant_info['custom_domain'] ?? '',
        'mode' => $mode
    ];
}


/**
 * Extracts the tenant information from the request URI.
 *
 * @param string $request_uri The request URI.
 * @return array|false The tenant information array or false if not found.
 * @todo Support subdirectory installation of perfex
 */
function perfex_saas_get_tenant_info_by_request_uri($request_uri)
{
    $saas_url_marker = '/' . PERFEX_SAAS_ROUTE_ID;
    // Should match either /tenant/ps/* or /tenant/ps
    $saas_url_id_pos = stripos($request_uri, $saas_url_marker . '/');

    if ($saas_url_id_pos === false && str_ends_with($request_uri, $saas_url_marker))
        $saas_url_id_pos = stripos($request_uri, $saas_url_marker);

    if ($saas_url_id_pos !== false) {

        // Extract tenant slug and id
        $tenant_slug = substr($request_uri, 1, $saas_url_id_pos - 1);
        // Find the position of the last slash
        $lastSlashPos = strrpos($tenant_slug, '/');
        // Extract the substring after the last slash
        if ($lastSlashPos !== false)
            $tenant_slug = substr($tenant_slug, $lastSlashPos + 1);

        // Get the directory in case the perfex is installed in subfolder
        $base_url_path = parse_url(APP_BASE_URL_DEFAULT);
        if (!isset($base_url_path['path'])) {
            throw new \Exception("Your base url in app/app-config.php should end with trailing slash !", 1);
        }

        $base_url_path = $base_url_path['path'];

        if (!empty($tenant_slug) && str_starts_with($request_uri, $base_url_path . $tenant_slug . $saas_url_marker)) {

            $id = trim($base_url_path . $tenant_slug . $saas_url_marker, '/'); // i.e. tenantslug/ps or dir/tenantslug/ps

            return [
                'slug' => $tenant_slug,
                'path_id' => $id,
            ];
        }
    }

    return false;
}

/**
 * Get tenant information based on the provided host.
 * Returned array contain either of non empty 'custom_domain' and 'slug' but not both.
 *
 * @param string $http_host The HTTP host.
 * @return array|false Tenant information if found or false is on same domain with saas base domain
 * @throws Exception If no tenant is found or an invalid subdomain is detected.
 */
function perfex_saas_get_tenant_info_by_host($http_host)
{
    // Validate input
    if (!filter_var($http_host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || stripos($http_host, '/') !== false) {
        throw new Exception('Invalid HTTP host provided: ' . $http_host);
    }

    // Get the default host and the URL host
    $app_host = perfex_saas_get_saas_default_host();
    $host = $http_host;
    $tenant_slug = '';

    if (str_starts_with($app_host, 'www.')) {
        $app_host = str_ireplace('www.', '', $app_host);
    }

    if (str_starts_with($host, 'www.')) {
        $host = str_ireplace('www.', '', $host);
    }

    if ($app_host === $host) {
        //throw new Exception('No tenant found for the provided host.');
        return false;
    }

    // Check for subdomain
    if (str_ends_with($host, $app_host)) {
        $subdomain = trim(str_ireplace($app_host, '', $host), '.');

        if (empty($subdomain) || stripos($subdomain, '.') !== false) {
            throw new Exception('Invalid subdomain detected.');
        }

        $tenant_slug = $subdomain; // Assign the subdomain as the tenant slug
        $host = ""; // Reset the host value
    }

    return [
        'custom_domain' => $host, // Custom domain (without "www")
        'slug' => $tenant_slug // Tenant slug
    ];
}

/**
 * Get the default app base url host. Use the address for installation before setting up SaaS.
 *
 * @return string
 */
function perfex_saas_get_saas_default_host()
{
    return parse_url(APP_BASE_URL_DEFAULT, PHP_URL_HOST);
}

/**
 * Detect if the provided host is a localhost
 *
 * @param string $host
 * @param bool $strict Determine if to include some test extension i.e .test .dev e.t.c
 * @return bool
 */
function perfex_saas_host_is_local($host, $strict = false)
{
    $localhosts = ['localhost', '127.0.0.1', '::1'];
    foreach ($localhosts as $localhost) {
        if ($host === $localhost || str_starts_with($host, $localhost))
            return true;
    }

    if ($strict && str_ends_with($host, '.test')) return true;

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return true;
    }

    return false;
}

/**
 * Retrieve a tenant by a certain db column/field in companies table
 *
 * @param string $field The db column name
 * @param string $value The value to search for
 * @return object|null The tenant object if found, null otherwise
 */
function perfex_saas_search_tenant_by_field($field, $value)
{
    $tenant_table = perfex_saas_table('companies');
    $query = "SELECT `slug`, `name`, `dsn`, `clientid`, `metadata`, `status` FROM `$tenant_table` WHERE `$field` = :value";
    $parameters = [':value' => $value];

    return perfex_saas_raw_query_row($query, [], true, true, $parameters);
}

/**
 * Retrieve the package invoice for a client
 *
 * @param int $clientid The client ID
 * @return object|null The package invoice object if found, null otherwise
 * @todo Merger this function with Perfex_saas_model::get_company_invoice() and unify i.e DRY
 */
function perfex_saas_get_client_package_invoice($clientid)
{

    $dbprefix = perfex_saas_master_db_prefix();
    $invoice_table = $dbprefix . 'invoices';
    $package_table = perfex_saas_table('packages');
    $client_table = $dbprefix . 'clients';
    $contact_table = $dbprefix . 'contacts';
    $package_column = perfex_saas_column('packageid');
    $clientmeta_table = perfex_saas_table('client_metadata');

    $q = "SELECT 
            `$invoice_table`.*,
            `$package_table`.`status` as `package_status`, `slug`, 
            `firstname`, `lastname`, `email`,
            `clientid`, `name`, `description`, `price`, `bill_interval`, `is_default`, 
            `is_private`, `db_scheme`, `db_pools`, `modules`, `metadata`, `trial_period` 
        FROM `$invoice_table` 
            INNER JOIN `$package_table` ON `$package_table`.`id` = `$package_column` 
            INNER JOIN `$client_table` ON `$client_table`.`userid` = `$invoice_table`.`clientid` 
            LEFT JOIN `$contact_table` ON `$contact_table`.`userid` = `$client_table`.`userid` AND `is_primary`='1' 
        WHERE `recurring` > '0' AND `$package_column` IS NOT NULL AND `clientid`=:clientid;";

    $parameters = [':clientid' => (int)$clientid];

    $package_invoice = perfex_saas_raw_query_row($q, [], true, true, $parameters);
    // Decode package/invoice details
    if ($package_invoice) {

        // check if not upaid/overdue/partialy paid child invoice
        $unpaid_child = perfex_saas_raw_query_row("SELECT * FROM `$invoice_table` WHERE `is_recurring_from` = '$package_invoice->id' AND `status` < 5 AND `status` != 2", [], true);
        if ($unpaid_child) {
            $package_invoice = (object)array_merge((array)$package_invoice, (array)$unpaid_child);
        }

        if (!empty($package_invoice->metadata)) {
            $package_invoice->metadata = json_decode($package_invoice->metadata);
        }

        if (!empty($package_invoice->modules)) {
            $package_invoice->modules = json_decode($package_invoice->modules, true);
        }

        // Add invoice customization
        $package_invoice->custom_limits = (object)[];
        $package_invoice->purchased_modules = (object)[];
        $client_metadata = perfex_saas_raw_query_row("SELECT * FROM `$clientmeta_table` WHERE `clientid` = $package_invoice->clientid", [], true);
        if (!empty($client_metadata->metadata)) {
            $package_invoice->custom_limits = (object)(json_decode($client_metadata->metadata)->custom_limits ?? []);
            $package_invoice->purchased_modules = (object)(json_decode($client_metadata->metadata)->purchased_modules ?? []);
        }
    }
    return $package_invoice;
}


/**##################################################################################################################***
 *                                                                                                                      *
 *                                               Common tenant helpers methods                                          *
 *                                                                                                                      *
 ***##################################################################################################################**/

if (!function_exists('dd')) {
    function dd()
    {
        var_dump(func_get_args());
        exit();
    }
}

/**
 * Function to generate name for instance db.
 * Add unique signature to DB created by the saas
 *
 * @param string $db
 * @throws Exception    When the length of the db will be higher than 64 characters.
 * @return string
 */
function perfex_saas_db($db)
{
    $db = PERFEX_SAAS_MODULE_NAME . '_db_' . $db;

    // Convert slug to lowercase
    $db = strtolower($db);

    // Replace non-alphanumeric characters with underscore
    $db = preg_replace('/[^a-z0-9]+/', '_', $db);

    // Remove leading digits or underscores
    $db = preg_replace('/^[0-9_]+/', '', $db);

    // Remove leading and trailing underscores
    $db = trim($db, '_');

    // throw error when length is above 64 characters (database name limit)
    if (strlen($db) > 64) throw new \Exception("Database name provided has exceed the 64 character limit: $db", 1);

    return $db;
}

/**
 * Method to prefix saas table names
 *
 * @param string $table
 * @return string
 */
function perfex_saas_table($table)
{
    $db_prefix = perfex_saas_master_db_prefix();

    return $db_prefix . PERFEX_SAAS_MODULE_NAME . '_' . $table;
}


/**
 * Method to generate perfex saas column name for perfex tables
 *
 * @param string $column
 * @return string
 */
function perfex_saas_column($column)
{
    return PERFEX_SAAS_MODULE_NAME . '_' . $column;
}


/**
 * Function to get master slug
 *
 * @return string
 */
function perfex_saas_master_tenant_slug()
{
    return 'master';
}

/**
 * check is request is instance request or saas module
 *
 * @return     bool
 */
function perfex_saas_is_tenant()
{
    return defined('PERFEX_SAAS_TENANT_BASE_URL') && defined('PERFEX_SAAS_TENANT_SLUG') && !empty($GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant']);
}


/**
 * Get the active tenant
 * 
 * @return     object|false
 * 
 * Returned object can have 'package_invoice' and object contain bot property of invoice and the package together.
 */
function perfex_saas_tenant()
{
    if (!perfex_saas_is_tenant()) return false;

    $tenant = (object)$GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant'];

    return $tenant;
}

/**
 * Check if tenant may use a particular feature or settings
 *
 * @param string $permission
 * @return bool
 */
function perfex_saas_tenant_is_enabled($permission)
{
    $permission = 'perfex_saas_enable_' . $permission;
    $options = (array)(perfex_saas_tenant()->saas_options ?? []);
    if (isset($options[$permission]))
        return $options[$permission] === 'yes' || ((int)$options[$permission]) === 1;

    return false;
}


/**
 * Get the active tenant slug
 *
 * @return     string|false
 */
function perfex_saas_tenant_slug()
{
    if (!perfex_saas_is_tenant()) return false;

    return defined('PERFEX_SAAS_TENANT_SLUG') ? PERFEX_SAAS_TENANT_SLUG : false;
}

/**
 * Get the database prefix for master instance.
 *
 * If the function `db_prefix()` exists, it will be used to retrieve the database prefix.
 * Otherwise, it will fallback to the default prefix 'tbl'.
 *
 * @return string The database prefix for Perfex SAAS custom tables.
 */
function perfex_saas_master_db_prefix()
{
    if (function_exists('db_prefix') && !defined('APP_DB_PREFIX'))
        return db_prefix();
    return 'tbl';
}

/**
 * Return tenant add with master db prefix
 *
 * @param string $slug
 * @param string $table
 * @return string
 */
function perfex_saas_tenant_db_prefix($slug, $table = '')
{
    $master_prefix = perfex_saas_master_db_prefix();
    $suffix = $master_prefix;
    if (!empty($table)) {
        $suffix = str_starts_with($table, $master_prefix) ? $table : $master_prefix . $table;
    }

    return perfex_saas_str_to_valid_table_name($slug) . '_' . $suffix;
}

/**
 * Convert string to table friendly name
 *
 * @param string $input_string
 * @return string
 * @throws Exception
 */
function perfex_saas_str_to_valid_table_name($input_string)
{
    // Remove any non-alphanumeric characters except underscores
    $clean_string = preg_replace('/[^a-zA-Z0-9_]/', '_', $input_string);

    // Remove leading digits or underscores
    $clean_string = preg_replace('/^[0-9_]+/', '', $clean_string);

    $clean_string = trim($clean_string, '_');

    // Ensure the table name starts with a letter or underscore
    if (!preg_match('/^[a-zA-Z_]/', $clean_string)) {
        throw new \Exception("Table name should start with underscore or letter", 1);
    }

    // throw error when length is above 64 characters (database name limit)
    if (strlen($clean_string) > 64) throw new \Exception("Table name provided has exceed the 64 character limit: $clean_string", 1);

    return $clean_string;
}


/**##################################################################################################################***
 *                                                                                                                      *
 *                                               Raw Database helpers                                                   *
 *                                                                                                                      *
 ***##################################################################################################################**/

/**
 * Retrieves the master database connection details.
 *
 * @return array  The master database connection details
 */
function perfex_saas_master_dsn()
{
    return array(
        'driver' => APP_DB_DRIVER,
        'host' => defined('APP_DB_HOSTNAME_DEFAULT') ? APP_DB_HOSTNAME_DEFAULT : APP_DB_HOSTNAME,
        'user' => defined('APP_DB_USERNAME_DEFAULT') ? APP_DB_USERNAME_DEFAULT : APP_DB_USERNAME,
        'password' => defined('APP_DB_PASSWORD_DEFAULT') ? APP_DB_PASSWORD_DEFAULT : APP_DB_PASSWORD,
        'dbname' => defined('APP_DB_NAME_DEFAULT') ? APP_DB_NAME_DEFAULT : APP_DB_NAME
    );
}

/**
 * Execute a raw SQL query using PDO and prevent SQL vulnerabilities.
 *
 * The query is executed using the provided PDO connection and can contain placeholders for query parameters.
 * The function supports single queries and multiple queries in an array.
 * The function is expected to be used internally and should be use with parameters when running input from the user/public.
 *
 * @param string|string[] $query              The SQL query or array of queries to execute.
 * @param array           $dsn                The database connection details. Defaults to an empty array.
 * @param bool            $return             Whether to return the query results. Defaults to false.
 * @param bool            $atomic             Whether to run the queries in a transaction. Defaults to true.
 * @param callable|null   $callback           Optional callback function to execute on each result row.
 * @param bool            $disable_foreign_key Whether to disable foreign key checks. Defaults to false.
 * @param bool            $stop_on_error      Whether to stop execution on the first query error. Defaults to true.
 * @param array           $query_params       Array of query parameters to bind to the prepared statement. Defaults to an empty array.
 *
 * @return mixed|null The query result or null if there was an error.
 *
 * @throws \PDOException If there is a database error and the environment is set to development.
 * @throws \Exception    If there is a non-database-related error and the environment is set to development.
 */
function perfex_saas_raw_query($query, $dsn = [], $return = false, $atomic = true, $callback = null, $disable_foreign_key = false, $stop_on_error = true, $query_params = [])
{

    if (empty($dsn)) {

        $dsn = perfex_saas_master_dsn();
    }

    if (is_string($dsn)) { //conn is dsn sting
        $dsn = perfex_saas_parse_dsn($dsn);
    }

    // Get PDO
    $pdo = perfex_saas_get_pdo_conn($dsn, true);

    $is_multi_query = is_array($query);
    $resultList = array();

    try {

        if ($is_multi_query && !empty($query_params))
            throw new \Exception("Query parameter binding is not supported for multiple query", 1);

        $pre_queries = [];
        $post_queries = [];
        $queries = $is_multi_query ? $query : [$query];

        if ($disable_foreign_key) {
            $pre_queries[] = "SET foreign_key_checks = 0;";
            $post_queries[] = "SET foreign_key_checks = 1;"; // add to end
        }

        // Run prequeries. These are safe to run without binding
        foreach ($pre_queries as $pr_q) {
            $pdo->query($pr_q);
        }

        if ($atomic) {
            $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
            $pdo->beginTransaction();
        }


        foreach ($queries as $index => $q) {

            $stmt = false;

            if (!$stop_on_error) {
                try {
                    $stmt = perfex_saas_pdo_safe_query($pdo, $q, $query_params);
                } catch (\Throwable $th) {
                    log_message("error", "Database Error: " . $th->getMessage());
                    $stmt = false;
                }
            } else {
                $stmt = perfex_saas_pdo_safe_query($pdo, $q, $query_params);
            }


            $results = [false];

            if ($stmt) {

                $results = [true];

                if ($return) {
                    $results = [];
                    while ($row = $stmt->fetchObject()) {
                        $results[] = $row;
                        if ($callback && is_callable($callback)) {
                            call_user_func($callback, $row);
                        }
                    }
                }
                $stmt->closeCursor();
            }

            $resultList[$index] = $results;
        }

        // Safe queries
        foreach ($post_queries as $po_q) {
            $pdo->query($po_q);
        }

        if ($atomic) {
            $pdo->commit();
            $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        }
    } catch (\PDOException $e) {

        log_message("error", "Database Error: " . $e->getMessage() . ': ' . @$q);

        if ($atomic) $pdo->rollBack();

        if (ENVIRONMENT === 'development') {
            echo $q ?? '';
            throw $e;
        }

        return null;
    } catch (\Exception $e) {

        log_message("error", $e->getMessage() . ': ' . @$q);

        if ($atomic) $pdo->rollBack();

        if (ENVIRONMENT === 'development') {
            echo $q ?? '';
            throw $e;
        }

        return null;
    }

    return $is_multi_query ? $resultList : $resultList[0];
}

/**
 * Executes a safe query using prepared statements with PDO.
 *
 * @param PDO $pdo The PDO instance.
 * @param string $query The SQL query.
 * @param array $parameters The query parameters to bind.
 * @return PDOStatement|false The PDOStatement object if successful, false otherwise.
 */
function perfex_saas_pdo_safe_query($pdo, $query, $parameters)
{
    $statement = $pdo->prepare($query);

    foreach ($parameters as $key => $value) {
        $statement->bindParam($key, $value);
    }

    return $statement->execute() ? $statement : false;
}

/**
 * Executes a raw query and returns the first row of the result set.
 *
 * @param string|string[] $query The SQL query.
 * @param array $dsn The connection details.
 * @param bool $return Whether to return the result set or not.
 * @param bool $atomic Whether to run the query in a transaction or not.
 * @param array $query_params The query parameters.
 * @return mixed|null The first row of the result set if successful, null otherwise.
 */
function perfex_saas_raw_query_row($query, $dsn = [], $return = false, $atomic = true, $query_params = [])
{
    $result = perfex_saas_raw_query($query, $dsn, $return, $atomic, null, false, true, $query_params);
    if (!$result) {
        return $result;
    }
    return $result[0];
}


/**
 * Executes a database query based on the provided SQL statement in context of the current instance.
 *
 * @param string $sql The SQL query.
 * @return mixed The result of the query execution.
 */
function perfex_saas_db_query($sql)
{
    $slug = perfex_saas_tenant_slug();

    if (!$slug) {
        // Default saas panel.
        if (
            stripos($sql, perfex_saas_table('')) !== false //saas table queries
        )
            return $sql;

        // Always set this for security. This ensure other tenant data is not loaded for master instance on multitenant singledb
        $slug = perfex_saas_master_tenant_slug();
    }
    return perfex_saas_simple_query($slug, $sql);
}


/**
 * Function validate SQL QUERY.
 * This function can be used for logging all SQL queries and make extra validations
 *
 * @param string $slug The tenant slug.
 * @param string $sql The SQL query.
 * @throws \Exception
 * @return mixed|string The parsed SQL query or the result of the query execution.
 */
function perfex_saas_simple_query($slug, $sql)
{

    $is_master = $slug === perfex_saas_master_tenant_slug();
    if ($is_master) return $sql;

    $master_dbprefix = perfex_saas_master_db_prefix();

    $sql = trim($sql);

    // Perfex crm and some author often used the default db prefix 'tbl' hardcoded sometimes in some queries. i.e /application/libraries/import/Import_leads.php LINE 47 and 48:
    // @todo Remove this line when Perfex core fixes above mentioned instances.
    $tenant_dbprefix = perfex_saas_tenant_db_prefix($slug);
    $sql = str_ireplace($tenant_dbprefix . 'tbl', $tenant_dbprefix, $sql);
    // end of patch to remove

    // Bad developer habit control for hard coded tables
    if (stripos($sql, $master_dbprefix)) {

        $sql = str_ireplace(
            ["`$master_dbprefix", "($master_dbprefix", ".$master_dbprefix"],
            ["`$tenant_dbprefix", "($tenant_dbprefix", ".$tenant_dbprefix"],
            $sql
        );

        if (stripos($sql, ' ' . $master_dbprefix)) {
            $commonKeywords = array("FROM", "INSERT INTO", "UPDATE", "DELETE FROM", "CREATE TABLE", "ALTER TABLE");
            foreach ($commonKeywords as $key) {
                $sql = str_ireplace(
                    ["$key $master_dbprefix"],
                    ["$key $tenant_dbprefix"],
                    $sql
                );
            }
        }
    }
    // End of patch

    $will_change_db_struct = stripos($sql, 'ALTER TABLE') !== false ||
        stripos($sql, 'TRUNCATE TABLE') !== false ||
        stripos($sql, 'DROP TABLE') !== false ||
        stripos($sql, 'RENAME TABLE') !== false ||
        (stripos($sql, ' RENAME') !== false && stripos($sql, 'ALTER TABLE') !== false) ||
        str_starts_with($sql, 'DROP DATABASE') || str_starts_with($sql, 'GRANT');

    // Deny, unsupported, tenant shouldnt be able to do any of this query
    if ($will_change_db_struct) {

        $parser = new PHPSQLParser($sql);
        $parsed  = $parser->parsed;

        $key = strtoupper(key($parsed));

        if (!in_array($key, ['TRUNCATE', 'DROP', 'RENAME'])) {

            // Any normal insert or write query should not reach here and we can loosly check for master prefix in query.
            $_sql = trim($sql);
            $will_change_db_struct_on_master = stripos($_sql, ' ' . $master_dbprefix) !== false ||
                stripos($_sql, '`' . $master_dbprefix) !== false ||
                stripos($_sql, '(' . $master_dbprefix) !== false ||
                stripos($_sql, '.' . $master_dbprefix) !== false;

            if ($will_change_db_struct_on_master) {
                throw new \Exception("Query running out of bounds for the tenant: $sql", 1);
            }

            return $sql;
        }

        throw new \Exception("Unsupported query for tenant: $sql", 1);
    }

    return $sql;
}





/**##################################################################################################################***
 *                                                                                                                      *
 *                                               Database locator and DSN                                               *
 *                                                                                                                      *
 ***##################################################################################################################**/

/**
 * Check if a domain string is valid domain name.
 *
 * @return bool
 */
function perfex_saas_is_valid_custom_domain($domain)
{

    // Length Check
    if (strlen($domain) > 255) {
        return false;
    }

    // Character Set Check
    if (!preg_match('/^[A-Za-z0-9.-]+$/', $domain)) {
        return false;
    }

    if (str_starts_with($domain, '.') || str_ends_with($domain, '.') || !stripos($domain, '.'))
        return false;

    // Dont allow subdomain of main host
    if (str_ends_with($domain, perfex_saas_get_saas_default_host()))
        return false;


    // Label Length Check
    $labels = explode('.', $domain);
    foreach ($labels as $label) {
        if (strlen($label) > 63) {
            return false;
        }
    }

    if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        return false;
    }

    return true;
}

/**
 * Convert an array DSN to a string representation.
 * 
 * @param array $dsn The array DSN containing driver, host, dbname, user, and password.
 * @param bool $with_auth Whether to include authentication details in the DSN.
 * @return string The DSN string representation.
 */
function perfex_saas_dsn_to_string(array $dsn, $with_auth = true)
{
    // Extract the individual components from the DSN array
    $driver = $dsn['driver'] ?? APP_DB_DRIVER;
    $host = $dsn['host'] ?? APP_DB_HOSTNAME_DEFAULT;
    $dbname = $dsn['dbname'] ?? '';
    $user = $dsn['user'] ?? '';
    $password = $dsn['password'] ?? '';

    // Build the basic DSN string
    $dsn_string = $driver . ':host=' . $host . ';dbname=' . $dbname;

    // If 'with_auth' is false, return the basic DSN string without authentication details
    if (!$with_auth) {
        return $dsn_string;
    }

    // Append the authentication details to the DSN string
    $dsn_string = $dsn_string . ';user=' . $user . ';password=' . $password . ';';

    return $dsn_string;
}



/**
 * Parse a DSN string and return the parsed components.
 *
 * Example dsn string: mysql:host=127.0.0.1;dbname=demodb;user=demouser;password=diewo;eg@j$l!;
 * DSN should follow above pattern and should ends with ";".
 * 
 * @param string $dsn The DSN string to parse.
 * @param array $returnKeys The specific keys to return from the parsed DSN.
 * @return array The parsed DSN components.
 * @throws Exception When the DSN string is empty or invalid.
 */
function perfex_saas_parse_dsn($dsn, $returnKeys = [])
{
    // Define the default indexes for parsing
    $indexes = ['host', 'dbname', 'user', 'password'];

    // Check if specific keys are requested for return
    $returnSet = is_array($returnKeys) && !empty($returnKeys);
    if ($returnSet) {
        $indexes = $returnKeys;
    }

    // Check if the DSN string is empty or invalid
    if (empty($dsn) || (false === ($pos = stripos($dsn, ":")))) {
        $error = "Empty or Invalid DSN string";
        log_message("error", "$error: $dsn");
        throw new Exception($error);
    }

    // Extract the driver from the DSN string
    $driver = strtolower(substr($dsn, 0, $pos)); // always returns a string

    // Check if the driver is empty
    if (empty($driver)) {
        throw new Exception(_l("perfex_saas_invalid_dsn_no_driver"));
    }

    // Initialize the parsed DSN array with the driver
    $parsedDsn = ['driver' => $driver];

    // Define the keys used for mapping and their order in the DSN string
    $mapKeys = [':host=', ';dbname=', ';user=', ';password='];

    // Iterate through the map keys to extract values from the DSN string
    foreach ($mapKeys as $i => $key) {
        $position = stripos($dsn, $key);
        $nextPosition = ($i + 1) >= count($mapKeys) ? stripos($dsn, ';', -1) : stripos($dsn, $mapKeys[$i + 1]);

        // Get the length of the value using the next position minus the key position
        $valueLength = $nextPosition - $position;
        $value = substr($dsn, $position, $valueLength);

        // Remove the key from the captured value
        $value = str_ireplace($key, '', $value);

        // Clean the DSN key
        $key = str_ireplace([':', '=', ';'], '', $key);

        $parsedDsn[$key] = $value;
    }

    // Set the return value based on the requested keys
    $r = $parsedDsn;

    if ($returnSet) {
        $r = [];
        foreach ($indexes as $key) {
            // Check if the parsed DSN contains the requested key
            if (!isset($parsedDsn[$key])) {
                throw new RuntimeException(_l('perfex_saas_dsn_missing_key', $key));
            }

            $r[$key] = $parsedDsn[$key];
        }
    }

    return $r;
}

/**
 * Check if a DSN is valid by testing the database connection.
 *
 * @param array $dsn The DSN array to validate.
 * @param bool $use_cache Flag to indicate whether to use the cached connection.
 * @return bool|string Returns true if the DSN is valid, otherwise returns an error message.
 */
function perfex_saas_is_valid_dsn(array $dsn, $use_cache = true)
{
    try {
        // Check if the required DSN components (host, user, dbname) are present
        if (empty($dsn['host'] ?? '') || empty($dsn['user'] ?? '') || empty($dsn['dbname'] ?? '')) {
            throw new \Exception(_l('perfex_saas_host__user_and_dbname_is_required_for_valid_dsn'), 1);
        }

        // Test the database connection
        $conn = perfex_saas_get_pdo_conn($dsn, $use_cache);

        if (!$conn) {
            throw new \Exception("Error establishing connection", 1);
        }

        return true;
    } catch (\Throwable $th) {
        return $th->getMessage();
    }
}

/**
 * Get a PDO database connection based on the provided DSN.
 *
 * @param array $dsn The DSN array containing driver, host, dbname, user, and password.
 * @param bool $use_cache Flag to indicate whether to use the cached connection.
 * @return PDO The PDO database connection.
 */
function perfex_saas_get_pdo_conn($dsn, $use_cache = true)
{
    // PDO uses 'mysql' instead of 'mysqli'
    if (!isset($dsn['driver']) || (isset($dsn['driver']) && $dsn['driver'] == 'mysqli')) {
        $dsn['driver'] = 'mysql';
    }

    $dsn_string = perfex_saas_dsn_to_string($dsn, false);

    $cached = isset($GLOBALS[$dsn_string]);

    if ($cached && $use_cache) {
        $pdo = $GLOBALS[$dsn_string];
        if ($pdo instanceof PDO) {
            return $pdo;
        }
    }

    $pdo = new PDO(
        $dsn_string,
        $dsn['user'],
        $dsn['password'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    $GLOBALS[$dsn_string] = $pdo;
    return $pdo;
}




/**##################################################################################################################/**
 *                                                                                                                      *
 *                                                    UI and Http helpers                                               *
 *                                                                                                                      *
 ***##################################################################################################################**/

/**
 * Show a custom error page for the tenant (middleware).
 *
 * @param string $heading The heading of the error page.
 * @param string $message The error message to display.
 * @param int $error_code The error code to display (default: 403).
 * @param string $template The error template file to use (default: '404').
 */
function perfex_saas_show_tenant_error($heading, $message, $error_code = 403, $template = '404')
{
    $error_file = APPPATH . 'views/errors/html/error_' . $template . '.php';

    $message = "
        $message 
        <script>
            let tag = document.querySelector('h2');
            tag.innerHTML = tag.innerHTML.replace('404', '$error_code');
        </script>
    ";

    if (file_exists($error_file)) {
        require_once($error_file);
        exit();
    }

    echo ($heading . '<br/><br/>' . $message);
    exit();
}




/**
 * Generate the URL signature for the tenant withot trailing (left or right) slash
 *
 * @param string $slug The slug of the tenant.
 * @return string The URL signature.
 */
function perfex_saas_tenant_url_signature($slug)
{
    $path_prefix = PERFEX_SAAS_ROUTE_ID;
    return "$slug/$path_prefix";
}


/**
 * Get the host name base on the server variables.
 *
 * @param array $server The server variables.
 * @param bool $use_forwarded_host Whether to use the forwarded host.
 * @return string The http host name.
 * @throws Exception When no valid host if found
 */
function perfex_saas_http_host($server, $use_forwarded_host = true)
{
    $host = ($use_forwarded_host && isset($server['HTTP_X_FORWARDED_HOST'])) ? $server['HTTP_X_FORWARDED_HOST'] : (isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : null);
    if (empty($host) || !filter_var($host, FILTER_VALIDATE_DOMAIN))
        throw new \Exception("Error detecting valid http host", 1);
    return $host;
}

/**
 * Get the URL origin based on the server variables.
 *
 * @param array $server The server variables.
 * @param bool $use_forwarded_host Whether to use the forwarded host.
 * @return string The URL origin.
 */
function perfex_saas_url_origin($server, $use_forwarded_host = true)
{
    $ssl = (!empty($server['HTTPS']) && $server['HTTPS'] == 'on');
    $sp = strtolower($server['SERVER_PROTOCOL']);
    $protocol = !empty($server['HTTP_X_FORWARDED_PROTO']) ? $server['HTTP_X_FORWARDED_PROTO'] : substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $server['SERVER_PORT'];
    $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
    $host = perfex_saas_http_host($server, $use_forwarded_host);
    $host = isset($host) ? $host : $server['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

/**
 * Get the full URL based on the server variables.
 *
 * @param array $server The server variables.
 * @param bool $use_forwarded_host Whether to use the forwarded host.
 * @return string The full URL.
 */
function perfex_saas_full_url($server, $use_forwarded_host = true)
{
    $url_origin = perfex_saas_url_origin($server, $use_forwarded_host);
    $request_uri = $server['REQUEST_URI'];
    return $url_origin . $request_uri;
}

/**
 * Redirect the user back to the previous page or a default page.
 */
function perfex_saas_redirect_back()
{
    if (function_exists('redirect')) {

        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            return redirect($_SERVER['HTTP_REFERER']);
        }

        if (function_exists('admin_url')) {
            // If HTTP_REFERER is not set or empty, redirect to a default page
            return redirect(admin_url());
        }
    }

    header('Location: ' . perfex_saas_url_origin($_SERVER));
}


/**
 * Perform an HTTP request using cURL.
 *
 * @param string $url     The URL to send the request to.
 * @param array  $options An array of options for the request.
 *
 * @return array An array containing the 'error' and 'response' from the request.
 */
function perfex_saas_http_request($url, $options)
{
    // Initialize cURL
    $curl = curl_init($url);

    // Set SSL verification and timeout options
    $verify_ssl = (int) ($options['sslverify'] ?? 0);
    $timeout = (int) ($options['timeout'] ?? 30);

    if ($options) {
        // Get request method
        $method = strtoupper($options["method"] ?? "GET");

        // Get request data and headers
        $data = @$options["data"];
        $headers = (array) @$options["headers"];

        // Set JSON data and headers for POST requests
        if ($method === "POST") {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        // Set custom headers if provided
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
    }

    // Set common cURL options
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYHOST => $verify_ssl,
        CURLOPT_TIMEOUT => (int) $timeout,
    ]);

    // Make the request
    $result = curl_exec($curl);

    // Check for errors
    $error = '';
    if (curl_error($curl) || curl_errno($curl)) {
        $error = 'Curl Error - "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
    }

    // Close the cURL session
    curl_close($curl);

    return ['error' => $error, 'response' => $result];
}

/**
 * Get the list of modules available to the tenant.
 *
 * @param object|null $tenant                       The tenant object. If null, the current tenant will be used.
 * @param bool        $include_saas_module           Whether to include the SAAS module in the list.
 * @param bool        $include_tenant_disabled_modules Whether to include tenant-disabled modules.
 * @param bool        $include_admin_disabled_modules  Whether to include admin-disabled modules.
 *
 * @return array The list of tenant modules.
 */
function perfex_saas_tenant_modules(
    object $tenant = null,
    bool $include_saas_module = true,
    bool $include_tenant_disabled_modules = false,
    bool $include_admin_disabled_modules = false
) {
    // Get the tenant object
    $tenant = $tenant ?? perfex_saas_tenant();

    // Get the package and modules
    $package = isset($tenant->package_invoice) ? $tenant->package_invoice : null;
    $modules = (array) ($package->modules ?? []);

    // Get the metadata and approved/disabled modules
    $metadata = (object) $tenant->metadata;
    $admin_approved_modules = isset($metadata->admin_approved_modules) ? (array) $metadata->admin_approved_modules : [];
    $admin_disabled_modules = isset($metadata->admin_disabled_modules) ? (array) $metadata->admin_disabled_modules : [];
    $disabled_modules = isset($metadata->disabled_modules) ? (array) $metadata->disabled_modules : [];

    $tenant_modules = [];

    // Include SAAS module if required.
    // NOTE: make saas module the first module to be loaded when $include_saas_module is true
    if ($include_saas_module) {
        $tenant_modules[] = PERFEX_SAAS_MODULE_NAME;
    }

    // Merge package modules and admin-approved modules
    $tenant_modules = array_merge($tenant_modules, $modules, $admin_approved_modules);

    // Include user purchased modules
    $purchased_modules = (array)($tenant->package_invoice->purchased_modules ?? []);
    $tenant_modules = array_merge($tenant_modules, $purchased_modules);

    // Make the package and assigned modules unique
    $tenant_modules = array_unique($tenant_modules);

    // Remove disabled modules if not included
    if (!$include_tenant_disabled_modules) {
        $tenant_modules = array_diff($tenant_modules, $disabled_modules);
    }

    // Remove admin-disabled modules if not included
    if (!$include_admin_disabled_modules) {
        $tenant_modules = array_diff($tenant_modules, $admin_disabled_modules);
    }

    return (array) $tenant_modules;
}

/**
 * Get the list of default modules disabled for the tenant.
 *
 * @param object|null $tenant   The tenant object. If null, the current tenant will be used.
 *
 * @return array The list of tenant disabled default modules.
 */
function perfex_saas_tenant_disabled_default_modules(object $tenant = null, $mode = "controller")
{
    // Get the tenant object
    $tenant = $tenant ?? perfex_saas_tenant();

    // Get the package and modules
    $package = isset($tenant->package_invoice) ? $tenant->package_invoice : null;
    $modules = (array) ($package->metadata->disabled_default_modules ?? []);

    // Get the metadata and approved/disabled modules
    $metadata = (object) $tenant->metadata;
    $admin_disabled_modules =  (array) ($metadata->admin_disabled_default_modules ?? []);


    // Merge package modules and admin-approved modules
    $tenant_default_disabled_modules = array_merge($modules, $admin_disabled_modules);

    if ($mode !== "controller") {
        // Adapt for menu and tabs check
        foreach ($tenant_default_disabled_modules as $key => $value) {
            if (empty($value)) unset($tenant_default_disabled_modules[$key]);
            if (stripos($value, '_') !== false)
                $tenant_default_disabled_modules[] = str_replace('_', '-', $value);

            if ($value === 'tickets') {
                $tenant_default_disabled_modules[] = 'support';
            }
        }
    }

    // Add controller alias for some specific cases
    if (in_array('invoices', $tenant_default_disabled_modules)) {
        $tenant_default_disabled_modules[] = 'taxes';
        $tenant_default_disabled_modules[] = 'currencies';
    }
    if (in_array('payments', $tenant_default_disabled_modules)) {
        $tenant_default_disabled_modules[] = 'paymentmodes';
        $tenant_default_disabled_modules[] = 'currencies';
    }

    if (in_array('credit_notes', $tenant_default_disabled_modules)) {
        $tenant_default_disabled_modules[] = 'creditnotes';
    }

    return $tenant_default_disabled_modules;
}


/**
 * Get master settings from options table.
 *
 * @param mixed $fields     The masked fields.
 * @param bool $parse       If to flatten the result into field => value pair
 * @return mixed            The shared secret master settings.
 * @Bridge-Function
 */
function perfex_saas_get_options($field, bool $parse = true)
{
    $single_option = is_string($field);
    $fields = "'" . implode("','", $single_option ? [$field] : $field) . "'";
    $option_query = 'SELECT name, value FROM ' . perfex_saas_master_db_prefix() . "options WHERE `name` IN ($fields)";

    // Perform a raw query to fetch the shared secret master settings
    $result = perfex_saas_raw_query($option_query, [], true);

    if (!$parse) return $result;

    $fields_value = [];
    foreach ($result as $row) {
        $fields_value[$row->name] = $row->value;
    }

    if ($single_option) return isset($fields_value[$field]) ? $fields_value[$field] : '';

    return $fields_value;
}

/**
 * Update master settings value
 *
 * @param string $field
 * @param string $value
 * @return mixed
 * @Bridge-Function
 */
function perfex_saas_update_option($field, $value)
{
    $option_query = 'UPDATE `' . perfex_saas_master_db_prefix() . "options` SET `value` ='$value' WHERE `name`='$field'";
    return perfex_saas_raw_query($option_query, []);
}

/**
 * Save or update client metatdata
 *
 * @param mixed $clientid
 * @param array $update_data
 * @return array
 * @Bridge-Function
 */
function perfex_saas_get_or_save_client_metadata($clientid, $update_data = [])
{
    $table = perfex_saas_table('client_metadata');
    $client_metadata = perfex_saas_raw_query_row("SELECT * FROM $table WHERE `clientid`='$clientid';", [], true);
    $metadata = empty($client_metadata->metadata) ? [] : (array)json_decode($client_metadata->metadata);

    if (empty($update_data)) {
        return $metadata;
    }

    $id = $client_metadata->id ?? null;
    $where = !empty($id) ? "WHERE `id`='$id' AND `clientid`='$clientid'" : '';
    $metadata = array_merge($metadata, $update_data);
    $metadata_json = json_encode($metadata);
    if (!empty($id))
        $query = "UPDATE $table SET `metadata`='$metadata_json', `clientid` = '$clientid' $where";
    else
        $query = "INSERT INTO `$table` (`id`, `metadata`, `clientid`) VALUES (NULL, '$metadata_json', '$clientid')";

    $updated = perfex_saas_raw_query($query);
    if ($updated)
        return $metadata;

    return null;
}
