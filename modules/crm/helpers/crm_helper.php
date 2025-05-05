<?php

defined('BASEPATH') or exit('No direct script access allowed');


/**
 * Load invoices total templates
 * This is the template where is showing the panels Outstanding Invoices, Paid Invoices and Past Due invoices
 * @return string
 */
function carrega_faturascontrato_total_template()
{
    $CI = &get_instance();
    $CI->load->model('invoices_model');
    $_data = $CI->input->post();
    if (!$CI->input->post('customer_id')) {
        $multiple_currencies = call_user_func('is_using_multiple_currencies');
    } else {
        $_data['customer_id'] = $CI->input->post('customer_id');
        $multiple_currencies  = call_user_func('is_client_using_multiple_currencies', $CI->input->post('customer_id'));
    }

    if ($CI->input->post('project_id')) {
        $_data['project_id'] = $CI->input->post('project_id');
    }

    if ($multiple_currencies) {
        $CI->load->model('currencies_model');
        $data['invoices_total_currencies'] = $CI->currencies_model->get();
    }

    $data['invoices_years'] = $CI->invoices_model->get_invoices_years();

    if (
        count($data['invoices_years']) >= 1
        && !\app\services\utilities\Arr::inMultidimensional($data['invoices_years'], 'year', date('Y'))
    ) {
        array_unshift($data['invoices_years'], ['year' => date('Y')]);
    }

    $data['total_result'] = $CI->invoices_model->get_invoices_total($_data);
    $data['_currency']    = $data['total_result']['currencyid'];

    $CI->load->view('crm/contratos/faturascontrato_total_template', $data);
}


}
