<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = [
    'ID',
    'Banco',
    'Agência',
    'Conta',
    'Gerente',
    'Telefone',
    'Saldo Inicial',
    'Data Inicio',
    'Saldo Atual',
    'Ativo',
];


$table_data = hooks()->apply_filters('contasbancarias_table_columns', $table_data);

// @param  array   $headings
// @param  string  $class              table class / add prefix eq.table-$class
// @param  array   $additional_classes additional table classes
// @param  array   $table_attributes   table attributes
// @param  boolean $tfoot              includes blank tfoot


render_datatable($table_data, (isset($class) ? $class : 'contasbancarias'));
