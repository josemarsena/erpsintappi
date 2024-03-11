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
        $this->load->model('bancos_model');
        close_setup_menu();

        if (staff_cant('view', 'bancos') && staff_cant('view_own', 'bancos')) {
            access_denied('bancos');
        }

 //       $data['table'] = App_table::find('fin_bancos');

        // Checa as Permissões para os Bancos
        if (!has_permission('financeiro_bancos', '', 'view')) {
            access_denied('financeiro_bancos');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('bancos');
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

       // $data['table'] = $this->bancos_model->get();
        $this->load->view('bancos/gerenciar', $data);
    }


    public function contasbancarias()
    {
        if (!has_permission('financeiro_contasbancarias', '', 'view')) {
            access_denied('financeiro_contasbancarias');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('contasbancarias');
        }

        $data['title'] = 'Contas Bancárias';
        $this->load->model('contasbancarias_model');
        if ($this->input->post()) {
            $message          = '';
            $data             = $this->input->post();
            if ($data['id'] == '') {
                if(!has_permission('financeiro_bancos','','create') && !is_admin()){
                    access_denied('financeiro_bancos');
                }

                $id = $this->contasbancarias_model->add($data);
                if ($id) {
                    $success = true;
                    $message = _l('added_successfully');
                    set_alert('success', $message);
                }
                redirect(admin_url('financeiro/contasbancarias'));

            } else {
                if(!has_permission('financeiro_contasbancarias','','edit') && !is_admin()){
                    access_denied('financeiro_contasbancarias');
                }

                $success = $this->contasbancarias_model->update($data);
                if ($success) {
                    $message = _l('updated_successfully');
                    set_alert('success', $message);
                }
                redirect(admin_url('financeiro/contasbancarias'));
            }
            die;
        }


        $this->load->view('contasbancarias/gerenciar', $data);

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


    public function table_bancos()
    {
        if (
            staff_cant('view', 'bancos')
            && staff_cant('view_own', 'bancos')
            && get_option('allow_staff_view_bancos_assigned') == 0
        ) {
            ajax_access_denied();
        }


        $this->app->get_table_data(module_views_path(FINANCEIRO_MODULE_NAME, 'tables/bancos'));
        $this->app->get_table_data('bancos');


   //     App_table::find('bancos')->output();
    }


    public function table_contasbancarias()
    {
        if (
            staff_cant('view', 'contasbancarias')
            && staff_cant('view_own', 'contasbancarias')
            && get_option('allow_staff_view_contasbancarias_assigned') == 0
        ) {
            ajax_access_denied();
        }


        $this->app->get_table_data(module_views_path(FINANCEIRO_MODULE_NAME, 'tables/contasbancarias'));
        $this->app->get_table_data('contasbancarias');


        //     App_table::find('bancos')->output();
    }
    public function adicionar_banco()
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['message'] = html_purify($this->input->post('message', false));
            $id = $this->tickets_model->add($data, get_staff_user_id());
            if ($id) {
                set_alert('success', _l('new_ticket_added_successfully', $id));
                redirect(admin_url('helpdesk/ticket/' . $id));
            }
        }
        if ($userid !== false) {
            $data['userid'] = $userid;
            $data['client'] = $this->clients_model->get($userid);
        }
        // Carregas os Modelos necessários


        $whereStaff = [];
        if (get_option('access_tickets_to_none_staff_members') == 0) {
            $whereStaff['is_not_staff'] = 0;
        }

        $data['title'] = 'Novo Banco';

        if ($this->input->get('project_id') && $this->input->get('project_id') > 0) {
            // request from project area to create new ticket
            $data['project_id'] = $this->input->get('project_id');
            $data['userid'] = get_client_id_by_project_id($data['project_id']);
            if (total_rows(db_prefix() . 'contacts', ['active' => 1, 'userid' => $data['userid']]) == 1) {
                $contact = $this->clients_model->get_contacts($data['userid']);
                if (isset($contact[0])) {
                    $data['contact'] = $contact[0];
                }
            }
        } elseif ($this->input->get('contact_id') && $this->input->get('contact_id') > 0 && $this->input->get('userid')) {
            $contact_id = $this->input->get('contact_id');
            if (total_rows(db_prefix() . 'contacts', ['active' => 1, 'id' => $contact_id]) == 1) {
                $contact = $this->clients_model->get_contact($contact_id);
                if ($contact) {
                    $data['contact'] = (array)$contact;
                }
            }
        }

        $this->load->view('financeiro/bancos/adicionar_banco', $data);

    }

    public function adicionar_contabancaria($id = '')
    {
        $this->load->model('contasbancarias_model');
        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'contabancaria')) {
                    set_alert('danger', _l('access_denied'));
                    echo json_encode([
                        'url' => admin_url('financeiro/contabancaria'),
                    ]);
                    die;
                }
                $id = $this->contasbancarias_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('expense')));
                    echo json_encode([
                        'url'       => admin_url('expenses/list_expenses/' . $id),
                        'expenseid' => $id,
                    ]);
                    die;
                }
                echo json_encode([
                    'url' => admin_url('financeiro/contasbancarias'),
                ]);
                die;
            }
            if (staff_cant('edit', 'contasbancarias')) {
                set_alert('danger', _l('access_denied'));
                echo json_encode([
                    'url' => admin_url('financeiro/contasbancarias/' . $id),
                ]);
                die;
            }
            $success = $this->contasbancarias_model->update($this->input->post(), $id);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('expense')));
            }
            echo json_encode([
                'url'       => admin_url('expenses/list_expenses/' . $id),
                'id' => $id,
            ]);
            die;
        }
        if ($id == '') {
            $title = 'Adicionar nova Conta Bancária';
        } else {
            $data['contabancaria'] = $this->contasbancarias_model->get($id);

            if (!$data['contabancaria'] || (staff_cant('view', 'contasbancarias') && $data['contabancaria']->addedfrom != get_staff_user_id())) {
                blank_page('Conta Bancária não encontrada');
            }

            $title = 'Editar Conta Bancária';
        }

        // Carrega o Modelo de Bancos para Seleção
        $this->load->model('bancos_model');

        $data['bancos']     = $this->bancos_model->get();
        $data['bodyclass']  = 'contabacancaria';
        $data['title']      = $title;

        $this->load->view('financeiro/contasbancarias/adicionar_contabancaria', $data);
    }
}