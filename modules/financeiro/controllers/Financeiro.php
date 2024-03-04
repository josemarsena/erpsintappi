<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Financeiro extends AdminController
{
	public function __construct()
    {
        parent::__construct();
        $this->load->model('financeiro_model');
        $this->load->model('bancos_model');

    }

    /**
     * manage transaction
     * @return view
     */


    public function dashboard()
    {
        // Checa as permissoes
        if (!has_permission('financeiro_dashboard', '', 'view')) {
            access_denied('financeiro_dashboard');
        }
        $data['title'] = 'Financeiro';


        $this->load->view('dashboard/manage', $data);

    }


    public function bancos()
    {

        // Checa as Permissões para os Bancos
        if (!has_permission('financeiro_bancos', '', 'view')) {
            access_denied('financeiro_bancos');
        }

        $data['title'] = 'Bancos Brasileiros';

        if ($this->input->post()) {
            $message          = '';
            $data             = $this->input->post();
            if ($data['id'] == '') {
                if(!has_permission('financeiro_bancos','','create') && !is_admin()){
                    access_denied('financeiro_bancos');
                }
                $id = $this->bancos_model->add($data);
                if ($id) {
                    $success = true;
                    $message = _l('added_successfully');
                    set_alert('success', $message);
                }
                redirect(admin_url('financeiro/bancos'));
            } else {
                if(!has_permission('financeiro_bancos','','edit') && !is_admin()){
                    access_denied('financeiro_bancos');
                }

                $success = $this->bancos_model->update($data);
                if ($success) {
                    $message = _l('updated_successfully');
                    set_alert('success', $message);
                }
                redirect(admin_url('financeiro/bancos'));
            }
            die;
        }

        $data['bancos'] = $this->bancos_model->get();

        $this->load->view('bancos/cadbancos', $data);
    }


    public function contasbancarias()
    {
        if (!has_permission('financeiro_contasbancarias', '', 'view')) {
            access_denied('financeiro_contasbancarias');
        }
        $data['title'] = 'Contas Bancárias';
        $this->load->model('currencies_model');

        $data['currency'] = $this->currencies_model->get_base_currency();
        $data['currencys'] = $this->currencies_model->get();

        $data_filter = ['date' => 'last_30_days'];

        $this->load->view('bancos/cadcontasbanco', $data);

    }


    public function contaspagar()
    {

        if (!has_permission('financeiro_contaspagar', '', 'view')) {
            access_denied('financeiro_contaspagar');
        }

        $this->load->model('invoices_model');
        $this->load->model('credit_notes_model');

        if (staff_cant('view', 'invoices')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('invoices');
        }

        close_setup_menu();

        $this->load->model('payment_modes_model');
        $data['payment_modes']        = $this->payment_modes_model->get('', [], true);
        $data['invoiceid']            = $id;
        $data['title']                = 'Contas a Pagar';
        $data['invoices_years']       = $this->invoices_model->get_invoices_years();
        $data['invoices_sale_agents'] = $this->invoices_model->get_sale_agents();
        $data['invoices_statuses']    = $this->invoices_model->get_statuses();
        $data['invoices_table'] = App_table::find('invoices');
        $data['bodyclass']            = 'invoices-total-manual';
        $this->load->view('contaspagar/manage', $data);

    }
    public function contasreceber()
    {

        if (!has_permission('financeiro_contaspagar', '', 'view')) {
            access_denied('financeiro_contaspagar');
        }

        $this->load->model('invoices_model');
        $this->load->model('credit_notes_model');

        if (staff_cant('view', 'invoices')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('invoices');
        }

        close_setup_menu();

        $this->load->model('payment_modes_model');
        $data['payment_modes']        = $this->payment_modes_model->get('', [], true);
        $data['invoiceid']            = $id;
        $data['title']                = 'Contas a Pagar';
        $data['invoices_years']       = $this->invoices_model->get_invoices_years();
        $data['invoices_sale_agents'] = $this->invoices_model->get_sale_agents();
        $data['invoices_statuses']    = $this->invoices_model->get_statuses();
        $data['invoices_table'] = App_table::find('invoices');
        $data['bodyclass']            = 'invoices-total-manual';
        $this->load->view('contaspagar/manage', $data);

    }

 }