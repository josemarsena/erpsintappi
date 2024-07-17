<?php

defined('BASEPATH') or exit('No direct script access allowed');

$myfile = fopen("erro.txt", "w") or die("Unable to open file!");

$aColumns = ['number', 'duedate', 'total', 'status'];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'invoices';
$join         = [];

fwrite($myfile, var_dump($aColumns));

$where = [];

if (staff_cant('view', 'customers')) {
    array_push($where, 'AND ' . db_prefix() . 'invoices.client_id = '. $client_id .
        'AND ' . db_prefix() . 'invoices.proposta_id = ' . $proposta_id .
        'AND ' . db_prefix() . 'invoices.duedate >= ' . $data_inicio .
        'AND ' . db_prefix() . 'invoices.duedate <= ' . $data_fim . ')');
}

if ($this->ci->input->post('custom_view')) {
    $filter = $this->ci->input->post('custom_view');
    if (startsWith($filter, 'consent_')) {
        array_push($where, 'AND ' . db_prefix() . 'invoices.client_id = '. $client_id .
            'AND ' . db_prefix() . 'invoices.proposta_id = ' . $proposta_id .
            'AND ' . db_prefix() . 'invoices.duedate >= ' . $data_inicio .
            'AND ' . db_prefix() . 'invoices.duedate <= ' . $data_fim . ')');
    }
}
fwrite($myfile, var_dump($where));

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, []);

fwrite($myfile, var_dump($result));
fclose($myfile);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $rowName = '<div class="row-options tw-ml-9">';

    $rowName .= '<a href="#" onclick="invoice(' . $aRow['number'] . ',' . $aRow['id'] . ');return false;">' . _l('edit') . '</a>';

    $rowName .= '</div>';

    $row[] = $rowName;

    // For exporting
    $outputActive = '<span class="hide">' . ($aRow['active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

    $row[] = $outputActive;

    $row['DT_RowClass'] = 'has-row-options';

    if ($aRow['registration_confirmed'] == 0) {
        $row['DT_RowClass'] .= ' info requires-confirmation';
        $row['Data_Title'] = _l('customer_requires_registration_confirmation');
        $row['Data_Toggle'] = 'tooltip';
    }

    $row = hooks()->apply_filters('all_contacts_table_row', $row, $aRow);

    $output['aaData'][] = $row;
}