<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = array(
  _l('invoice_dt_table_heading_number'),
    _l('invoice_dt_table_heading_date'),
  _l('invoice_dt_table_heading_duedate'),
  _l('invoice_dt_table_heading_amount'),
  _l('invoice_total_tax'),
  _l('invoice_dt_table_heading_status'));

$table_data = hooks()->apply_filters('invoices_table_columns', $table_data);

render_datatable($table_data, (isset($class) ? $class : 'invoicescontrato'));

