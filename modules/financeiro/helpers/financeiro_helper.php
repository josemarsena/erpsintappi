<?php

defined('BASEPATH') or exit('No direct script access allowed');


// este filtro chama a função antes de adicionar_fatura
hooks()->add_filter('antes_adicionar_fatura', '_formata_dados_fatura');

/**
 * obtem o fornecedor via sql.
 *
 * @return     string  * obtem o fornecedor via sql.
 * .
 */
function get_sql_select_fornecedor_company()
{
    return 'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM ' . db_prefix() . 'pur_contacts WHERE ' .
        db_prefix() . 'pur_contacts.userid = ' . db_prefix() . 'pur_vendor.userid and is_primary = 1) ELSE company END as company';
}


/**
 * Formata o Status da Conta Bancária
 * @param  integer  $status
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function formata_status_contabancaria($status, $classes = '', $label = true)
{
    $id          = $status;
    $label_class = financeiro_status_color_class($status);
    $status      = financeiro_status_by_id($status);
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status financeiro-status-' . $id . ' financeiro-status-' . $label_class . '">' . $status . '</span>';
    }

    return $status;
}



/**
 * Retornar status do Orcamento traduzido pelo id passado
 * @param  mixed $id id do Orçamento passado
 * @return string
 */
function financeiro_status_by_id($id)
{
    $status = '';
    if ($id == 0) {
        $status = 'Sim';
    } elseif ($id == 1) {
        $status = 'Não';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $status = _l('not_sent_indicator');
            }
        }
    }

    return hooks()->apply_filters('financeiro_status_label', $status, $id);
}

/**
 * Retornar a classe de cor do status do Orçamento com base no bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function financeiro_status_color_class($id, $replace_default_by_muted = false)
{
    $class = '';
    if ($id == 1) {
        $class = 'default';
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'danger';
    } elseif ($id == 4) {
        $class = 'success';
    } elseif ($id == 5) {
        // status 5
        $class = 'warning';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $class = 'default';
                if ($replace_default_by_muted == true) {
                    $class = 'muted';
                }
            }
        }
    }

    return hooks()->apply_filters('financeiro_status_color_class', $class, $id);
}


/**
 * Atualiza Status da Fatura
 * @param  mixed $id id da fatura
 * @return mixed status de atualizações de fatura / se nenhuma atualização retornar falso
 * @return boolean $prevent_logging não registre alterações se o status for atualizado para o log de atividades da fatura
 */
function atualiza_status_fatura($id, $force_update = false, $prevent_logging = false)
{
    $CI = &get_instance();

    $CI->load->model('invoices_model');
    $invoice = $CI->invoices_model->get($id);

    $original_status = $invoice->status;

    if (($original_status == Invoices_model::STATUS_DRAFT && $force_update == false)
        || ($original_status == Invoices_model::STATUS_CANCELLED && $force_update == false)
    ) {
        return false;
    }

    $CI->db->select('amount')
        ->where('invoiceid', $id)
        ->order_by(db_prefix() . 'invoicepaymentrecords.id', 'asc');
    $payments = $CI->db->get(db_prefix() . 'invoicepaymentrecords')->result_array();

    if (!class_exists('credit_notes_model')) {
        $CI->load->model('credit_notes_model');
    }

    $credits = $CI->credit_notes_model->get_applied_invoice_credits($id);
    // Merge credits applied with payments, credits in this function are casted as payments directly to invoice
    // This merge will help to update the status
    $payments = array_merge($payments, $credits);

    $totalPayments = [];
    $status        = Invoices_model::STATUS_UNPAID;

    // Check if the first payments is equal to invoice total
    if (isset($payments[0])) {
        if ($payments[0]['amount'] == $invoice->total) {
            // Paid status
            $status = Invoices_model::STATUS_PAID;
        } else {
            foreach ($payments as $payment) {
                array_push($totalPayments, $payment['amount']);
            }

            $totalPayments = array_sum($totalPayments);

            if ((function_exists('bccomp')
                    ?  bccomp($invoice->total, $totalPayments, get_decimal_places()) === 0
                    || bccomp($invoice->total, $totalPayments, get_decimal_places()) === -1
                    : number_format(($invoice->total - $totalPayments), get_decimal_places(), '.', '') == '0')
                || $totalPayments > $invoice->total
            ) {
                // Paid status
                $status = Invoices_model::STATUS_PAID;
            } elseif ($totalPayments == 0) {
                // Unpaid status
                $status = Invoices_model::STATUS_UNPAID;
            } else {
                if ($invoice->duedate != null) {
                    if ($totalPayments > 0) {
                        // Not paid completely status
                        $status = Invoices_model::STATUS_PARTIALLY;
                    } elseif (date('Y-m-d', strtotime($invoice->duedate)) < date('Y-m-d')) {
                        $status = Invoices_model::STATUS_OVERDUE;
                    }
                } else {
                    // Not paid completely status
                    $status = Invoices_model::STATUS_PARTIALLY;
                }
            }
        }
    } else {
        if ($invoice->total == 0) {
            $status = Invoices_model::STATUS_PAID;
        } else {
            if ($invoice->duedate != null) {
                if (date('Y-m-d', strtotime($invoice->duedate)) < date('Y-m-d')) {
                    // Overdue status
                    $status = Invoices_model::STATUS_OVERDUE;
                }
            }
        }
    }

    $CI->db->where('id', $id);
    $CI->db->update(db_prefix() . 'invoices', [
        'status' => $status,
    ]);

    if ($CI->db->affected_rows() > 0) {
        hooks()->do_action('invoice_status_changed', ['invoice_id' => $id, 'status' => $status]);

        if ($prevent_logging == true) {
            return $status;
        }

        $log = 'Invoice Status Updated [Invoice Number: ' . format_invoice_number($invoice->id) . ', From: ' . format_invoice_status($original_status, '', false) . ' To: ' . format_invoice_status($status, '', false) . ']';

        log_activity($log, null);

        $additional_activity = serialize([
            '<original_status>' . $original_status . '</original_status>',
            '<new_status>' . $status . '</new_status>',
        ]);

        $CI->invoices_model->log_invoice_activity($invoice->id, 'invoice_activity_status_updated', false, $additional_activity);

        return $status;
    }

    return false;
}


/**
 * Remove e formata alguns dados comumente usados ​​para o recurso de vendas, como faturas, orcaemntos, etc.
 * @param  array $data $_POST data
 * @return array
 */
function _formata_dados_fatura($data)
{
    foreach (_obter_nomes_naousados_fatura() as $u) {
        if (isset($data['data'][$u])) {
            unset($data['data'][$u]);
        }
    }

    if (isset($data['data']['data'])) {
        $data['data']['data'] = to_sql_date($data['data']['data']);
    }

    if (isset($data['data']['open_till'])) {
        $data['data']['open_till'] = to_sql_date($data['data']['open_till']);
    }

    if (isset($data['data']['datavencimento'])) {
        $data['data']['datavencimento'] = to_sql_date($data['data']['datavencimento']);
    }

    if (isset($data['data']['termos'])) {
        $data['data']['termos'] = nl2br_save_html($data['data']['termos']);
    }

    if (isset($data['data']['nota_admin'])) {
        $data['data']['nota_admin'] = nl2br($data['data']['nota_admin']);
    }

    if ((isset($data['data']['ajuste']) && !is_numeric($data['data']['ajuste'])) || !isset($data['data']['ajuste'])) {
        $data['data']['ajuste'] = 0;
    } elseif (isset($data['data']['ajuste']) && is_numeric($data['data']['ajuste'])) {
        $data['data']['ajuste'] = number_format($data['data']['ajuste'], get_decimal_places(), '.', '');
    }

    if (isset($data['data']['total_descto']) && $data['data']['total_descto'] == 0) {
        $data['data']['tipo_descto'] = '';
    }

    foreach (['country', 'billing_country', 'shipping_country', 'id_projeto', 'sale_agent'] as $should_be_zero) {
        if (isset($data['data'][$should_be_zero]) && $data['data'][$should_be_zero] == '') {
            $data['data'][$should_be_zero] = 0;
        }
    }

    return $data;
}


/**
 * Nomes de solicitação $_POST não usados, geralmente são usados como entradas auxiliares no formulário
 * A função top irá verificar todos eles e desfazer a configuração do $data
 * @return array
 */
function _obter_nomes_naousados_fatura()
{
    return [
        'taxname', 'description',
        'currency_symbol', 'price',
        'isedit', 'taxid',
        'long_description', 'unit',
        'rate', 'quantity',
        'item_select', 'tax',
        'billed_tasks', 'billed_expenses',
        'task_select', 'task_id',
        'expense_id', 'repeat_every_custom',
        'repeat_type_custom', 'bill_expenses',
        'save_and_send', 'merge_current_invoice',
        'cancel_merged_invoices', 'invoices_to_merge',
        'tags', 's_prefix', 'save_and_record_payment',
    ];
}



/**
 * Função que atualiza o imposto total na tabela de vendas eq. fatura, proposta, orçamentos, nota de crédito
 * @param  mixed $id
 * @return void
 */
function atualiza_imposto_fatura($id, $type, $table)
{
    $CI = &get_instance();
    $CI->db->select('porcento_descto, tipo_descto, total_descto, subtotal');
    $CI->db->from($table);
    $CI->db->where('id', $id);

    $data = $CI->db->get()->row();

    $items = get_items_by_type($type, $id);

    $total_tax         = 0;
    $taxes             = [];
    $_calculated_taxes = [];

    $func_taxes = 'get_' . $type . '_item_taxes';

    foreach ($items as $item) {
        $item_taxes = call_user_func($func_taxes, $item['id']);
        if (count($item_taxes) > 0) {
            foreach ($item_taxes as $tax) {
                $calc_tax     = 0;
                $tax_not_calc = false;
                if (!in_array($tax['taxname'], $_calculated_taxes)) {
                    array_push($_calculated_taxes, $tax['taxname']);
                    $tax_not_calc = true;
                }

                if ($tax_not_calc == true) {
                    $taxes[$tax['taxname']]          = [];
                    $taxes[$tax['taxname']]['total'] = [];
                    array_push($taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                    $taxes[$tax['taxname']]['tax_name'] = $tax['taxname'];
                    $taxes[$tax['taxname']]['taxrate']  = $tax['taxrate'];
                } else {
                    array_push($taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                }
            }
        }
    }

    foreach ($taxes as $tax) {
        $total = array_sum($tax['total']);
        if ($data->discount_percent != 0 && $data->discount_type == 'before_tax') {
            $total_tax_calculated = ($total * $data->discount_percent) / 100;
            $total                = ($total - $total_tax_calculated);
        } elseif ($data->discount_total != 0 && $data->discount_type == 'before_tax') {
            $t     = ($data->discount_total / $data->subtotal) * 100;
            $total = ($total - $total * $t / 100);
        }
        $total_tax += $total;
    }

    $CI->db->where('id', $id);
    $CI->db->update($table, [
        'total_impostos' => $total_tax,
    ]);
}

/**
 * Format invoice number based on description
 * @param  mixed $id
 * @return string
 */
function formata_numero_faturapagar($id)
{
    $CI = &get_instance();

    if (!is_object($id)) {
        $CI->db->select('data,numero,prefixo,formatonumero,status')
            ->from(db_prefix() . 'fin_faturas')
            ->where('id', $id);

        $invoice = $CI->db->get()->row();
    } else {
        $invoice = $id;

        $id = $invoice->id;
    }

    if (!$invoice) {
        return '';
    }

    if (!class_exists('Invoices_model', false)) {
        get_instance()->load->model('faturas_model');
    }

    if ($invoice->status == Faturas_model::STATUS_DRAFT) {
        $number = $invoice->prefix . 'DRAFT';
    } else {
        $number = sales_number_format($invoice->numero, $invoice->formatonumero, $invoice->prefixo, $invoice->data);
    }

    return hooks()->apply_filters('format_invoice_number', $number, [
        'id'      => $id,
        'invoice' => $invoice,
    ]);
}
