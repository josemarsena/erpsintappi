<?php

defined('BASEPATH') or exit('No direct script access allowed');

$this->ci->load->model('faturas_model');

$where = [];

$project_id = $this->ci->input->post('id_projeto');

$clientid = $this->ci->input->post('id_fornecedor');

$aColumns = [
    'numero',
    'data',
    'datavencimento',
    'id_fornecedor',
    'YEAR(data) as ano',
    db_prefix() . 'projects.name as project_name',
    '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'fin_faturas.id and rel_type="invoice" ORDER by tag_order ASC) as tags',
    'total',
    'total_impostos',
    db_prefix() . 'fin_faturas.status',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'fin_faturas';

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'fin_faturas.id_fornecedor',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'fin_faturas.moeda',
    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'fin_faturas.id_projeto',
];


array_push($where, ' AND ' . db_prefix() . 'fin_faturas.status = 1 OR ' . db_prefix() . 'fin_faturas.status = 3 OR ' . db_prefix()
    . 'fin_faturas.status = 4 OR ' . db_prefix() . 'fin_faturas.status = 6');


if ($project_id) {
    array_push($where, 'AND project_id=' . $this->ci->db->escape_str($project_id));
}

if (staff_cant('view', 'invoices')) {
    $userWhere = 'AND ' . get_invoices_where_sql_for_staff(get_staff_user_id());
    array_push($where, $userWhere);
}

$aColumns = hooks()->apply_filters('invoices_table_sql_columns', $aColumns);


$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'fin_faturas.id',
    db_prefix() . 'fin_faturas.id_fornecedor',
    db_prefix() . 'currencies.name as currency_name',
    'id_projeto',
    'hash',
    'recorrente',
]);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $numberOutput = '';

    // If is from client area table
    if (is_numeric($clientid) || $project_id) {
        $numberOutput = '<a href="' . admin_url('financeiro/contasreceber/list_invoices/' . $aRow['id']) . '" target="_blank">' . format_invoice_number($aRow['id']) . '</a>';
    } else {
        $numberOutput = '<a href="' . admin_url('financeiro/contasreceber/list_invoices/' . $aRow['id']) . '" onclick="init_contasreceber(' . $aRow['id'] . '); return false;">' . format_invoice_number($aRow['id']) . '</a>';
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

    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['id_fornecedor']) . '">' . $aRow['company'] . '</a>';


    $row[] = $aRow['year'];


    $row[] = '<a href="' . admin_url('projects/view/' . $aRow['id_projeto']) . '">' . $aRow['project_name'] . '</a>';;

    $row[] = render_tags($aRow['tags']);

    $row[] = app_format_money($aRow['total_imposto'], $aRow['currency_name']);

    $row[] = app_format_money($aRow['total'], $aRow['currency_name']);

    $row[] = format_invoice_status($aRow[db_prefix() . 'fin_faturas.status']);


    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('invoices_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;

}