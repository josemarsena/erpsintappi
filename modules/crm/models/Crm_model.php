<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Crm_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    function gerar_faturascontrato($id)
    {
        $this->load->model('contracts_model');
        $this->load->model('clients_model');
        $this->load->model('proposals_model');
        $this->load->model('invoices_model');

        $contrato = $this->contracts_model->get($id);

        if (!isset($contrato->proposta_id)) {
            return false;
        }

        $startTS = strtotime($contrato->datestart);
        $endTS   = strtotime($contrato->dateend);

        $proposta = $this->proposals_model->get($contrato->proposta_id);

        if (!isset($proposta)) {
            return false;
        }

        $invoices = $this->invoices_model->get('', [
            'contrato_id' => $id,
            'duedate >='  => date('Y-m-d', $startTS),
            'duedate <='  => date('Y-m-d', $endTS),
        ]);

        if (isset($contrato->nro_parcelas) && $contrato->nro_parcelas > 0) {
            $nroparcelas = $contrato->nro_parcelas;
        } else {
            $diffDias = ceil(($endTS - $startTS) / (60 * 60 * 24));
            $nroparcelas = ceil($diffDias / 30);
        }

        if (count($invoices) >= $nroparcelas) {
            return false;
        }

        $datai = $startTS;
        $dataf = (isset($contrato->tem_entrada) && $contrato->tem_entrada == 1) ? $datai : strtotime('+30 days', $datai);

        $i = 1;

        while ($i <= $nroparcelas) {
            $novafatura = true;
            foreach ($invoices as $invoice) {
                $invoiceDueTS = strtotime($invoice->duedate);
                if ($invoiceDueTS >= $datai && $invoiceDueTS < $dataf) {
                    $novafatura = false;
                    break;
                }
            }

            if ($novafatura) {
                $newinvoice = [];
                $newinvoice['date']    = date('Y-m-d');
                $newinvoice['duedate'] = date('Y-m-d', $dataf);
                $newinvoice['subtotal'] = $contrato->contract_value / $nroparcelas;
                $newinvoice['total']    = $contrato->contract_value / $nroparcelas;
                $newinvoice['contrato_id'] = $id;
                $newinvoice['clientid'] = $contrato->client;
                $newinvoice['currency'] = 3;
                $cliente = $this->clients_model->get($contrato->client);
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

            $datai = $dataf;
            $dataf = strtotime('+30 days', $datai);
            $i++;
        }

        return true;
    }

}
