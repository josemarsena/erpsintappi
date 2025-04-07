<?php

defined('BASEPATH') or exit('No direct script access allowed');

$this->ci->load->model('invoices_model');
$this->ci->load->model('contracts_model');
$where = [];

//$contrato = $this->contracts_model->get($contrato_id);

$project_id = $this->ci->input->post('project_id');

$clientid = $this->ci->input->post('client_id');

$aColumns = [
    'number',
    'date',
    'duedate',
    'total',
    'total_tax',
    db_prefix() . 'invoices.status',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'invoices';

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'invoices.clientid',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency',
    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'invoices.project_id',
];

$custom_fields = get_table_custom_fields('invoice');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);

    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'invoices.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}


if ($project_id) {
    array_push($where, 'AND project_id=' . $this->ci->db->escape_str($project_id));
}

if (staff_cant('view', 'invoices')) {
    $userWhere = 'AND ' . get_invoices_where_sql_for_staff(get_staff_user_id());
    array_push($where, $userWhere);
}

// filtra o contrato
array_push($where, ' AND ' . db_prefix() . 'invoices.contrato_id = ' . $contrato_id);

// filtra a data de inicio e término do contrato
// array_push($where, ' AND ' . db_prefix() . 'invoices.duedate >= ' . $contrato->datestart . ' AND ' . db_prefix() . 'invoices.duedate <= ' . $contrato->dateend);

$aColumns = hooks()->apply_filters('invoices_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'invoices.id',
    db_prefix() . 'invoices.clientid',
    db_prefix() . 'currencies.name as currency_name',
    'project_id',
    'hash',
    'recurring',
    'deleted_customer_name',
]);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $numberOutput = '';

    // If is from client area table
    if (is_numeric($clientid) || $project_id) {
        $numberOutput = '<a href="' . admin_url('financeiro/contasreceber/' . $aRow['id']) . '" target="_blank">' . format_invoice_number($aRow['id']) . '</a>';
    } else {
        $numberOutput = '<a href="' . admin_url('financeiro/contasreceber/' . $aRow['id']) . '" onclick="init_contasreceber(' . $aRow['id'] . '); return false;">' . format_invoice_number($aRow['id']) . '</a>';
    }

    if ($aRow['recurring'] > 0) {
        $numberOutput .= '<br /><span class="label label-primary inline-block tw-mt-1"> ' . _l('invoice_recurring_indicator') . '</span>';
    }

    $numberOutput .= '<div class="row-options">';

    $numberOutput .= '<a href="' . site_url('invoice/' . $aRow['id'] . '/' . $aRow['hash']) . '" target="_blank">' . _l('view') . '</a>';
    if (staff_can('edit',  'invoices')) {
        $numberOutput .= ' | <a href="' . admin_url('invoices/invoice/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }
    $numberOutput .= '</div>';

    $row[] = $numberOutput;

    $row[] = _d($aRow['date']);

    $row[] = _d($aRow['duedate']);


   /**** $row[] = $aRow['year'];    ****/


   /**** $row[] = '<a href="' . admin_url('projects/view/' . $aRow['project_id']) . '">' . $aRow['project_name'] . '</a>';;   *****/

  /*****  $row[] = render_tags($aRow['tags']);    ****/



    $row[] = app_format_money($aRow['total'], $aRow['currency_name']);

    $row[] = app_format_money($aRow['total_tax'], $aRow['currency_name']);

    $row[] = format_invoice_status($aRow[db_prefix() . 'invoices.status']);


    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('invoices_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;

}