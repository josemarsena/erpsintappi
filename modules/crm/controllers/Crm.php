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

    public function gerar_faturas($id = '')
    {

        $this->load->model('contracts_model');
        $this->load->model('clients_model');
        $this->load->model('proposals_model');
        $contrato = $this->contracts_model->get($id);

        if (!isset($contrato->proposta_id)) {
            // Contrato precisa ter uma Proposta ou Orçamento relacionados
            return false;
        }
        // Converte datas do contrato para timestamps
        $startTS = strtotime($contrato->datestart);
        $endTS   = strtotime($contrato->dateend);

        // Pega a proposta relacionada para puxar os itens
        $proposta = $this->proposals_model->get($contrato->proposta_id);
        // Consulta invoices no intervalo
        $this->load->model('invoices_model');
        $invoices = $this->invoices_model->get('', [
            'contrato_id' => $id,
            'duedate >='  => date('Y-m-d', $startTS),
            'duedate <='  => date('Y-m-d', $endTS),
        ]);

        // Exemplo de cálculo de parcelas
        $diffDias    = floor(($endTS - $startTS) / (60 * 60 * 24));
        $nroparcelas = floor($diffDias / 30);

        // Range de 30 dias
        $datai = $startTS;
        $dataf = strtotime('+30 days', $datai);
        $novafatura = false;
        $i = 1;
        while ($i <= $nroparcelas)
        {

            foreach ($invoices as $invoice) {
                $invoiceDueTS = strtotime($invoice->duedate);

                if ($invoiceDueTS > $datai && $invoiceDueTS <= $dataf) {
                    $novafatura = false;
                        // não faz nada
                } else {
                    $novafatura = true;
                }
            }

            if ($novafatura = true)
            {
                // Cria a fatura
                $newinvoice = [];
                // ...
                $newinvoice['date']    = date('Y-m-d');         // hoje
                $newinvoice['duedate'] = date('Y-m-d', $dataf); // +30 dias
                $newinvoice['subtotal'] = $contrato->contract_value / $nroparcelas;
                $newinvoice['total']    = $contrato->contract_value / $nroparcelas;

                $newinvoice['contrato_id'] = $id;
                $newinvoice['clientid'] = $contrato->client;
                $newinvoice['currency'] = 3;
                $cliente = $this->clients_model->get($contrato->client);

                // $newinvoice['addedfrom'] = $invoice->contrato_id;
                $newinvoice['status'] = 3;
                $newinvoice['number'] = get_option('next_invoice_number');
                $newinvoice['allowed_payment_modes'] = ['a:1:{i:0;s:1:"1";}'];
                $newinvoice['billing_street'] = $cliente->billing_street;
                $newinvoice['billing_city'] = $cliente->billing_city;
                $newinvoice['billing_state'] = $cliente->billing_state;
                $newinvoice['billing_zip'] = $cliente->billing_zip;
                $newinvoice['newitems'] = get_items_by_type('proposal', $contrato->proposta_id);

                $this->invoices_model->add($newinvoice);

            }
            // Passa para o próximo bloco de 30 dias
            $datai = $dataf;
            $dataf = strtotime('+30 days', $datai);
            $i = $i + 1;
        }


        // Pega a quantidade de faturas já atribuidas no Contrato
        set_alert('success', _l('added_successfully', _l('invoice')));

        return true;

    }



}