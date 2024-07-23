<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns        = [];

$hasPermissionDelete = staff_can('delete',  'financeiro_bancos');

$aColumns = array_merge($aColumns, [
    'id',
    'codigobanco',
    'nomebanco',
    'criadopor',
    'datacriacao',
]);

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'fin_bancos';
$join         = [];

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
$result = data_tables_init($aColumns, $sIndexColumn, $sTable);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow)
{
    $row = [];

    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];

        $attributes = [
            'data-toggle'             => 'modal',
            'data-target'             => '#bancos_modal',
            'data-id'                 => $aRow['id'],
            'data-placement'          => $aRow['placement'],];

        if ($aColumns[$i] == 'criadopor') {
            $_data = get_staff($aRow['criadopor']);
        }

        if ($aColumns[$i] == 'nomebanco') {
            $_data = $aRow['nomebanco'];
            $_data = '<a href="' . admin_url('financeiro/editar_banco/' . $aRow['id']) . '">' . $_data . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('financeiro/editar_banco/' . $aRow['id']) . '">' . _l('view') . '</a>';

            if ($hasPermissionDelete) {
                $_data .= ' | <a href="' . admin_url('financeiro/excluir_banco/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $_data .= '</div>';
        }
 //       if ($aColumns[$i] == 'nomebanco') {
        //    $_data = '<a href="#" onclick="(this,' . $aRow['id'] . '); return false" data-name="' . $aRow['name']
            // . '" data-calendar-id="' . $aRow['calendar_id'] . '" data-email="' . $aRow['email']
            // . '" data-hide-from-client="' . $aRow['hidefromclient'] . '" data-host="' . $aRow['host']
            // . '" data-password="' . $ps . '" data-folder="' . $aRow['folder'] . '" data-imap_username="'
            // . $aRow['imap_username'] . '" data-encryption="' . $aRow['encryption']
            // . '" data-delete-after-import="' . $aRow['delete_after_import'] . '">' . $_data . '</a>';
  //          $_data = '<a href="#"';
   //     }
        $row[] = $_data;
    }

    $row['DT_RowClass'] = 'has-row-options';
    $row = hooks()->apply_filters('admin_bancos_table_row', $row, $aRow);
    $output['aaData'][] = $row;
}
