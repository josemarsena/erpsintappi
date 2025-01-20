<?php

defined('BASEPATH') or exit('No direct script access allowed');

$this->ci->load->model('faturas_model');

$where = [];

$id_projeto = $this->ci->input->post('id_projeto');

$id_fornecedor = $this->ci->input->post('id_fornecedor');

$aColumns = [
    'numero',
    'data',
    'datavencimento',
     get_sql_select_fornecedor_company(),
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
    'LEFT JOIN ' . db_prefix() . 'pur_vendor ON ' . db_prefix() . 'pur_vendor.userid = ' . db_prefix() . 'fin_faturas.id_fornecedor',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'fin_faturas.moeda',
    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'fin_faturas.id_projeto',
];


array_push($where, ' AND ' . db_prefix() . 'fin_faturas.status = 1 OR ' . db_prefix() . 'fin_faturas.status = 3 OR ' . db_prefix()
    . 'fin_faturas.status = 4 OR ' . db_prefix() . 'fin_faturas.status = 6');


if ($id_projeto) {
    array_push($where, 'AND id_projeto=' . $this->ci->db->escape_str($id_projeto));
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
    if (is_numeric($id_fornecedor) || $id_projeto) {
        $numberOutput = '<a href="' . admin_url('financeiro/fatura_a_pagar/' . $aRow['id']) . '" target="_blank">' . formata_numero_faturapagar($aRow['id']) . '</a>';
    } else {
        $numberOutput = '<a href="' . admin_url('financeiro/fatura_a_pagar/' . $aRow['id']) . '" onclick="init_contaspagar(' . $aRow['id'] . '); return false;">' . formata_numero_faturapagar($aRow['id']) . '</a>';
    }

    if ($aRow['recorrente'] > 0) {
        $numberOutput .= '<br /><span class="label label-primary inline-block tw-mt-1"> ' . _l('invoice_recurring_indicator') . '</span>';
    }

    $numberOutput .= '<div class="row-options">';

    $numberOutput .= '<a href="' . site_url('financeiro/' . $aRow['id'] . '/' . $aRow['hash']) . '" target="_blank">' . _l('view') . '</a>';
    if (staff_can('edit',  'invoices')) {
        $numberOutput .= ' | <a href="' . admin_url('financeiro/fatura_a_pagar/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }
    $numberOutput .= '</div>';

    $row[] = $numberOutput;

    $row[] = _d($aRow['data']);

    $row[] = _d($aRow['datavencimento']);

    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['id_fornecedor']) . '">' . $aRow['company'] . '</a>';

    $row[] = $aRow['ano'];


    $row[] = '<a href="' . admin_url('projects/view/' . $aRow['id_projeto']) . '">' . $aRow['project_name'] . '</a>';;

    $row[] = render_tags($aRow['tags']);

   $row[] = app_format_money($aRow['total_impostos'], $aRow['currency_name']);
//
    $row[] = app_format_money($aRow['total'], $aRow['currency_name']);

    $row[] = format_invoice_status($aRow[db_prefix() . 'fin_faturas.status']);


    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('invoices_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;

}