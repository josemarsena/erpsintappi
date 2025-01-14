<?php

defined('BASEPATH') or exit('No direct script access allowed');

$hasPermissionDelete = staff_can('delete',  'financeiro_bancos');

$aColumns = [
    'id',
    'codigobanco',
    'nomebanco',
    db_prefix() . 'staff.firstname',
    'datacriacao',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'fin_bancos';
$join         = ['LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'fin_bancos.criadopor '];

// Busca Campos Customizaqdos, se houver

$where = [];

// Correção para grandes consultas. Algumas hospedagens possuem max_join_limit
//if (count($custom_fields) > 4) {
//    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');//
//}
/**
* Função geral para todas as tabelas de dados, realiza pesquisas,additional select,join,where,orders
* @param  array $aColumns           Colunas da Tabela
* @param  mixed $sIndexColumn       coluna principal da tabela para melhor desempenho
* @param  string $sTable            Nome da Tabela
* @param  array  $join              junta outras tabelas
* @param  array  $where             Executa where na query
* @param  array  $additionalSelect  seleciona campos adicionais
* @param  string $sGroupBy group results
* @return array
 *
 */
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow)
{
    $row = [];

    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];

        if ($aColumns[$i] == 'criadopor') {
            $_data = $aRow['firstname'];
        }

        if ($aColumns[$i] == 'codigobanco' || $aColumns[$i] == 'nomebanco') {

            //          //$_data = '<a href="#" data-toggle="modal" data-default-selected="' . $aRow['codigobanco'] . '"data-target="#bancos_modal" data-id="' . $aRow['id'] . '">' . $_data . '</a>';
          $_data = '<a href="#" data-toggle="modal" data-target="#bancos_modal" data-id="' . $aRow['id'] . '">' . $_data
                     . '</a>';

       }


        $row[] = $_data;
    }

    $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
    $options .= '<a href="#" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" ' . _attributes_to_string([
            'data-toggle'           => 'modal',
            'data-target'           => '#bancos_modal',
            'data-id'               => $aRow['id'],
        ]) . '><i class="fa-regular fa-pen-to-square fa-lg"></i></a>';

    $options .= '<a href="' . admin_url('financeiro/excluir_banco/' . $aRow['id']) .
        '" class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete"><i class="fa-regular fa-trash-can fa-lg"></i></a>';
    $options .= '</div>';

    $row[] = $options;

 //   $row['DT_RowClass'] = 'has-row-options';
 //   $row = hooks()->apply_filters('admin_bancos_table_row', $row, $aRow);
    $output['aaData'][] = $row;
}
