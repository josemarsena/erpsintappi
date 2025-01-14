<?php

defined('BASEPATH') or exit('No direct script access allowed');


/**
 * Formata o Status da Conta Bancária
 * @param  integer  $status
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_contabancaria_status($status, $classes = '', $label = true)
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
 * Return estimate status translated by passed status id
 * @param  mixed $id estimate status id
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
 * Return estimate status color class based on twitter bootstrap
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
 * @param  mixed $id invoice id
 * @return mixed invoice updates status / if no update return false
 * @return boolean $prevent_logging do not log changes if the status is updated for the invoice activity log
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
