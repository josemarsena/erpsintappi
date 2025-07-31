<?php

defined('BASEPATH') or exit('No direct script access allowed');

$hasPermissionDelete = staff_can('delete',  'financeiro_contasbancarias');
$aColumns        = [];

$aColumns = array_merge($aColumns, [
    'id',
    'banco_id',
    'agencia',
    'conta',
    'gerente',
    'telefone',
    'saldoinicial',
    'datasaldoinicial',
    'saldoatual',
    'ativo',
]);

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'fin_contabancaria';
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



foreach ($rResult as $aRow) {
    $row = [];

    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];

        // Conta Banc[aria esta ativa?
        if ($aColumns[$i] == 'ativo') {

        }

        // Edita o Banco-> Abre nova Janela
        if ($aColumns[$i] == 'banco_id') {

        }
        // Formata o Saldo Atual
        if ($aColumns[$i] == 'saldoatual') {
            $_data = app_format_money($aRow['saldoatual'],'R$ ');
        }
        // Formata o Saldo Inicial
        if ($aColumns[$i] == 'saldoinicial') {
            $_data = app_format_money($aRow['saldoinicial'],'R$ ');
        }
        if ($aColumns[$i] == 'conta') {
            $_data = $aRow['conta'];
            $_data = '<a href="' . admin_url('financeiro/contabancaria/' . $aRow['id']) . '">' . $_data . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('financeiro/contabancaria/' . $aRow['id']) . '">' . _l('view') . '</a>';

            if ($hasPermissionDelete) {
                $_data .= ' | <a href="' . admin_url('financeiro/excluir_contabancaria/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $_data .= '</div>';


        }

        if ($aColumns[$i] == 'ativo') {
                $checked = '';
                if ($aRow['ativo'] == 1) {
                    $checked = 'checked';
                }
                $_data = '<div class="onoffswitch">
                <input type="checkbox" data-switch-url="' . admin_url() . 'financeiro/muda_status_contabancaria" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . $checked . '>
                <label class="onoffswitch-label" for="c_' . $aRow['id'] . '"></label>
            </div>';

        }


        $row[] = $_data;
    }

    $output['aaData'][] = $row;
}
