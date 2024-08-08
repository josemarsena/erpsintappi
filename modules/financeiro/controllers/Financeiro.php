<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Financeiro extends AdminController
{
    /***************
     * @return void
     * Funcao: Construtor da Classe financeiro
     * Parametros: nd
     */
	public function __construct()
    {
        parent::__construct();
        $this->load->model('financeiro_model');
        $this->load->model('bancos_model');
        $this->load->model('contasbancarias_model');
        $this->load->model('planocontas_model');
    }


    /***************
     * @return void
     * Funcao: Mostrar o dashoard Financeiro
     * Parametros: nd
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


    /***************
     * @return void
     * Funcao: Mostrar e gerenciar os Bancos
     * Parametros: nd
     */
    public function bancos()
    {
        $this->load->model('bancos_model');
        $this->load->model('staff_model');
        close_setup_menu();

        // quem pode acessar?
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
            // obter os dados da tabela conforme arquivo da tabela definido na pasta tables
        }

        $data['title'] = 'Bancos Brasileiros';
        $data['staff'] = $this->staff_model->get();

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

                $success = $this->bancos_model->update($data, $data['id']);
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

    /***************
     * @return void
     * Funcao: Mostrar e gerenciar as ContasBancarias
     * Parametros: nd
     */
    public function gerenciar_contasbancarias()
    {
        if (!has_permission('financeiro_contasbancarias', '', 'view')) {
            access_denied('financeiro_contasbancarias');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('contasbancarias');
        }

        $data['title'] = 'Contas Bancárias';

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
                //$myfile = fopen("erro.txt", "w") or die("Unable to open file!");
                //fwrite($myfile, $data);
                //fclose($myfile);
                $success = $this->contasbancarias_model->update($data);
                if ($success) {
                    $message = _l('updated_successfully');
                    set_alert('success', $message);
                }
                redirect(admin_url('financeiro/contabancaria'));
            }
            die;
        }


        $this->load->view('contasbancarias/gerenciar', $data);

    }

    /***************
     * @return void
     * Funcao: Mostrar e Gerenciar o Contas a Pagar
     * Parametros: nd
     */
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
        $data['invoices_table']       = App_table::find('invoices');
        $data['bodyclass']            = 'invoices-total-manual';
        $this->load->view('contaspagar/manage', $data);

    }

    public function table_contasreceber($clientid = '')
    {
        if (staff_cant('view', 'estimates') && staff_cant('view_own', 'estimates') && get_option('allow_staff_view_estimates_assigned') == '0') {
            ajax_access_denied();
        }

        // busca os orçamentos baseado no ID do Cliente
        App_table::find('estimates')->output([
            'clientid' => $clientid,
        ]);
    }

    /***************
     * @return void
     * Funcao: Mostrar e Gerenciar o Contas a Receber
     * Parametros: nd
     */
    public function contasreceber($id = '')
    {

        if (!has_permission('financeiro_receber', '', 'view')) {
            access_denied('financeiro_contasreceber');
        }

        $this->load->model('invoices_model');
        $this->load->model('credit_notes_model');

        if (staff_cant('view', 'invoices')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('contasreceber');
        }

        close_setup_menu();

        $invoices_table = App_table::find('invoices');
        //$this->invoices_model->get_unpaid_invoices();

        
        $this->load->model('payment_modes_model');
        $data['payment_modes']        = $this->payment_modes_model->get('', [], true);
        $data['invoiceid']            = $id;
        $data['title']                = 'Contas a Receber';
        $data['invoices_years']       = $this->invoices_model->get_invoices_years();
        $data['invoices_sale_agents'] = $this->invoices_model->get_sale_agents();
        $data['invoices_statuses']    = $this->invoices_model->get_status_naopagos();
        $data['invoices_table']       = $invoices_table;
        /** ///App_table::find('invoices');    **/
        $data['bodyclass']            = 'invoices-total-manual';
        $this->load->view('financeiro/contasreceber/gerenciar', $data);

    }

    /***************
     * @return void
     * Funcao: Obtem os dados da Tabela baseado nos parametros
     * Parametros: nd
     */
    public function table_bancos()
    {
        if (
            staff_cant('view', 'bancos')
            && staff_cant('view_own', 'bancos')
            && get_option('allow_staff_view_bancos_assigned') == 0
        ) {
            ajax_access_denied();
        }
        // get_table_data = obtem os dados da tabela Função que analisará os dados da tabela da pasta tabelas para a Area de Admin
        // $table = nome da tabela
        // $params
        $this->app->get_table_data(module_views_path(FINANCEIRO_MODULE_NAME, 'tables/bancos'));
    //    $this->app->get_table_data('bancos');


   //     App_table::find('bancos')->output();
    }

    /***************
     * @return void
     * Funcao: Muda o Status da Conta Bancária
     * Parametros: nd
     */
    public function muda_status_contabancaria($id, $status)
    {
        if ($this->input->is_ajax_request()) {
            $this->contasbancarias_model->muda_status_contabancaria($id, $status);
        }
    }

    /***************
     * @return void
     * Funcao: Obtem os dados da tabela baseado nos parametros
     * Parametros: nd
     */
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

    /***************
     * @return void
     * Funcao: Mostra/Edita o Banco
     * Parametros: nd
     */
    public function editar_banco($id)
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

    /***************
     * @return void
     * Funcao: Adicona um novo Banco
     * Parametros: nd
     */
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

    /***************
     * @return void
     * Funcao: Exclui um Banco da Base
     * Parametros: nd
     */
    public function excluir_banco($id)
    {
        if (!$id) {
            redirect(admin_url('financeiro/bancos'));
        }
        $response = $this->bancos_model->delete($id);
        if ($response == true) {
            set_alert('success', 'Banco Excluido com Sucesso');
        } else {
            set_alert('warning', 'Houve um problema para excluir o Banco. Verifique!');
        }
        redirect(admin_url('financeiro/bancos'));
    }

    /***************
     * @return void
     * Funcao: Exclui um Banco da Base
     * Parametros: nd
     */
    public function excluir_contabancaria($id)
    {
        if (!$id) {
            redirect(admin_url('financeiro/contasbancarias'));
        }
        $response = $this->contasbancarias_model->delete($id);
        if ($response == true) {
            set_alert('success', 'Conta Excluida com Sucesso');
        } else {
            set_alert('warning', 'Houve um problema para excluir a Conta. Verifique!');
        }
        redirect(admin_url('financeiro/gerenciar_contasbancarias'));
    }


    /***************
     * @return void
     * Funcao: Adiciona/Edita uma nova Conta Bancaria
     * Parametros: nd
     */
    public function contabancaria($id = '')
    {
        $this->load->model('contasbancarias_model');
        $this->load->model('staff_model');
        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'contabancaria')) {
                    set_alert('danger', _l('access_denied'));
                }
                $id = $this->contasbancarias_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', 'Conta Bancária'));
                }
            }
            if (staff_cant('edit', 'contasbancarias')) {
                set_alert('danger', _l('access_denied'));
            }
            $success = $this->contasbancarias_model->update($this->input->post(), $id);

            if ($success) {
                set_alert('success', _l('updated_successfully', 'Conta Bancaria Atualizada com Sucesso'));
            }
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

        $this->load->view('financeiro/contasbancarias/contabancaria', $data);
    }

    /***************
     * @return void
     * Funcao: Gerencia o Plano de Contas
     * Parametros: nd
     */
    public function planocontas(){
        if (!has_permission('planocontas', '', 'view')) {
            access_denied('planocontas');
        }

        $data['title'] = 'Plano de Contas Gerencial';
        $data['tipos_conta'] = $this->financeiro_model->obter_tipos_conta();
        $data['tipos_detalhes'] = $this->financeiro_model->obter_detalhes_tipos_conta();
        $data['contas'] = $this->financeiro_model->obter_contas();
        $this->load->view('planocontas/gerenciar', $data);
    }

    /**
     *
     *  Adiciona ou Edita uma conta do Plano de Contas Financeiro
     *  @param  integer  $id    Identificador
     *  @return view
     */
    public function conta()
    {
        if (!has_permission('accounting_chart_of_accounts', '', 'edit') && !has_permission('accounting_chart_of_accounts', '', 'create')) {
            access_denied('financeiro');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            $data['description'] = $this->input->post('description', false);
            $message = '';
            if ($data['id'] == '') {
                if (!has_permission('accounting_chart_of_accounts', '', 'create')) {
                    access_denied('financeiro');
                }
                $success = $this->planocontas_model->add_account($data);
                if ($success) {
                    $message = _l('added_successfully', _l('acc_account'));
                }else {
                    $message = _l('add_failure');
                }
            } else {
                if (!has_permission('accounting_chart_of_accounts', '', 'edit')) {
                    access_denied('financeiro');
                }
                $id = $data['id'];
                unset($data['id']);
                $success = $this->planocontas_model->update_account($data, $id);
                if ($success) {
                    $message = _l('updated_successfully', _l('acc_account'));
                }else {
                    $message = _l('updated_fail');
                }
            }

            echo json_encode(['success' => $success, 'message' => $message]);
            die();
        }
    }



}