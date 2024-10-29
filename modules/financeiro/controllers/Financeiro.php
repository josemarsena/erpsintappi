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
     * Funcao: Obtem os dados da Tabela de Contas a Pagar (Fatura a Pagar)
     * Parametros: nd
     */
    public function table_contaspagar()
    {
        $this->app->get_table_data(module_views_path('financeiro', 'contaspagar/contaspagar'));
    }

    /* List all invoices datatables */
    public function lista_faturas_receber($id = '')
    {
        if (staff_cant('view', 'invoices')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('invoices');
        }

        close_setup_menu();

        $this->load->model('payment_modes_model');
        $data['payment_modes']        = $this->payment_modes_model->get('', [], true);
        $data['invoiceid']            = $id;
        $data['title']                = _l('invoices');
        $data['invoices_years']       = $this->invoices_model->get_invoices_years();
        $data['invoices_sale_agents'] = $this->invoices_model->get_sale_agents();
        $data['invoices_statuses']    = $this->invoices_model->get_statuses();
        $data['invoices_table'] = App_table::find('invoices');
        $data['bodyclass']            = 'invoices-total-manual';
        $this->load->view('financeiro/contasreceber/gerenciar', $data);
    }

    /***************
     * @return void
     * Funcao: Registra nova Fatura a Pagar
     * Parametros: nd
     */
    public function fatura_a_pagar($id = '')
    {

        $this->load->model('faturas_model');

        if ($this->input->post()) {
            $dados_fatura = $this->input->post();

          //  $myfile = fopen("erro.txt", "w") or die("Unable to open file!");
       //     fwrite($myfile, var_dump($dados_fatura));
        //    fclose($myfile);

            if ($id == '') {

                // Verifica permissão para a Equipe
                if (staff_cant('create', 'invoices')) {
                    access_denied('invoices');
                }

                if (hooks()->apply_filters('valida_fatura_a_pagar', true)) {
                    $number = ltrim($dados_fatura['numero'], '0');
                    if (total_rows('fin_faturas', [
                        'YEAR(data)' => (int) date('Y', strtotime(to_sql_date($dados_fatura['data']))),
                        'numero'     => $number,
                        'status !='  => Faturas_model::STATUS_DRAFT,
                    ])) {
                        set_alert('warning', _l('invoice_number_exists'));

                        redirect(admin_url('contaspagar/pagar'));
                    }
                }

                $id = $this->faturas_model->add($dados_fatura);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('invoice')));
                    $redUrl = admin_url('financeiro/contaspagar/list_invoices/' . $id);

                    if (isset($dados_fatura['save_and_record_payment'])) {
                        $this->session->set_userdata('record_payment', true);
                    } elseif (isset($dados_fatura['save_and_send_later'])) {
                        $this->session->set_userdata('send_later', true);
                    }

                    redirect($redUrl);
                }
            } else {
                // Verifica se a Equipe pode Editar
                if (staff_cant('edit', 'invoices')) {
                    access_denied('invoices');
                }

                // If number not set, is draft
                if (hooks()->apply_filters('valida_fatura_a_pagar', true) && isset($dados_fatura['numero'])) {
                    $number = trim(ltrim($dados_fatura['numero'], '0'));
                    if (total_rows('fin_faturas', [
                        'YEAR(data)' => (int) date('Y', strtotime(to_sql_date($dados_fatura['data']))),
                        'numero'     => $number,
                        'status !='  => Faturas_model::STATUS_DRAFT,
                        'id !='      => $id,
                    ])) {
                        set_alert('warning', _l('invoice_number_exists'));

                        redirect(admin_url('financeiro/contaspagar/fatura/' . $id));
                    }
                }
                $success = $this->invoices_model->update($dados_fatura, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('invoice')));
                }

                redirect(admin_url('financeiro/contaspagar/list_invoices/' . $id));
            }
        }
        if ($id == '') {
            $titulo                  = ' Criar Nova Fatura a Pagar';
            $data['billable_tasks'] = [];
        } else {
            $fatura = $this->faturas_model->get($id);

            if (!$fatura || !user_can_view_invoice($id)) {
                blank_page(_l('invoice_not_found'));
            }

            $data['invoices_to_merge'] = $this->faturas_model->check_for_merge_invoice($fatura->id_fornecedor, $fatura->id);
            $data['expenses_to_bill']  = $this->faturas_model->get_expenses_to_bill($fatura->id_fornecedor);

            $data['fatura']        = $fatura;
            $data['edit']           = true;
            $data['billable_tasks'] = $this->tasks_model->get_billable_tasks($fatura->id_fornecedor, !empty($fatura->id_projeto) ? $fatura->id_projeto : '');

            $titulo = _l('edit', _l('invoice_lowercase')) . ' - ' . format_invoice_number($fatura->id);
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);

        $this->load->model('taxes_model');
        $data['impostos'] = $this->taxes_model->get();
        $this->load->model('invoice_items_model');

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $this->load->model('currencies_model');
        $data['moedas'] = $this->currencies_model->get();

        $data['moeda_base'] = $this->currencies_model->get_base_currency();

        $data['equipe']     = $this->staff_model->get('', ['active' => 1]);
        $data['titulo']     = $titulo;
        $data['bodyclass'] = 'invoice';

        $this->load->model('purchase_model');
        $data['fornecedores'] = $this->purchase_model->get_vendor();

        $this->load->view('financeiro/contaspagar/fatura', $data);
    }

    public function valida_fatura_a_pagar()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        if (total_rows('invoices', [
                'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
                'number' => $number,
                'status !=' => Invoices_model::STATUS_DRAFT,
            ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }
    
    /***************
     * @return void
     * Funcao: Mostrar e Gerenciar o Contas a Pagar
     * Parametros: nd
     */
    public function contaspagar($id = '')
    {

        if (!has_permission('financeiro_pagar', '', 'view')) {
            access_denied('financeiro_contaspagar');
        }

        $this->load->model('faturas_model');
        $this->load->model('credit_notes_model');

        if (staff_cant('view', 'invoices')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            access_denied('financeiro_contasreceberpagar');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('contaspagar');
            // obter os dados da tabela conforme arquivo da tabela definido na pasta tables
        }
        close_setup_menu();

        $this->load->model('payment_modes_model');
        $this->load->model('purchase_model');
        $data['payment_modes']        = $this->payment_modes_model->get('', [], true);     // Array de modos de Pagamento
        $data['id_fatura']            = $id;
        $data['titulo']                = 'Contas a Pagar';
        $data['fornecedores'] = $this->purchase_model->get_vendor();
        $data['invoices_years']       = $this->faturas_model->get_invoices_years();    // Array de Anos das Faturas
        $data['invoices_sale_agents'] = $this->faturas_model->get_compradores();    // Array dos Vendedores
        $data['invoices_statuses']    = $this->faturas_model->get_status_naopagos();    // Array dos não Pagos

        $data['bodyclass']            = 'invoices-total-manual';
        $this->load->view('contaspagar/gerenciar', $data);

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
            access_denied('financeiro_contasreceber');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('contasreceber');
            // obter os dados da tabela conforme arquivo da tabela definido na pasta tables
        }
        close_setup_menu();

    //    if ($this->input->is_ajax_request()) {
     //       $this->app->get_table_data(module_views_path('financeiro', 'tables/contasreceber'));
     //   }
//
        $invoices_table = App_table::find('invoices');

        $this->load->model('payment_modes_model');
        $data['payment_modes']        = $this->payment_modes_model->get('', [], true);     // Array de modos de Pagamento
        $data['invoiceid']            = $id;
        $data['title']                = 'Contas a Receber';
        $data['invoices_years']       = $this->invoices_model->get_invoices_years();    // Array de Anos das Faturas
        $data['invoices_sale_agents'] = $this->invoices_model->get_sale_agents();    // Array dos Vendedores
        $data['invoices_statuses']    = $this->invoices_model->get_status_naopagos();    // Array dos não Pagos
        $data['invoices_table']       = $invoices_table;   // Tabela de Fatura
        $data['bodyclass']            = 'invoices-total-manual';
        $this->load->view('contasreceber/gerenciar', $data);

    }


    /**
     * Gets the pur order data ajax.
     *
     * @param      <type>   $id         The identifier
     * @param      boolean  $to_return  To return
     *
     * @return     view.
     */
    public function get_contasreceber_data_ajax($id, $to_return = false)
    {
        if (staff_cant('view', 'invoices')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0') {
            echo _l('access_denied');
            die;
        }


        $myfile = fopen("erro.txt", "w") or die("Unable to open file!");
        fwrite($myfile, 'Passou1');
        fclose($myfile);

        if (!$id) {
            die(_l('invoice_not_found'));
        }

        $invoice = $this->invoices_model->get($id);

        if (!$invoice || !user_can_view_invoice($id)) {
            echo _l('invoice_not_found');
            die;
        }

        $template_name = 'invoice_send_to_customer';

        if ($invoice->sent == 1) {
            $template_name = 'invoice_send_to_customer_already_sent';
        }

        $data = prepare_mail_preview_data($template_name, $invoice->clientid);

        // Check for recorded payments
        $this->load->model('payments_model');
        $data['invoices_to_merge']          = $this->invoices_model->check_for_merge_invoice($invoice->clientid, $id);
        $data['members']                    = $this->staff_model->get('', ['active' => 1]);
        $data['payments']                   = $this->payments_model->get_invoice_payments($id);
        $data['activity']                   = $this->invoices_model->get_invoice_activity($id);
        $data['totalNotes']                 = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'invoice']);
        $data['invoice_recurring_invoices'] = $this->invoices_model->get_invoice_recurring_invoices($id);

        $data['applied_credits'] = $this->credit_notes_model->get_applied_invoice_credits($id);
        // This data is used only when credit can be applied to invoice
        if (credits_can_be_applied_to_invoice($invoice->status)) {
            $data['credits_available'] = $this->credit_notes_model->total_remaining_credits_by_customer($invoice->clientid);

            if ($data['credits_available'] > 0) {
                $data['open_credits'] = $this->credit_notes_model->get_open_credits($invoice->clientid);
            }

            $customer_currency = $this->clients_model->get_customer_default_currency($invoice->clientid);
            $this->load->model('currencies_model');

            if ($customer_currency != 0) {
                $data['customer_currency'] = $this->currencies_model->get($customer_currency);
            } else {
                $data['customer_currency'] = $this->currencies_model->get_base_currency();
            }
        }

        $data['invoice'] = $invoice;

        $data['record_payment'] = false;
        $data['send_later']     = false;

        if ($this->session->has_userdata('record_payment')) {
            $data['record_payment'] = true;
            $this->session->unset_userdata('record_payment');
        } elseif ($this->session->has_userdata('send_later')) {
            $data['send_later'] = true;
            $this->session->unset_userdata('send_later');
        }

        $this->load->view('financeiro/contasreceber/invoice_preview_template', $data);
    }

    // $clientid = ''

    public function table_contasreceber()
    {
        $this->app->get_table_data(module_views_path('financeiro', 'contasreceber/contasreceber'));
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
        $this->load->model('planocontas_model');

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
        $data['tipos_conta'] = $this->planocontas_model->obter_tipos_conta();
        $data['tipos_detalhes'] = $this->planocontas_model->obter_detalhes_tipo_conta();
        $data['contas'] = $this->planocontas_model->obter_contas();
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