<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Perfex_saas_model extends App_Model
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get data from a table.
     *
     * @param string $table The name of the table.
     * @param string $id The ID of the data to retrieve. If empty, retrieve all data.
     * @return mixed The retrieved data.
     */
    function get($table, $id = '')
    {
        $this->db->select();
        $this->db->from($table);
        $this->db->order_by('id', 'ASC');

        if (!empty($id)) {
            $this->db->where('id', $id);
        }

        $query = $this->db->get();

        return empty($id) ? $query->result() : $query->row();
    }

    /**
     * Get an entity by slug.
     *
     * @param string $entity The entity name.
     * @param string $slug The slug of the entity.
     * @param string $parse_method The slef method to use for parsing the entity.
     * @return mixed The retrieved entity.
     */
    function get_entity_by_slug($entity, $slug, $parse_method = '')
    {
        $this->db->select();
        $this->db->from(perfex_saas_table($entity));
        $this->db->where('slug', $slug);

        $row = $this->db->get()->row();

        if (!empty($parse_method) && !empty($row)) {
            $row = $this->{$parse_method}($row);
        }

        return $row;
    }

    /**
     * Add or update an entity.
     *
     * @param string $entity The entity name.
     * @param array $data The data to add or update.
     * @return int|bool The ID of the added or updated entity, or false on failure.
     */
    public function add_or_update(string $entity, array $data)
    {
        return $this->add_or_update_raw(perfex_saas_table($entity), $data);
    }

    /**
     * Add or update an entity using raw table name.
     *
     * @param string $table The name of the table.
     * @param array $data The data to add or update.
     * @return int|bool The ID of the added or updated entity, or false on failure.
     */
    public function add_or_update_raw(string $table, array $data)
    {
        $id = false;

        if (isset($data['id']) && !empty($data['id'])) {
            $this->db->where('id', $data['id']);
            if ($this->db->update($table, $data)) {
                $id = $data['id'];
            }
        } else {
            $this->db->insert($table, $data);
            $id = $this->db->insert_id();
        }

        return $id;
    }

    /**
     * Delete an entity by ID.
     *
     * @param string $entity The entity name.
     * @param mixed $id The ID of the entity to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $entity, $id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(perfex_saas_table($entity));
    }

    /**
     * Clone an entity by ID.
     *
     * @param string $entity The entity name.
     * @param mixed $id The ID of the entity to clone.
     * @return int|bool The ID of the cloned entity, or false on failure.
     */
    public function clone(string $entity, $id)
    {
        $table = perfex_saas_table($entity);

        $entity_data = $this->get($table, $id);
        if (!$entity_data) {
            return false;
        }

        $total = count($this->get($table));

        if (isset($entity_data->name)) {
            $entity_data->name = $entity_data->name . '#' . ($total + 1);
        }

        if (isset($entity_data->slug)) {
            $entity_data->slug = slug_it($entity_data->name);
        }

        if (isset($entity_data->is_default)) {
            $entity_data->is_default = 0;
        }

        unset($entity_data->id);

        return $this->add_or_update($entity, (array)$entity_data);
    }

    /**
     * Check if the database user has create privilege
     * @return bool
     */
    public function db_user_has_create_privilege()
    {
        $debug_mode = $this->db->db_debug;
        $has_priviledge = false;
        try {
            $db = perfex_saas_db('testdb');
            $this->db->db_debug = false;
            if ($this->db->query('CREATE DATABASE ' . $db)) {
                if (!$this->db->query('DROP DATABASE `' . $db . '`'))
                    throw new \Exception("Error dropping test db $db", 1);
            } else {
                throw new \Exception("Error creating database", 1);
            }
            $has_priviledge = true;
        } catch (\Throwable $th) {
            log_message('error', 'Database user dont have permission to create new db:' . $th->getMessage());
        }
        $this->db->db_debug = $debug_mode;
        return $has_priviledge;
    }

    /**
     * Create a database
     * @param string $db The name of the database to create
     * @return bool|string True on success, error message on failure
     */
    public function create_database($db)
    {
        try {
            if (!$this->db->query("CREATE DATABASE IF NOT EXISTS `$db`")) {
                throw new \Exception("Error creating database $db", 1);
            }
            return true;
        } catch (\Throwable $th) {
            log_message('error', 'Database user dont have permission to create new db:' . $th->getMessage());
            return $th->getMessage();
        }
        return false;
    }

    /**
     * Get database pools population by package ID
     * @param int $package_id The ID of the package
     * @return array Array containing population map and pools map
     */
    public function get_db_pools_population_by_packgeid($package_id)
    {
        $packages = $this->packages($package_id);
        $packages = !empty($package_id) ? [$packages] : $packages;

        $dbprefix = perfex_saas_master_db_prefix();

        $query = "SHOW TABLES LIKE '%\_" . $dbprefix . "staff'";
        $population_map = [];
        $pools_map = [];

        foreach ($packages as $p) {
            $pools = $p->db_pools;

            if (!empty($pools)) {
                foreach ($pools as $pool) {
                    $pool = (array)$pool;
                    $key = perfex_saas_dsn_to_string($pool, false);

                    if (!isset($pools_map[$key])) {
                        try {

                            $population = count(perfex_saas_raw_query($query, $pool, true));
                            $population_map[$key] = (int)$population;
                            $pools_map[$key] = $pool;
                        } catch (\Throwable $th) {
                            log_message("error", "Error reading stat from database: " . ($pool['dbname'] ?? '') . $th->getMessage());
                        }
                    }
                }
            }
        }

        return [$population_map, $pools_map];
    }

    /**
     * Get database pools population
     * @param array $pools Array of database pools
     * @return array Array containing population map and pools map
     */
    public function get_db_pools_population($pools)
    {
        $dbprefix = perfex_saas_master_db_prefix();

        $query = "SHOW TABLES LIKE '%\_" . $dbprefix . "staff'";
        $population_map = [];
        $pools_map = [];

        foreach ($pools as $pool) {
            $pool = (array)$pool;
            $key = perfex_saas_dsn_to_string($pool, false);

            if (!isset($pools_map[$key])) {

                $total = count(perfex_saas_raw_query($query, $pool, true));
                $population = $total;
                $population_map[$key] = (int)$population;
                $pools_map[$key] = $pool;
            }
        }

        return [$population_map, $pools_map];
    }

    /**
     * Method to make basic statistic about a pacakge
     *
     * @param int $packageid
     * @return object
     */
    function package_stats($packageid)
    {

        list($populations) = $this->get_db_pools_population_by_packgeid($packageid);
        $total_pool_population = array_sum(array_values($populations));

        $packageid_col = perfex_saas_column('packageid');
        $query = 'SELECT COUNT(DISTINCT(' .  $packageid_col . ')) as total FROM `' . perfex_saas_master_db_prefix() . 'invoices` WHERE `' . $packageid_col . "`=$packageid";
        $resp = perfex_saas_raw_query_row($query, [], true);
        $total_subscribed = $resp->total ?? 0;

        return (object)['total_invoices' => $total_subscribed, 'total_pool_population' => $total_pool_population];
    }

    /**
     * Get the chart dataset for packag invoice dourghnut chart
     *
     * @return array
     */
    public function package_invoice_chart()
    {
        $chart = [
            'labels'   => [],
            'datasets' => [],
        ];

        $_data                         = [];
        $_data['data']                 = [];
        $_data['backgroundColor']      = [];
        $_data['hoverBackgroundColor'] = [];
        $_data['statusLink']           = [];

        $packages = $this->packages();
        foreach ($packages as $package) {

            $result = $this->package_stats($package->id)->total_invoices;
            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            array_push($chart['labels'], $package->name);
            array_push($_data['backgroundColor'], $color);
            array_push($_data['hoverBackgroundColor'], adjust_color_brightness($color, -20));
            array_push($_data['data'], $result);
        }

        $chart['datasets'][] = $_data;

        return $chart;
    }


    /**
     * Get the list of database schemes for tenant management.
     *
     * @return array The array of database schemes.
     */
    public function db_schemes()
    {
        // Define an array of database schemes for tenant management
        $schemes = [
            ['key' => 'multitenancy', 'label' => _l('perfex_saas_use_the_current_active_database_for_all_tenants')], // Option for using the current active database for all tenants
            ['key' => 'single', 'label' => _l('perfex_saas_use_single_database_for_each_company_instance.')], // Option for using a single database for each company instance
            ['key' => 'single_pool', 'label' => _l('perfex_saas_single_pool_db_scheme')], // Option for using a single pool database scheme
            ['key' => 'shard', 'label' => _l('perfex_saas_distribute_companies_data_among_the_provided_databases_in_the_pool')], // Option for distributing companies' data among provided databases in the pool
        ];

        // Check if the current database user has the privilege to create a new database
        if (!$this->db_user_has_create_privilege()) {
            unset($schemes[1]); // Remove the option for single database per company if the privilege is not available
        }

        $schemes = hooks()->apply_filters('perfex_saas_module_db_schemes', $schemes);

        return $schemes;
    }

    /**
     * Get the alternative list of database schemes for tenant management.
     *
     * @return array The array of alternative database schemes.
     */
    public function db_schemes_alt()
    {
        // Define an array of alternative database schemes for tenant management
        $schemes = [
            ['key' => 'package', 'label' => _l('perfex_saas_auto_detect_from_client_subcribed_package')], // Option for auto-detecting database scheme from client subscribed package
            ['key' => 'multitenancy', 'label' => _l('perfex_saas_use_the_current_active_database')], // Option for using the current active database
            ['key' => 'single', 'label' => _l('perfex_saas_create_a_separate_database')], // Option for creating a separate database
            ['key' => 'shard', 'label' => _l('perfex_saas_i_will_provide_database_credential')], // Option for providing database credentials
        ];

        // Check if the current database user has the privilege to create a new database
        if (!$this->db_user_has_create_privilege()) {
            unset($schemes[1]); // Remove the option for using the current active database if the privilege is not available
        }

        $schemes = hooks()->apply_filters('perfex_saas_module_db_schemes_alt', $schemes);

        return $schemes;
    }

    /**
     * Get the list of company status options.
     *
     * @return array The array of company status options.
     */
    public function company_status_list()
    {
        // Return an array of company status options
        return [
            ['key' => 'active', 'label' => _l('perfex_saas_active')], // Option for active company
            ['key' => 'inactive', 'label' => _l('perfex_saas_inactive')], // Option for inactive company
            ['key' => 'banned', 'label' => _l('perfex_saas_banned')] // Option for banned company
        ];
    }

    /**
     * Get the list of yes/no options.
     *
     * @return array The array of yes/no options.
     */
    public function yes_no_options()
    {
        // Return an array of yes/no options
        return [
            ['key' => 'no', 'label' => _l('perfex_saas_no')], // Option for "No"
            ['key' => 'yes', 'label' => _l('perfex_saas_yes')] // Option for "Yes"
        ];
    }

    /**
     * Get the shared options from the database.
     *
     * @return array The array of shared options.
     */
    public function shared_options()
    {
        $options_table = perfex_saas_master_db_prefix() . 'options';
        // Retrieve the options from the database
        $this->db->select("`name` as 'key', REPLACE(`name`,'_',' ') as 'name'");
        $results = $this->db->get($options_table)->result();
        return $results;
    }

    /**
     * Get options that are classified as dangerous to share for tenant seeding
     *
     * @return void
     */
    public function sensitive_shared_options()
    {
        $options_table = perfex_saas_master_db_prefix() . 'options';
        $sql = "SELECT `name` as 'key', REPLACE(`name`,'_',' ') as 'name' FROM `" . $options_table . "` WHERE
                `name` LIKE '%password%' OR `name` LIKE '%key%' OR `name` LIKE '%secret%' OR `name` LIKE '%\_id' OR `name` LIKE '%token' OR
                `name` LIKE '%company_logo%' OR `name` ='favicon' OR `name` ='main_domain' OR
                `name` LIKE 'invoice_company\_%' OR `name` ='company_vat' OR `name` ='company_state' OR
                `name` LIKE 'perfex_saas%'
                ";
        $results = $this->db->query($sql)->result();
        return $results;
    }

    /**
     * List of default inbuilt perfex modules
     *
     * @param boolean $parse If to format or not into system_name and custom_name associative array.
     * @return array
     */
    public function default_modules($parse = true)
    {
        $default_modules = ['leads', 'projects', 'tasks', 'expenses', 'proposals', 'estimates', 'estimate_request', 'tickets', 'reports', 'contracts', 'knowledge_base', 'custom_fields', 'credit_notes', 'subscriptions', 'invoices', 'items', 'payments'];
        asort($default_modules);
        if ($parse) {
            foreach ($default_modules as $key => $value) {
                $default_modules[$key] = ['system_name' => $value, 'custom_name' => ucfirst(str_replace('_', ' ', $value))];
            }
        }
        return $default_modules;
    }

    /**
     * Get the list of modules installed on perfex.
     *
     * @param bool $exclude_self Flag to exclude the perfex saas module.
     * @return array The array of modules.
     */
    public function modules($exclude_self = true)
    {
        // Get the list of modules
        $_modules = $this->app_modules->get();
        $modules = [];

        // Retrieve the custom module names from the options
        $custom_modules_name = get_option('perfex_saas_custom_modules_name');
        $custom_modules_name = empty($custom_modules_name) ? [] : json_decode($custom_modules_name, true);

        $modules_market_settings = json_decode(get_option('perfex_saas_modules_marketplace') ?? '', true);

        foreach ($_modules as $value) {
            $module_id = $value['system_name'];

            // Check if the module is the self module and exclude it if necessary
            if ($module_id == PERFEX_SAAS_MODULE_NAME && $exclude_self) {
                continue;
            }

            $modules[$module_id] = $value;

            // Assign the custom name to the module if available, otherwise use the default module name
            $modules[$module_id]['custom_name'] = isset($custom_modules_name[$module_id]) ? $custom_modules_name[$module_id] : $modules[$module_id]['headers']['module_name'];

            // Add marketplace info
            $modules[$module_id]['description'] = $modules_market_settings[$module_id]['description'] ?? '';
            $modules[$module_id]['price'] = $modules_market_settings[$module_id]['price'] ?? '';
        }

        return $modules;
    }

    /**
     * Get the custom name of a module.
     *
     * @param string $module_system_name The system name of the module.
     * @return string The custom name of the module.
     */
    public function get_module_custom_name($module_system_name)
    {
        // Retrieve the custom module names from the options
        $custom_modules_name = get_option('perfex_saas_custom_modules_name');
        $custom_modules_name = empty($custom_modules_name) ? [] : json_decode($custom_modules_name, true);

        // Return the custom name if available, otherwise return the module system name
        return isset($custom_modules_name[$module_system_name]) ? $custom_modules_name[$module_system_name] : $module_system_name;
    }

    /**
     * Mark a package as default.
     *
     * @param int $package_id The ID of the package to mark as default.
     * @return bool True on success, false on failure.
     */
    public function mark_package_as_default($package_id)
    {
        $table = perfex_saas_table('packages');
        $this->db->update($table, ['is_default' => 0]);

        $this->db->where('id', $package_id);
        return $this->db->update($table, ['is_default' => 1]);
    }

    /**
     * Get all packages or single package by id.
     *
     * @param mixed $id The ID of the package to retrieve. If empty, retrieve all packages.
     * @return array|object The retrieved packages.
     */
    function packages($id = '')
    {
        $packages = $this->get(perfex_saas_table('packages'), $id);

        if (!empty($id) && !empty($packages)) {
            $packages = [$packages];
        }

        foreach ($packages as $key => $package) {
            $packages[$key] = $this->parse_package($package);
        }

        return !empty($id) ? $packages[0] : $packages;
    }

    /**
     * Get the default package
     *
     * @return mixed
     */
    function default_package()
    {
        $this->db->where('is_default', 1);
        $default_package = $this->packages();
        return $default_package[0] ?? null;
    }

    /**
     * Get all companies or single company speicifc by id.
     *
     * @param mixed $id The ID of the company to retrieve. If empty, retrieve all companies.
     * @return array|object The retrieved companies.
     */
    public function companies($id = '')
    {
        if (is_client_logged_in()) {
            $this->db->where('clientid', get_client_user_id());
        }

        $companies = $this->get(perfex_saas_table('companies'), $id);
        if (!empty($id)) {
            $companies = [$companies];
        }

        foreach ($companies as $key => $company) {
            if (!empty($company))
                $companies[$key] = $this->parse_company($company);
        }

        return !empty($id) ? $companies[0] : $companies;
    }



    /**
     * Parse a package object.
     *
     * @param object $package The package object to parse.
     * @return object The parsed package object.
     */
    public function parse_package(object $package)
    {
        if (isset($package->metadata)) {
            $package->metadata = (object)json_decode($package->metadata);

            // Parse discount for client or js
            $formatted_discounts = [];
            $discounts = $package->metadata->discounts ?? null;
            if (!empty($discounts)) {
                foreach ($discounts->limits as $key => $limit) {
                    if (!isset($formatted_discounts[$limit]))
                        $formatted_discounts[$limit] = [];

                    $unit = (int)$discounts->units[$key];
                    $formatted_discounts[$limit][$unit] = [
                        'unit' => $discounts->units[$key],
                        'percent' => $discounts->percents[$key]
                    ];
                }
                $package->metadata->formatted_discounts = (object)$formatted_discounts;
            }
        }

        if (isset($package->db_pools)) {
            $package->db_pools = (array)json_decode($this->encryption->decrypt($package->db_pools));
        }
        if (isset($package->modules)) {
            $package->modules = (array)json_decode($package->modules);
        }

        return $package;
    }

    /**
     * Parse a company object.
     *
     * @param object $company The company object to parse.
     * @return object The parsed company object.
     */
    public function parse_company(object $company)
    {
        if (isset($company->metadata)) {
            $company->metadata = (object)json_decode($company->metadata);
        }

        if (!empty($company->dsn)) {
            $company->dsn = $this->encryption->decrypt($company->dsn);
        }

        return $company;
    }

    /**
     * Create or update a company.
     *
     * @param mixed $data The company data.
     * @param mixed $invoice The invoice data.
     * @return mixed The ID of the created or updated company.
     * @throws \Exception When the company payload is malformed or certain conditions are not met.
     */
    public function create_or_update_company($data, $invoice)
    {

        $company = null;
        if (!empty($data['id'])) {
            $company = $this->companies($data['id']);
        }

        $creating_new = empty($company->id);

        if ($creating_new || empty($data['id'])) {
            if (empty($data['clientid']) || empty($data['name'])) {
                throw new \Exception(_l('perfex_saas_malformed_company_payload'), 1);
            }
        }


        $creating_as_admin = !is_client_logged_in() && (has_permission('perfex_saas_companies', '', 'create') && has_permission('perfex_saas_companies', '', 'edit'));

        $data['metadata'] = isset($data['metadata']) ? (array)$data['metadata'] : [];


        // Handle custom domain - updating or create
        $custom_domain = $data['custom_domain'] ?? '';
        if (!empty($custom_domain)) {

            if (!perfex_saas_is_valid_custom_domain($custom_domain))
                throw new \Exception(_l('perfex_saas_invalid_custom_domain', $custom_domain));

            // Ensure custom domain not taken
            $this->db->where('custom_domain', $custom_domain);
            if (!$creating_new) {
                $this->db->where('slug !=', $company->slug);
            }
            $count = count($this->get(perfex_saas_table('companies')));
            if ($count) throw new \Exception(_l('perfex_saas_custom_domain_exist'), 1);


            $autoapprove = (int)($invoice->metadata->autoapprove_custom_domain ?? 0);
            if (!$creating_as_admin && !$autoapprove) { // Make pending
                $data['metadata']['pending_custom_domain'] = $custom_domain;
                unset($data['custom_domain']);
            }
        }


        // Create actions
        if ($creating_new) {

            // Check limit for the owner
            $max = (int)(isset($invoice->metadata->max_instance_limit) ? $invoice->metadata->max_instance_limit : 1);
            $this->db->where('clientid', $data['clientid']);
            $count = count($this->companies());
            if ($max > 0 && $count >= $max) {
                throw new \Exception(_l('perfex_saas_max_instance_reached' . ($creating_as_admin ? '_admin' : ''), $max), 1);
            }

            // Handle slug
            $slug = isset($data['slug']) && !empty($data['slug']) ? $data['slug'] : explode(' ', $data['name'])[0];

            // Ensure we have valid unique slug
            $slug = perfex_saas_generate_unique_slug($slug, 'companies', $data['id'] ?? '');

            // Revalidate slug is valid
            if (!perfex_saas_slug_is_valid($slug))
                throw new \Exception(_l('perfex_saas_invalid_slug', PERFEX_SAAS_MAX_SLUG_LENGTH), 1);

            $data['slug'] = $slug;

            // Set default to empty for client and leave for admin.
            $data['dsn'] = $creating_as_admin ? $data['dsn'] : '';

            // Determine the dsn if none is provided so far
            if (empty($data['dsn'])) {

                // If invoice is single db, set the dbname. This prevents saving the master db credential to the database.
                if ($invoice->db_scheme == 'single') {

                    if (!$this->db_user_has_create_privilege()) {
                        throw new \Exception(_l('perfex_saas_db_scheme_single_not_supported'), 1);
                    }

                    $dbname = perfex_saas_db($data['slug']);
                    $create_db = $this->create_database($dbname);
                    if ($create_db !== true) {
                        throw new \Exception(_l('Error creating database: ' . $create_db), 1);
                    }

                    $data['dsn'] = perfex_saas_dsn_to_string([
                        'dbname' => $dbname
                    ]);
                }

                if ($invoice->db_scheme == 'multitenancy') {
                    $data['dsn'] = perfex_saas_dsn_to_string([
                        'dbname' => APP_DB_NAME,
                    ]);
                }

                if ($invoice->db_scheme == 'single_pool' || $invoice->db_scheme == 'shard') {

                    $dsn = perfex_saas_get_company_dsn($company, $invoice);
                    if (!perfex_saas_is_valid_dsn($dsn, true)) {
                        throw new \Exception(_l('perfex_saas_invalid_datacenter'), 1);
                    }

                    $data['dsn'] = perfex_saas_dsn_to_string($dsn);
                }
            }

            if (empty($company->id)) { // Create
                // Make pending by default. Only pending will be picked up by deployer.
                $data['status'] = 'pending';
            }

            // Make pending by default. Only pending will be picked up by deployer.
            $data['status'] = 'pending';
        }

        $filter = hooks()->apply_filters('perfex_saas_module_tenant_data_payload', ['data' => $data, 'tenant' => $company, 'invoice' => $invoice]);
        $data = $filter['data'];

        if (isset($data['dsn']) && !is_string($data['dsn'])) {
            throw new \Exception("DSN must be provided in string format", 1);
        }

        // Updating
        if (!$creating_new) {
            if (!$creating_as_admin) {
                unset($data['status']);
            }

            // Ensure slug is not updated
            if (isset($data['slug'])) {
                unset($data['slug']);
            }
        }

        // Admin options
        if (isset($data['db_scheme'])) {
            unset($data['db_scheme']);
        }

        if (isset($data['db_pools'])) {
            unset($data['db_pools']);
        }

        // Encrypt any DSN info to be saved to the DB
        if (empty($company->id) && isset($data['dsn']) && !empty($data['dsn'])) {
            $data['dsn'] = $this->encryption->encrypt($data['dsn']);
        }

        $old_metadata = (array)(isset($company->metadata) ? $company->metadata : []);
        if (isset($data['metadata'])) {
            $data['metadata'] = json_encode(array_merge($old_metadata, $data['metadata']));
        }

        // Save and make deployment another job
        $_id = $this->add_or_update('companies', $data);

        // Trigger module setup for the tenant
        if (!defined('CRON'))
            perfex_saas_trigger_module_install('', $company->slug ?? $data['slug']);

        hooks()->do_action('perfex_saas_module_tenant_created_or_updated', $_id);

        return $_id;
    }


    /**
     * Generate a client invoice.
     *
     * @param mixed $clientid The client ID.
     * @param mixed $packageid The package ID.
     * @return mixed The generated company invoice.
     * @throws \Exception When certain conditions are not met.
     */
    public function generate_company_invoice($clientid, $packageid)
    {
        $package = $this->packages($packageid);

        $metadata = $package->metadata;
        $old_invoice = $this->get_company_invoice($clientid);

        $date = date('Y-m-d');
        $duedate =  $date;
        $will_trial = false;
        $on_trial = false;
        $can_trial = !isset($old_invoice->id);

        if ($old_invoice) {

            // Validation: Ensure number of instances fit.
            if ((int)$package->metadata->max_instance_limit > 0) {
                // Count the user instances.
                $this->db->where('clientid', $clientid);
                $companies = $this->companies();
                if ($companies && count($companies) > (int)$package->metadata->max_instance_limit)
                    throw new \Exception(_l('perfex_saas_plan_upgrade_unfit_number_of_instances'), 1);

                // Confirm each company quota matches with new one.
                $package_quota = $package->metadata->limitations ?? [];
                $limited_resources = [];
                // Let filter out the resources that are not unlimited to save time complexity in next loop
                foreach ($package_quota as $res => $quota) {
                    if ((int)$quota >= 0) $limited_resources[] = $res;
                }

                foreach ($companies as $company) {
                    $usage_limits = perfex_saas_get_tenant_quota_usage($company, $limited_resources, $old_invoice);
                    $company->package_invoice = $old_invoice;
                    foreach ($usage_limits as $resources => $usage) {
                        $quota = perfex_saas_tenant_resources_quota($company, $resources);
                        if ($quota !== -1 && $usage > $quota) {
                            throw new \Exception(_l('perfex_saas_plan_upgrade_unfit_quota', [$company->name, $resources]), 1);
                        }
                    }
                }
            }

            // Start subscription from trial
            $on_trial = $old_invoice->status == Invoices_model::STATUS_DRAFT;
            if ($on_trial) {
                $data = ["status" => Invoices_model::STATUS_UNPAID, "duedate" => $duedate];
                $this->invoices_model->db->update(perfex_saas_master_db_prefix() . 'invoices', $data, ['id' => $old_invoice->id]);
                $this->invoices_model->change_invoice_number_when_status_draft($old_invoice->id);
                return $this->get_company_invoice($clientid);
            }

            // Ensure package changing
            if ($old_invoice->{perfex_saas_column('packageid')} == $packageid)
                throw new \Exception(_l("perfex_saas_no_change_detected"), 1);

            // Change of plan. Cancel existing invoices and its children
            $stale_invoices = $this->get_company_invoice_child_invoices((int)$old_invoice->id, false);
            $stale_invoices[] = $old_invoice;

            foreach ($stale_invoices as $_invoice) {

                // Mark as cancelled
                if (!$this->invoices_model->mark_as_cancelled($_invoice->id))
                    throw new \Exception(_l('perfex_saas_invoice_cancel_error'), 1);

                // Mark as non recurring (so the child invoice will not be recreated by cron)
                if ($_invoice->recurring != "0")
                    $this->invoices_model->db->update(perfex_saas_master_db_prefix() . 'invoices', ["recurring" => "0"], ['id' => $_invoice->id]);
            }
        }

        if ($can_trial) {
            $duedate = date('Y-m-d', strtotime("+$package->trial_period days"));
            $will_trial = true;
        }

        $next_invoice_number = get_option('next_invoice_number');
        $invoice_number      = str_pad($next_invoice_number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);

        // Payments options
        $payment_modes = $metadata->invoice->allowed_payment_modes ?? [];
        if (empty($payment_modes)) {
            $all_payment_modes = $this->payment_modes_model->get();
            foreach ($all_payment_modes as $pmode) {
                $payment_modes[] = $pmode['id'];
            }
        }

        $taxes = $metadata->invoice->taxname;
        $client = $this->clients_model->get($clientid);

        $new_items = [
            [
                "order" => "1",
                "description" => _l('perfex_saas_invoice_desc_subscription', $package->name),
                "long_description" => "",
                "qty" => "1",
                "unit" => "",
                "rate" => $package->price,
                "taxname" => $taxes
            ]
        ];

        $subtotal = $package->price;

        // Add old invoice customization items
        if ($old_invoice) {
            $items = get_items_by_type('invoice', $old_invoice->id);
            foreach ($items as $key => $item) {

                if ($item['item_order'] == '1') continue;

                $unit_price = (float)$item['rate'];
                $quantity = (int)$item['qty'];

                $new_items[] = [
                    "order" => $item['item_order'],
                    "description" => $item['description'],
                    "long_description" => $item['long_description'] ?? '',
                    "qty" => $quantity,
                    "unit" => $item['unit'],
                    "rate" => $unit_price,
                    "taxname" => $taxes
                ];
                $subtotal = $subtotal + ($unit_price * $quantity);
            }
        }

        $data = [
            "clientid" => $clientid,
            "number" => $invoice_number,
            "date" => $date,
            "duedate" => $duedate,
            "tags" => PERFEX_SAAS_FILTER_TAG,
            "allowed_payment_modes" => $payment_modes,
            "currency" => get_base_currency()->id,
            "sale_agent" => $metadata->invoice->sale_agent ?? "",
            "recurring" => $metadata->invoice->recurring ?? "1",
            "repeat_every_custom" => $metadata->invoice->repeat_every_custom ?? "",
            "repeat_type_custom" => $metadata->invoice->repeat_type_custom ?? "",
            "show_quantity_as" => "1",
            "newitems" => $new_items,
            "subtotal" => $subtotal,
            "discount_percent" => "0",
            "discount_total" => "0.00",
            "adjustment" => "0",
            "total" => $subtotal,
            "billing_street"   => $client->billing_street,
            "billing_city"     => $client->billing_city,
            "billing_state"    => $client->billing_state,
            "billing_zip"      => $client->billing_zip,
            "billing_country"  => $client->billing_country,
            "shipping_street"  => $client->shipping_street,
            "shipping_city"    => $client->shipping_city,
            "shipping_state"   => $client->shipping_state,
            "shipping_zip"     => $client->shipping_zip,
            "shipping_country" => $client->shipping_country,
        ];

        // Set taxes
        if (!empty($taxes)) {
            $total_tax = 0;
            foreach ($taxes as $key => $tax) {
                $tax = explode('|', $tax);
                $tax_amount = (float)end($tax);
                $total_tax += (($tax_amount / 100) * $data["subtotal"]);
            }
            $data["total"] = (float)$data["subtotal"] + $total_tax;
        }

        if ($will_trial) {
            $data["status"] =  Invoices_model::STATUS_DRAFT;
        }

        // mark as paid if zero invoice
        if ($data["total"] == 0) {
            $data["status"] = Invoices_model::STATUS_PAID;
        }

        // Important
        $data[perfex_saas_column('packageid')] = $packageid;

        if (!$this->invoices_model->add($data)) {
            throw new \Exception(((object)$this->db->error())->message, 1);
        }

        $invoice = $this->get_company_invoice($clientid);

        if ($on_trial && $invoice) {
            update_invoice_status($invoice->id, true);
        }

        return $invoice;
    }

    public function update_company_invoice($invoice, $clientid, $custom_limitations)
    {
        $package = $this->packages($invoice->{perfex_saas_column('packageid')});
        $metadata = $package->metadata;
        $discounts = $package->metadata->formatted_discounts ?? [];
        $client = $this->clients_model->get($clientid);

        // Update invoice with new items
        $items       = get_items_by_type('invoice', $invoice->id);
        $taxes = $metadata->invoice->taxname ?? [];
        $subtotal = (float)$package->price;
        $total_discount = 0;
        $new_items = [
            [
                "order" => "1",
                "description" => _l('perfex_saas_invoice_desc_subscription', $package->name),
                "long_description" => "",
                "qty" => "1",
                "unit" => "",
                "rate" => $package->price,
                "taxname" => $taxes
            ]
        ];

        $order = 2;
        foreach ($custom_limitations as $key => $limit) {
            $quantity = (int)$limit['quantity'];
            $resources = $limit['resources'];
            $unit_price = (float)$limit['unit_price'];

            if ($quantity < 1) {
                continue;
            }

            $discount = $discounts->{$resources} ?? [];

            if (!empty($discount)) {
                arsort($discount);
                foreach ($discount as $level => $value) {
                    $level = (int)$level;
                    if ($quantity >= $level) {
                        $percent = ((float)$value['percent']) / 100;
                        $discount_amount =  ($unit_price * $percent);
                        $total_discount += $discount_amount;
                        $unit_price = $unit_price - $discount_amount;
                        break;
                    }
                }
            }

            $subtotal = $subtotal + ($unit_price * $quantity);

            $new_items[] = [
                "order" => $order,
                "description" => $limit['description'],
                "long_description" => $limit['long_description'] ?? '',
                "qty" => $quantity,
                "unit" => "",
                "rate" => $unit_price,
                "taxname" => $taxes
            ];
            $order++;
        }

        // Payments options
        $payment_modes = $metadata->invoice->allowed_payment_modes ?? [];
        if (empty($payment_modes)) {
            $all_payment_modes = $this->payment_modes_model->get();
            foreach ($all_payment_modes as $pmode) {
                $payment_modes[] = $pmode['id'];
            }
        }

        $data = [
            "clientid" => $clientid,
            "date" => $invoice->date,
            "duedate" => $invoice->duedate,
            "tags" => PERFEX_SAAS_FILTER_TAG,
            "allowed_payment_modes" => $payment_modes,
            "currency" => get_base_currency()->id,
            "sale_agent" => $metadata->invoice->sale_agent ?? "",
            "recurring" => $metadata->invoice->recurring ?? "1",
            "repeat_every_custom" => $metadata->invoice->repeat_every_custom ?? "",
            "repeat_type_custom" => $metadata->invoice->repeat_type_custom ?? "",

            "show_quantity_as" => "1",
            "newitems" => $new_items,
            "subtotal" => $subtotal,
            "discount_percent" => "0",
            "discount_total" => "0.00",
            "total" => $subtotal,
            "removed_items" => array_column($items, 'id'),

            "billing_street"   => $client->billing_street,
            "billing_city"     => $client->billing_city,
            "billing_state"    => $client->billing_state,
            "billing_zip"      => $client->billing_zip,
            "billing_country"  => $client->billing_country,
            "shipping_street"  => $client->shipping_street,
            "shipping_city"    => $client->shipping_city,
            "shipping_state"   => $client->shipping_state,
            "shipping_zip"     => $client->shipping_zip,
            "shipping_country" => $client->shipping_country,

            "status" => $invoice->status
        ];

        // Apply tax
        if (!empty($taxes)) {
            $total_tax = 0;
            foreach ($taxes as $key => $tax) {
                $tax = explode('|', $tax);
                $tax_amount = (float)end($tax);
                $total_tax += (($tax_amount / 100) * $data['subtotal']);
            }
            $data["total"] = (float)$data["subtotal"] + $total_tax;
        }

        // Important
        $data[perfex_saas_column('packageid')] = $package->id;

        if (!$this->invoices_model->update($data, $invoice->id)) {
            throw new \Exception(((object)$this->db->error())->message, 1);
        }

        return true;
    }


    /**
     * Get a company by its slug.
     *
     * @param string $slug The company slug.
     * @param string $clientid The client ID.
     * @return mixed The company with the given slug.
     */
    public function get_company_by_slug($slug, $clientid = '')
    {
        if ($clientid) {
            $this->db->where('clientid', $clientid);
        }
        return $this->get_entity_by_slug('companies', $slug, 'parse_company');
    }

    /**
     * Get a company invoice.
     *
     * @param mixed $clientid The client ID.
     * @param array $options The optional option params
     * @return mixed The company invoice.
     */
    public function get_company_invoice($clientid, $options = [])
    {
        if (!class_exists('Invoices_model'))
            $this->load->model('invoices_model');

        $dbprefix = perfex_saas_master_db_prefix();
        $packageTable = perfex_saas_table('packages');
        $invoiceTable = $dbprefix . 'invoices';
        $invoicePackageCol = perfex_saas_column('packageid');

        $this->db->where($invoicePackageCol . ' IS NOT NULL'); // Must have packageid
        $this->db->where('recurring >', '0'); // Must be recurring

        if (!isset($options['include_cancelled'])) {
            $this->db->where("`$invoiceTable.status` !=", Invoices_model::STATUS_CANCELLED); // Must not be cancelled
        }
        $this->db->where('clientid', $clientid);
        $this->db->select("$invoiceTable.*, clientid, name, description, slug, price, bill_interval, is_default, is_private, db_scheme, db_pools, $packageTable.status as package_status, modules, metadata, trial_period");
        $this->db->join($packageTable, $packageTable . '.id = ' . $invoicePackageCol, 'inner');
        $this->db->join($dbprefix . 'clients', $dbprefix . 'clients.userid = ' . $invoiceTable . '.clientid', 'inner');
        $this->db->order_by($invoiceTable . '.datecreated', 'DESC');
        $invoice = $this->db->from($invoiceTable)->get()->row();

        if (!$invoice) return $invoice;

        if (!isset($options['skip_children'])) {
            // get children invoices for the recurring invoice that is either unpaid,overdue or partially paid
            $unpaid_child = $this->get_company_invoice_child_invoices((int)$invoice->id);
            if ($unpaid_child) {
                unset($unpaid_child->{$invoicePackageCol});
                $invoice = (object)array_merge((array)$invoice, (array)$unpaid_child);
            }
        }

        // Add invoice customization
        $invoice->custom_limits = (object)[];
        $invoice->purchased_modules = (object)[];
        $client_metadata = $this->db->where('clientid', $clientid)->from(perfex_saas_table('client_metadata'))->get()->row();
        if (!empty($client_metadata->metadata)) {
            $invoice->custom_limits = (object)(json_decode($client_metadata->metadata)->custom_limits ?? []);
            $invoice->purchased_modules = (object)(json_decode($client_metadata->metadata)->purchased_modules ?? []);
        }

        return $this->parse_package($invoice);
    }

    /**
     * Get company invoice child recurring invoices that is/are either unpaid/overdue or partially paid.
     * It return child invoices created out of renewal.
     *
     * @param int $invoiceid
     * @param boolean $single_row if to return single row or all matches
     * @return mixed
     */
    function get_company_invoice_child_invoices($invoiceid, $single_row = true)
    {
        $this->db->select('*');
        $this->db->where('is_recurring_from', $invoiceid);
        $this->db->where('status <', Invoices_model::STATUS_CANCELLED); // not cancelled or draft
        $this->db->where('status !=', Invoices_model::STATUS_PAID); // not paid
        $unpaid_child = $this->db->get(perfex_saas_master_db_prefix() . 'invoices');
        return $single_row ? $unpaid_child->row() : $unpaid_child->result_object();
    }

    /**
     * Get invoice total from all statuses
     * @since  Version 0.0.5
     * @param  mixed $data
     * @return array
     */
    public function get_invoices_total($data)
    {
        $this->load->model('currencies_model');
        $this->load->model('invoices_model');

        if (isset($data['currency'])) {
            $currencyid = $data['currency'];
        } else {
            $currencyid = $this->currencies_model->get_base_currency()->id;
        }

        $result            = [];
        $result['due']     = [];
        $result['paid']    = [];
        $result['overdue'] = [];

        $has_permission_view                = has_permission('invoices', '', 'view');
        $noPermissionsQuery                 = get_invoices_where_sql_for_staff(get_staff_user_id());

        $dbprefix = perfex_saas_master_db_prefix();

        for ($i = 1; $i <= 3; $i++) {
            $select = 'id,total';
            if ($i == 1) {
                $select .= ', (SELECT total - (SELECT COALESCE(SUM(amount),0) FROM ' . $dbprefix . 'invoicepaymentrecords WHERE invoiceid = ' . $dbprefix . 'invoices.id) - (SELECT COALESCE(SUM(amount),0) FROM ' . $dbprefix . 'credits WHERE ' . $dbprefix . 'credits.invoice_id=' . $dbprefix . 'invoices.id)) as outstanding';
            } elseif ($i == 2) {
                $select .= ',(SELECT SUM(amount) FROM ' . $dbprefix . 'invoicepaymentrecords WHERE invoiceid=' . $dbprefix . 'invoices.id) as total_paid';
            }
            $this->db->select($select);
            $this->db->from($dbprefix . 'invoices');
            $this->db->where('currency', $currencyid);

            // Must be recurring
            $this->db->where('recurring >', '0');
            // Must have packageid
            $this->db->where(perfex_saas_column('packageid') . ' IS NOT NULL');

            // Exclude cancelled invoices
            $this->db->where('status !=', Invoices_model::STATUS_CANCELLED);
            // Exclude draft
            $this->db->where('status !=', Invoices_model::STATUS_DRAFT);

            if (isset($data['project_id']) && $data['project_id'] != '') {
                $this->db->where('project_id', $data['project_id']);
            } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
                $this->db->where('clientid', $data['customer_id']);
            }

            if ($i == 3) {
                $this->db->where('status', Invoices_model::STATUS_OVERDUE);
            } elseif ($i == 1) {
                $this->db->where('status !=', Invoices_model::STATUS_PAID);
            }

            if (isset($data['years']) && count($data['years']) > 0) {
                $this->db->where_in('YEAR(date)', $data['years']);
            }

            if (!$has_permission_view) {
                $whereUser = $noPermissionsQuery;
                $this->db->where('(' . $whereUser . ')');
            }

            $invoices = $this->db->get()->result_array();

            foreach ($invoices as $invoice) {
                if ($i == 1) {
                    $result['due'][] = $invoice['outstanding'];
                } elseif ($i == 2) {
                    $result['paid'][] = $invoice['total_paid'];
                } elseif ($i == 3) {
                    $result['overdue'][] = $invoice['total'];
                }
            }
        }
        $currency             = get_currency($currencyid);
        $result['due']        = array_sum($result['due']);
        $result['paid']       = array_sum($result['paid']);
        $result['overdue']    = array_sum($result['overdue']);
        $result['currency']   = $currency;
        $result['currencyid'] = $currencyid;

        return $result;
    }
}
