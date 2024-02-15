<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This is a client class for managing instances and subscribing to a package.
 */
class Perfex_saas_client extends ClientsController
{
    /**
     * Common url to redirect to
     *
     * @var string
     */
    public $redirect_url = '';

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();

        // Load essensial models
        $this->load->model('payment_modes_model');
        $this->load->model('invoices_model');
        $this->load->model('currencies_model');

        $this->redirect_url = base_url('clients?companies');

        if (!is_client_logged_in()) {
            return redirect($this->redirect_url);
        }
    }

    /**
     * Method to create a company instance
     *
     * @return void
     */
    public function create()
    {
        if (!$this->input->post()) {
            return show_404();
        }
        return $this->create_or_edit_company();
    }

    /**
     * Method to handle company editing
     *
     * @param string $slug
     * @return void
     */
    public function edit($slug)
    {
        if (!$this->input->post()) {
            return show_404();
        }
        $id = $this->get_auth_company_by_slug($slug)->id;
        return $this->create_or_edit_company($id);
    }

    /**
     * Method to deploy a company instance (AJAX)
     *
     * @return void
     */
    public function deploy()
    {
        echo json_encode(perfex_saas_deployer('', get_client_user_id()));
        exit();
    }

    /**
     * Method to delete a company instance
     *
     * @param string $slug
     * @return void
     */
    public function delete($slug)
    {
        $company = $this->get_auth_company_by_slug($slug);
        $id = (int)$company->id;

        if ($this->input->post()) {

            $removed = false;
            try {
                perfex_saas_remove_company($company);
            } catch (\Throwable $th) {
                $removed = $th->getMessage();
            }

            if ($this->perfex_saas_model->delete('companies', $id))
                set_alert('success', _l('deleted', _l('perfex_saas_company')) . ($removed !== true ? ' With error: ' . $removed : ''));

            set_alert('success', _l('deleted', _l('perfex_saas_company')));
        }

        return redirect($this->redirect_url);
    }

    /**
     * Method to subscribe to a package.
     * It assign the package to user and generate and invoice using perfex invoicing system.
     *
     * @param string $packageslug
     * @return void
     */
    public function subscribe($packageslug)
    {
        try {

            $clientid = get_client_user_id();
            $package = $this->perfex_saas_model->get_entity_by_slug('packages', $packageslug);
            $invoice = $this->perfex_saas_model->generate_company_invoice($clientid, $package->id);

            // Ensure we have the invoice created
            if (!$invoice) {
                set_alert('danger', _l('perfex_saas_error_creating_invoice'));
                return perfex_saas_redirect_back();
            }

            $this->db->where('clientid', $clientid);
            $companies = $this->perfex_saas_model->companies();

            if (empty($companies)) {

                if (get_option('perfex_saas_autocreate_first_company') == '1') {

                    // Create defalt company for the client
                    $company_name = get_client(get_client_user_id())->company;
                    $data = [
                        'name' => empty($company_name) ? 'Company#1' : $company_name,
                        'clientid' => $clientid
                    ];

                    // Add custom domain and subdomain from session if any
                    $data = hooks()->apply_filters('perfex_saas_create_instance_data', $data);

                    $_id = $this->perfex_saas_model->create_or_update_company($data, $invoice);

                    hooks()->do_action('perfex_saas_after_client_create_instance', $_id);

                    $custom_domain = $data['custom_domain'] ?? '';

                    // Notify supper admin on custom domain if needed
                    if (!empty($custom_domain)) {
                        $company = $this->perfex_saas_model->companies($_id);
                        perfex_saas_send_customdomain_request_notice($company, $custom_domain, $invoice);
                    }
                }
            }

            if (!in_array($invoice->status, [Invoices_model::STATUS_DRAFT, Invoices_model::STATUS_PAID]))
                return redirect(base_url("invoice/$invoice->id/$invoice->hash"));

            set_alert('success', _l('added_successfully', _l('invoice')));

            return redirect(base_url('clients?companies'));
        } catch (\Throwable $th) {

            set_alert('danger', $th->getMessage());
        }

        return perfex_saas_redirect_back();
    }

    /**
     * Method to subscribe to a package.
     * It assign the package to user and generate and invoice using perfex invoicing system.
     *
     * @param string $packageslug
     * @return void
     */
    public function my_account()
    {
        $clientid = get_client_user_id();

        // Check if the client has a subscription i.e invoice
        $invoice = $this->perfex_saas_model->get_company_invoice($clientid);
        if (empty($invoice->db_scheme)) {
            set_alert('danger', _l('perfex_saas_no_invoice_client'));
            return redirect(base_url('clients/?companies'));
        }

        $package = $this->perfex_saas_model->packages($invoice->{perfex_saas_column('packageid')});
        if (!perfex_saas_is_single_package_mode() && ($package->metadata->allow_customization ?? '') === 'no') {
            set_alert('danger', _l('perfex_saas_permission_denied'));
            return redirect(base_url('clients/?subscription'));
        }

        // Save and update invoice
        if (!empty($this->input->post())) {

            $custom_limits = $this->input->post('custom_limits', true);
            $purchased_modules = $this->input->post('purchased_modules', true);

            try {
                // Build invoice items without discounts. Discounts will be applied in model.
                $custom_limitations = [];
                if (!empty($custom_limits)) {
                    foreach ($custom_limits as $resources => $quantity) {

                        $quantity = (int)$quantity;
                        if ($quantity < 1) continue;

                        $is_storage = $resources === 'storage';
                        $unit_price = $is_storage ? ($package->metadata->storage_limit->unit_price ?? 0) : ($package->metadata->limitations_unit_price->{$resources} ?? 0);
                        $unit_price = (float)$unit_price;

                        $custom_limitations[] = [
                            'resources' => $resources,
                            'quantity' => $quantity,
                            'description' => _l('perfex_saas_invoice_addon_item_desc', _l('perfex_saas_limit_' . $resources)),
                            'unit_price' => $unit_price,
                        ];
                    }
                }

                if (!empty($purchased_modules)) {
                    $modules = $this->perfex_saas_model->modules();
                    foreach ($purchased_modules as $index => $module) {

                        $price = $package->metadata->limitations_unit_price->{$module} ?? ($modules[$module]['price'] ?? 0);
                        if (!$price) {
                            unset($purchased_modules[$index]);
                            continue;
                        }

                        $custom_limitations[] = [
                            'resources' => $module,
                            'quantity' => 1,
                            'description' => _l('perfex_saas_invoice_addon_module_item_desc', $modules[$module]['custom_name']),
                            //"long_description" => $modules[$module]['description'] ?? "",
                            'unit_price' => $price,
                        ];
                    }
                }

                if (!empty($custom_limitations) && $this->perfex_saas_model->update_company_invoice($invoice, $clientid, $custom_limitations)) {

                    // Save new custom limit to DB
                    $client_metadata = $this->db->where('clientid', $clientid)->from(perfex_saas_table('client_metadata'))->get()->row();
                    $metadata = empty($client_metadata->metadata) ? [] : (array)json_decode($client_metadata->metadata);
                    $metadata = array_merge($metadata, ['custom_limits' => $custom_limits, 'purchased_modules' => $purchased_modules]);
                    $data = ['metadata' => json_encode($metadata), 'clientid' => $clientid, 'id' => $client_metadata->id ?? null];

                    if ($this->perfex_saas_model->add_or_update('client_metadata', $data))
                        $invoice = $this->perfex_saas_model->get_company_invoice($clientid);

                    set_alert('success', _l('updated_successfully', _l('invoice')));

                    if (!in_array($invoice->status, [Invoices_model::STATUS_DRAFT, Invoices_model::STATUS_PAID]))
                        return redirect('invoice/' . $invoice->id . '/' . $invoice->hash);
                }
            } catch (\Throwable $th) {

                set_alert('danger', $th->getMessage());
            }
        }

        $data['title'] = _l('perfex_saas_pricing');
        $data['package'] = $package;
        $data['invoice'] = $invoice;
        $this->data($data);
        $this->view('client/my_account');
        $this->layout();
    }

    /**
     * Method to validate the client's invoice.
     *
     * @param $clientid
     * @return object|false The invoice object if valid, false otherwise.
     */
    private function validate_client_invoice($clientid)
    {
        $invoice = $this->perfex_saas_model->get_company_invoice($clientid);

        if (empty($invoice->db_scheme)) {
            set_alert('danger', _l('perfex_saas_no_invoice_client'));
            return false;
        }

        $on_trial = $invoice->status == Invoices_model::STATUS_DRAFT;
        $days_left = $on_trial ? (int) perfex_saas_get_days_until($invoice->duedate) : '';
        if ($on_trial && $days_left < 1) {
            set_alert('danger', _l('perfex_saas_trial_invoice_over_not'));
            return false;
        }

        if (!$on_trial && $invoice->status != Invoices_model::STATUS_PAID) {
            set_alert('danger', _l('perfex_saas_clear_unpaid_invoice_note'));
            redirect(base_url("invoice/$invoice->id/$invoice->hash"));
            return false;
        }

        return $invoice;
    }

    /**
     * Method to get a company by slug and ensure it belongs to the logged-in client.
     * Will redirect if failed.
     *
     * @param string $slug The slug of the company.
     * @return mixed The company object if found and authorized, or void otherwise.
     */
    private function get_auth_company_by_slug($slug)
    {
        $clientid = get_client_user_id();

        // Get company and validate
        $company = $this->perfex_saas_model->get_company_by_slug($slug, $clientid);

        if (empty($company)) {
            redirect($this->redirect_url);
        }

        if ($clientid != $company->clientid) {
            return access_denied('perfex_saas_companies');
        }

        return $company;
    }

    /**
     * Common method to handle create or edit form submission.
     * Client company form validation and execution are summarized in this method.
     *
     * @param string $id ID of the company to edit (optional)
     * @return void
     */
    private function create_or_edit_company($id = '')
    {
        $clientid = get_client_user_id();

        // Check if the client has a subscription i.e invoice and it's not unpaid
        if (($invoice = $this->validate_client_invoice($clientid)) === false) {
            return redirect($this->redirect_url);
        }

        // Company form validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules('name', _l('perfex_saas_name'), 'required');
        if ($this->form_validation->run() === false) {
            set_alert('danger', validation_errors());
            return redirect($this->redirect_url);
        }

        try {
            $form_data = $this->input->post(NULL, true);

            $data = ['name' => $form_data['name']];
            $data['clientid'] = $clientid;

            $data['custom_domain'] = $form_data['custom_domain'] ?? '';
            $custom_domain = $data['custom_domain'];

            // Add disabled modules
            $disabled_modules = $form_data['disabled_modules'] ?? [];
            $data['metadata'] = ['disabled_modules' => $disabled_modules];

            if (!empty($id)) {
                $data['id'] = $id;
            } else {
                // Creating new
                $data['slug'] = $form_data['slug'] ?? '';
            }

            // save to db
            $_id = $this->perfex_saas_model->create_or_update_company($data, $invoice);
            if ($_id) {

                // Notify supper admin on domain update
                if (!empty($custom_domain)) {
                    $company = $this->perfex_saas_model->companies($_id);
                    perfex_saas_send_customdomain_request_notice($company, $custom_domain, $invoice);
                }

                set_alert('success', _l(empty($id) ? 'added_successfully' : 'updated_successfully', _l('perfex_saas_company')));
                return redirect($this->redirect_url);
            }

            // Log error
            log_message('error', _l('perfex_saas_error_completing_action') . ':' . ($this->db->error() ?? ''));

            throw new \Exception(_l('perfex_saas_error_completing_action'), 1);
        } catch (\Exception $e) {
            set_alert('danger', $e->getMessage());
            return redirect($this->redirect_url);
        }
    }
}
