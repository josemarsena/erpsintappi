<?php

defined('BASEPATH') or exit('No direct script access allowed');

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
$join         = [
    'LEFT JOIN ' . db_prefix() . 'fin_bancos bancos ON ' . db_prefix() . 'fin_bancos.id=' . db_prefix() . 'fin_contabancaria.banco_id',
];

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
        $ps    = '';

        // Conta Banc[aria esta ativa?
        if ($aColumns[$i] == 'ativo') {
  //          $myfile = fopen("erro.txt", "w") or die("Unable to open file!");
  //          $toggleActive = '<div class="onoffswitch" data-toggle="tooltip" data-title="' . _l('customer_active_inactive_help') . '">
 //            <input type="checkbox"' . ' data-switch-url="' . admin_url() . 'financeiro/muda_status_contabancaria" name="onoffswitch" class="onoffswitch-checkbox" id="' .
 //               $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . ($aRow[db_prefix() . 'fin_contabancaria.ativo'] == 1 ? 'checked' : '') . '>
 //           <label class="onoffswitch-label" for="' . $aRow['id'] . '"></label></div>';

 //           $_data = $toggleActive;
 //           fwrite($myfile, $_data);
 //           fclose($myfile);
            //format_contabancaria_status($aColumns[$i], $classes = '', $label = true);
        }

        // Edita o Banco-> Abre nova Janela
        if ($aColumns[$i] == 'banco_id') {
         //   $myfile = fopen("erro.txt", "w") or die("Unable to open file!");
         //   fwrite($myfile, $aRow);
         //   fclose($myfile);
                //$_data = $aRow['banco.nomebanco'];
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
            // $_data = '<a href="#"';
            // onclick="editar_conta(this,' . $aRow['id'] . '); return false" data-name="' . $aRow['name'] . '" data-calendar-id="' . $aRow['calendar_id'] . '" data-email="' . $aRow['email'] . '" data-hide-from-client="' . $aRow['hidefromclient'] . '" data-host="' . $aRow['host'] . '" data-password="' . $ps . '" data-folder="' . $aRow['folder'] . '" data-imap_username="' . $aRow['imap_username'] . '" data-encryption="' . $aRow['encryption'] . '" data-delete-after-import="' . $aRow['delete_after_import'] . '">' . $_data . '</a>';
        }
        $row[] = $_data;
    }

 //   $rowName .= '</div>';


//    $row[] = $rowName;


 //   $row['DT_RowClass'] = 'has-row-options';
//    $row = hooks()->apply_filters('admin_bancos_table_row', $row, $aRow);
    $output['aaData'][] = $row;
}
