<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Crm extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('crm_model');

    }

    /***************
     * @return void
     * Funcao: Obtem os dados da tabela de Contas Bancárias baseado nos parametros
     * Parametros: nd
     */
    public function table_invoicescontrato($contrato)
    {
        if (
            staff_cant('view', 'contracts')
            && staff_cant('view_own', 'contracts')
            && get_option('allow_staff_view_contasbancarias_assigned') == 0
        ) {
            ajax_access_denied();
        }

        // obtem os dados da Tabela
        $this->app->get_table_data(module_views_path(CRM_MODULE_NAME, 'tables/invoicescontrato'),['contrato_id' => $contrato]);
    }

    public function gerar_faturas()
    {
        $contract_id = $this->input->post('id');
        // Lógica para gerar as faturas
        $this->load->model('crm_model');
        $resultado = $this->crm_model->gerar_faturascontrato($contract_id);
        if ($resultado) {
            echo json_encode(['status' => 'ok']);
        }
        else {
            echo json_encode(['status' => 'error']);
        }
    }


}