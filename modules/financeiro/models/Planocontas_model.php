<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Planocontas_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Atualizar Configuracoes Gerais
     * @param array $data The data
     * @return     boolean
     */
    public function atualiza_configuracoes_gerais($data)
    {
        $affectedRows = 0;
        if (!isset($data['acc_close_the_books'])) {
            $data['acc_close_the_books'] = 0;
        }
        if (!isset($data['acc_enable_account_numbers'])) {
            $data['acc_enable_account_numbers'] = 0;
        }
        if (!isset($data['acc_show_account_numbers'])) {
            $data['acc_show_account_numbers'] = 0;
        }

        if ($data['acc_closing_date'] != '') {
            $data['acc_closing_date'] = to_sql_date($data['acc_closing_date']);
        }

        foreach ($data as $key => $value) {
            $this->db->where('name', $key);
            $this->db->update(db_prefix() . 'options', [
                'value' => $value,
            ]);
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }
        }

        if ($affectedRows > 0) {
            return true;
        }
        return false;
    }

    /**
     * update automatic conversion
     *
     * @param array $data The data
     *
     * @return     boolean
     */
    public function update_automatic_conversion($data)
    {
        $affectedRows = 0;

        if (!isset($data['acc_invoice_automatic_conversion'])) {
            $data['acc_invoice_automatic_conversion'] = 0;
        }

        if (!isset($data['acc_payment_automatic_conversion'])) {
            $data['acc_payment_automatic_conversion'] = 0;
        }

        if (!isset($data['acc_expense_automatic_conversion'])) {
            $data['acc_expense_automatic_conversion'] = 0;
        }

        if (!isset($data['acc_tax_automatic_conversion'])) {
            $data['acc_tax_automatic_conversion'] = 0;
        }

        foreach ($data as $key => $value) {
            $this->db->where('name', $key);
            $this->db->update(db_prefix() . 'options', [
                'value' => $value,
            ]);
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }
        }

        if ($affectedRows > 0) {
            return true;
        }
        return false;
    }

    /**
     * Adiciona uma nova Conta
     * @param array $data
     * @return integer
     */
    public function adicionar_conta($data)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }

        if ($data['balance_as_of'] != '') {
            $data['balance_as_of'] = to_sql_date($data['balance_as_of']);
        }

        if (isset($data['update_balance'])) {
            unset($data['update_balance']);
        }

        $data['balance'] = str_replace(',', '', $data['balance']);
        $this->db->insert(db_prefix() . 'acc_accounts', $data);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            if ($data['balance'] > 0) {
                $node = [];
                $node['account'] = $insert_id;
                $node['ending_balance'] = $data['balance'];
                $node['beginning_balance'] = 0;
                $node['finish'] = 1;
                if ($data['balance_as_of'] != '') {
                    $node['ending_date'] = $data['balance_as_of'];
                } else {
                    $node['ending_date'] = date('Y-m-d');
                }

                $this->db->insert(db_prefix() . 'acc_reconciles', $node);
                $reconcile_id = $this->db->insert_id();

                $this->db->where('account_type_id', 10);
                $this->db->where('account_detail_type_id', 71);
                $account = $this->db->get(db_prefix() . 'acc_accounts')->row();

                if ($account) {
                    $node = [];

                    if ($data['account_type_id'] == 7 || $data['account_type_id'] == 15 || $data['account_type_id'] == 8 || $data['account_type_id'] == 9) {
                        $node['debit'] = $data['balance'];
                        $node['credit'] = 0;
                    } else {
                        $node['debit'] = 0;
                        $node['credit'] = $data['balance'];
                    }

                    $node['split'] = $insert_id;
                    $node['account'] = $account->id;
                    $node['rel_id'] = 0;
                    $node['rel_type'] = 'deposit';
                    if ($data['balance_as_of'] != '') {
                        $node['date'] = $data['balance_as_of'];
                    } else {
                        $node['date'] = date('Y-m-d');
                    }
                    $node['datecreated'] = date('Y-m-d H:i:s');
                    $node['addedfrom'] = get_staff_user_id();

                    $this->db->insert(db_prefix() . 'acc_account_history', $node);

                    $node = [];
                    if ($data['account_type_id'] == 7 || $data['account_type_id'] == 15 || $data['account_type_id'] == 8 || $data['account_type_id'] == 9) {
                        $node['debit'] = 0;
                        $node['credit'] = $data['balance'];
                    } else {
                        $node['debit'] = $data['balance'];
                        $node['credit'] = 0;
                    }

                    $node['reconcile'] = $reconcile_id;
                    $node['split'] = $account->id;
                    $node['account'] = $insert_id;
                    $node['rel_id'] = 0;
                    $node['rel_type'] = 'deposit';
                    if ($data['balance_as_of'] != '') {
                        $node['date'] = $data['balance_as_of'];
                    } else {
                        $node['date'] = date('Y-m-d');
                    }
                    $node['datecreated'] = date('Y-m-d H:i:s');
                    $node['addedfrom'] = get_staff_user_id();

                    $this->db->insert(db_prefix() . 'acc_account_history', $node);
                } else {
                    $this->db->insert(db_prefix() . 'acc_accounts', [
                        'name' => '',
                        'key_name' => 'acc_opening_balance_equity',
                        'account_type_id' => 10,
                        'account_detail_type_id' => 71,
                    ]);

                    $account_id = $this->db->insert_id();

                    if ($account_id) {
                        $node = [];
                        if ($data['account_type_id'] == 7 || $data['account_type_id'] == 15 || $data['account_type_id'] == 8 || $data['account_type_id'] == 9) {
                            $node['debit'] = $data['balance'];
                            $node['credit'] = 0;
                        } else {
                            $node['debit'] = 0;
                            $node['credit'] = $data['balance'];
                        }

                        $node['split'] = $insert_id;
                        $node['account'] = $account_id;
                        if ($data['balance_as_of'] != '') {
                            $node['date'] = $data['balance_as_of'];
                        } else {
                            $node['date'] = date('Y-m-d');
                        }
                        $node['rel_id'] = 0;
                        $node['rel_type'] = 'deposit';
                        $node['datecreated'] = date('Y-m-d H:i:s');
                        $node['addedfrom'] = get_staff_user_id();

                        $this->db->insert(db_prefix() . 'acc_account_history', $node);

                        $node = [];
                        if ($data['account_type_id'] == 7 || $data['account_type_id'] == 15 || $data['account_type_id'] == 8 || $data['account_type_id'] == 9) {
                            $node['debit'] = 0;
                            $node['credit'] = $data['balance'];
                        } else {
                            $node['debit'] = $data['balance'];
                            $node['credit'] = 0;
                        }

                        $node['reconcile'] = $reconcile_id;
                        $node['split'] = $account_id;
                        $node['account'] = $insert_id;
                        if ($data['balance_as_of'] != '') {
                            $node['date'] = $data['balance_as_of'];
                        } else {
                            $node['date'] = date('Y-m-d');
                        }
                        $node['rel_id'] = 0;
                        $node['rel_type'] = 'deposit';
                        $node['datecreated'] = date('Y-m-d H:i:s');
                        $node['addedfrom'] = get_staff_user_id();

                        $this->db->insert(db_prefix() . 'acc_account_history', $node);
                    }
                }
            }


            return $insert_id;
        }

        return false;
    }

    /**
     * Atualiza a Conta do Plano de Contas
     * @param array $data
     * @param integer $id
     * @return integer
     */
    public function atualizar_conta($data, $id)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }

        if ($data['balance_as_of'] != '') {
            $data['balance_as_of'] = to_sql_date($data['balance_as_of']);
        }
        $update_balance = 0;
        if (isset($data['update_balance'])) {
            $update_balance = $data['update_balance'];
            unset($data['update_balance']);
        }

        $data['balance'] = str_replace(',', '', $data['balance']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'acc_accounts', $data);

        if ($this->db->affected_rows() > 0) {
            if ($data['balance'] > 0 && $update_balance == 1) {
                $node = [];
                $node['account'] = $id;
                $node['ending_balance'] = $data['balance'];
                $node['beginning_balance'] = 0;
                $node['finish'] = 1;
                if ($data['balance_as_of'] != '') {
                    $node['ending_date'] = $data['balance_as_of'];
                } else {
                    $node['ending_date'] = date('Y-m-d');
                }

                $this->db->insert(db_prefix() . 'acc_reconciles', $node);
                $reconcile_id = $this->db->insert_id();

                $this->db->where('account_type_id', 10);
                $this->db->where('account_detail_type_id', 71);
                $account = $this->db->get(db_prefix() . 'acc_accounts')->row();

                if ($account) {
                    $node = [];

                    if ($data['account_type_id'] == 7 || $data['account_type_id'] == 15 || $data['account_type_id'] == 8 || $data['account_type_id'] == 9) {
                        $node['debit'] = $data['balance'];
                        $node['credit'] = 0;

                    } else {
                        $node['debit'] = 0;
                        $node['credit'] = $data['balance'];
                    }

                    $node['split'] = $id;
                    $node['account'] = $account->id;

                    if ($data['balance_as_of'] != '') {
                        $node['date'] = $data['balance_as_of'];
                    } else {
                        $node['date'] = date('Y-m-d');
                    }

                    $node['rel_id'] = 0;
                    $node['rel_type'] = 'deposit';
                    $node['datecreated'] = date('Y-m-d H:i:s');
                    $node['addedfrom'] = get_staff_user_id();
                    $this->db->insert(db_prefix() . 'acc_account_history', $node);

                    $node = [];
                    if ($data['account_type_id'] == 7 || $data['account_type_id'] == 15 || $data['account_type_id'] == 8 || $data['account_type_id'] == 9) {
                        $node['debit'] = 0;
                        $node['credit'] = $data['balance'];
                    } else {
                        $node['debit'] = $data['balance'];
                        $node['credit'] = 0;
                    }

                    $node['reconcile'] = $reconcile_id;
                    $node['split'] = $account->id;
                    $node['account'] = $id;
                    $node['rel_id'] = 0;

                    if ($data['balance_as_of'] != '') {
                        $node['date'] = $data['balance_as_of'];
                    } else {
                        $node['date'] = date('Y-m-d');
                    }
                    $node['rel_type'] = 'deposit';
                    $node['datecreated'] = date('Y-m-d H:i:s');
                    $node['addedfrom'] = get_staff_user_id();

                    $this->db->insert(db_prefix() . 'acc_account_history', $node);
                } else {
                    $this->db->insert(db_prefix() . 'acc_accounts', [
                        'name' => '',
                        'key_name' => 'acc_opening_balance_equity',
                        'account_type_id' => 10,
                        'account_detail_type_id' => 71,
                    ]);

                    $account_id = $this->db->insert_id();

                    if ($account_id) {
                        $node = [];
                        if ($data['account_type_id'] == 7 || $data['account_type_id'] == 15 || $data['account_type_id'] == 8 || $data['account_type_id'] == 9) {
                            $node['debit'] = $data['balance'];
                            $node['credit'] = 0;
                        } else {
                            $node['debit'] = 0;
                            $node['credit'] = $data['balance'];
                        }

                        $node['split'] = $id;
                        $node['account'] = $account_id;
                        $node['rel_id'] = 0;
                        if ($data['balance_as_of'] != '') {
                            $node['date'] = $data['balance_as_of'];
                        } else {
                            $node['date'] = date('Y-m-d');
                        }
                        $node['rel_type'] = 'deposit';
                        $node['datecreated'] = date('Y-m-d H:i:s');
                        $node['addedfrom'] = get_staff_user_id();

                        $this->db->insert(db_prefix() . 'acc_account_history', $node);

                        $node = [];
                        if ($data['account_type_id'] == 7 || $data['account_type_id'] == 15 || $data['account_type_id'] == 8 || $data['account_type_id'] == 9) {
                            $node['debit'] = 0;
                            $node['credit'] = $data['balance'];
                        } else {
                            $node['debit'] = $data['balance'];
                            $node['credit'] = 0;
                        }

                        $node['reconcile'] = $reconcile_id;
                        $node['split'] = $account_id;
                        $node['account'] = $id;
                        $node['rel_id'] = 0;
                        if ($data['balance_as_of'] != '') {
                            $node['date'] = $data['balance_as_of'];
                        } else {
                            $node['date'] = date('Y-m-d');
                        }
                        $node['rel_type'] = 'deposit';
                        $node['datecreated'] = date('Y-m-d H:i:s');
                        $node['addedfrom'] = get_staff_user_id();

                        $this->db->insert(db_prefix() . 'acc_account_history', $node);
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get the data account to choose from.
     *
     * @return     array  The product group select.
     */
    public function get_data_account_to_select()
    {

        $accounts = $this->get_accounts();
        $acc_enable_account_numbers = get_option('acc_enable_account_numbers');
        $acc_show_account_numbers = get_option('acc_show_account_numbers');
        $list_accounts = [];

        $account_types = $this->accounting_model->get_account_types();
        $account_type_name = [];

        foreach ($account_types as $key => $value) {
            $account_type_name[$value['id']] = $value['name'];
        }

        foreach ($accounts as $key => $account) {
            $note = [];
            $note['id'] = $account['id'];

            $_account_type_name = isset($account_type_name[$account['account_type_id']]) ? $account_type_name[$account['account_type_id']] : '';

            if ($acc_enable_account_numbers == 1 && $acc_show_account_numbers == 1 && $account['number'] != '') {
                $note['label'] = $account['number'] . ' - ' . $account['name'] . ' - ' . $_account_type_name;
            } else {
                $note['label'] = $account['name'] . ' - ' . $_account_type_name;
            }
            $list_accounts[] = $note;
        }
        return $list_accounts;
    }

    /**
     * add account history
     * @param array $data
     * @return boolean
     */
    public function add_account_history($data)
    {
        $this->db->where('rel_id', $data['id']);
        $this->db->where('rel_type', $data['type']);
        $this->db->delete(db_prefix() . 'acc_account_history');

        $data['amount'] = str_replace(',', '', $data['amount']);

        $data_insert = [];
        if ($data['type'] == 'invoice') {
            $this->load->model('invoices_model');
            $invoice = $this->invoices_model->get($data['id']);

            $this->load->model('currencies_model');
            $currency = $this->currencies_model->get_base_currency();

            $currency_converter = 0;
            if ($invoice->currency_name != $currency->name) {
                $currency_converter = 1;
            }

            $payment_account = $data['payment_account'];
            $deposit_to = $data['deposit_to'];
            $invoice_payment_account = get_option('acc_invoice_payment_account');
            $invoice_deposit_to = get_option('acc_invoice_deposit_to');
            $item_amount = $data['item_amount'];
            $paid = 0;
            if ($invoice->status == 2) {
                $paid = 1;
            }

            foreach ($invoice->items as $value) {
                $item = $this->get_item_by_name($value['description']);
                $item_id = 0;
                if (isset($item->id)) {
                    $item_id = $item->id;
                }

                $item_total = $value['qty'] * $value['rate'];
                if (isset($data['exchange_rate'])) {
                    $item_total = round(($value['qty'] * $value['rate']) * $data['exchange_rate'], 2);
                } elseif ($currency_converter == 1) {
                    $item_total = round($this->currency_converter($invoice->currency_name, $currency->name, $value['qty'] * $value['rate']), 2);
                }

                if (isset($payment_account[$item_id])) {
                    $node = [];
                    $node['split'] = $payment_account[$item_id];
                    $node['account'] = $deposit_to[$item_id];
                    $node['debit'] = $item_total;
                    $node['paid'] = $paid;
                    $node['date'] = $invoice->date;
                    $node['item'] = $item_id;
                    $node['customer'] = $invoice->clientid;
                    $node['tax'] = 0;
                    $node['credit'] = 0;
                    $node['description'] = '';
                    $node['rel_id'] = $data['id'];
                    $node['rel_type'] = $data['type'];
                    $node['datecreated'] = date('Y-m-d H:i:s');
                    $node['addedfrom'] = get_staff_user_id();
                    $data_insert[] = $node;

                    $node = [];
                    $node['split'] = $deposit_to[$item_id];
                    $node['paid'] = $paid;
                    $node['date'] = $invoice->date;
                    $node['item'] = $item_id;
                    $node['account'] = $payment_account[$item_id];
                    $node['customer'] = $invoice->clientid;
                    $node['tax'] = 0;
                    $node['debit'] = 0;
                    $node['credit'] = $item_total;
                    $node['description'] = '';
                    $node['rel_id'] = $data['id'];
                    $node['rel_type'] = $data['type'];
                    $node['datecreated'] = date('Y-m-d H:i:s');
                    $node['addedfrom'] = get_staff_user_id();
                    $data_insert[] = $node;
                } else {
                    $node = [];
                    $node['split'] = $invoice_payment_account;
                    $node['account'] = $invoice_deposit_to;
                    $node['date'] = $invoice->date;
                    $node['item'] = $item_id;
                    $node['debit'] = $item_total;
                    $node['customer'] = $invoice->clientid;
                    $node['paid'] = $paid;
                    $node['tax'] = 0;
                    $node['credit'] = 0;
                    $node['description'] = '';
                    $node['rel_id'] = $data['id'];
                    $node['rel_type'] = $data['type'];
                    $node['datecreated'] = date('Y-m-d H:i:s');
                    $node['addedfrom'] = get_staff_user_id();
                    $data_insert[] = $node;

                    $node = [];
                    $node['split'] = $invoice_deposit_to;
                    $node['customer'] = $invoice->clientid;
                    $node['account'] = $invoice_payment_account;
                    $node['date'] = $invoice->date;
                    $node['item'] = $item_id;
                    $node['paid'] = $paid;
                    $node['tax'] = 0;
                    $node['debit'] = 0;
                    $node['credit'] = $item_total;
                    $node['description'] = '';
                    $node['rel_id'] = $data['id'];
                    $node['rel_type'] = $data['type'];
                    $node['datecreated'] = date('Y-m-d H:i:s');
                    $node['addedfrom'] = get_staff_user_id();
                    $data_insert[] = $node;
                }
            }

            if (get_option('acc_tax_automatic_conversion') == 1) {
                $tax_payment_account = get_option('acc_tax_payment_account');
                $tax_deposit_to = get_option('acc_tax_deposit_to');

                $items = get_items_table_data($invoice, 'invoice', 'html', true);
                foreach ($items->taxes() as $tax) {
                    $t = explode('|', $tax['tax_name']);
                    $tax_name = '';
                    $tax_rate = 0;
                    if (isset($t[0])) {
                        $tax_name = $t[0];
                    }
                    if (isset($t[1])) {
                        $tax_rate = $t[1];
                    }

                    $this->db->where('name', $tax_name);
                    $this->db->where('taxrate', $tax_rate);
                    $_tax = $this->db->get(db_prefix() . 'taxes')->row();

                    $total_tax = $tax['total_tax'];
                    if (isset($data['exchange_rate'])) {
                        $total_tax = round($tax['total_tax'] * $data['exchange_rate'], 2);
                    } elseif ($currency_converter == 1) {
                        $total_tax = round($this->currency_converter($invoice->currency_name, $currency->name, $tax['total_tax']), 2);
                    }

                    if ($_tax) {
                        $tax_mapping = $this->get_tax_mapping($_tax->id);

                        if ($tax_mapping) {
                            $node = [];
                            $node['split'] = $tax_mapping->payment_account;
                            $node['account'] = $tax_mapping->deposit_to;
                            $node['tax'] = $_tax->id;
                            $node['item'] = 0;
                            $node['date'] = $invoice->date;
                            $node['paid'] = $paid;
                            $node['debit'] = $total_tax;
                            $node['customer'] = $invoice->clientid;
                            $node['credit'] = 0;
                            $node['description'] = '';
                            $node['rel_id'] = $data['id'];
                            $node['rel_type'] = 'invoice';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;

                            $node = [];
                            $node['split'] = $tax_mapping->deposit_to;
                            $node['customer'] = $invoice->clientid;
                            $node['account'] = $tax_mapping->payment_account;
                            $node['tax'] = $_tax->id;
                            $node['item'] = 0;
                            $node['date'] = $invoice->date;
                            $node['paid'] = $paid;
                            $node['debit'] = 0;
                            $node['credit'] = $total_tax;
                            $node['description'] = '';
                            $node['rel_id'] = $data['id'];
                            $node['rel_type'] = 'invoice';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;
                        } else {
                            $node = [];
                            $node['split'] = $tax_payment_account;
                            $node['account'] = $tax_deposit_to;
                            $node['tax'] = $_tax->id;
                            $node['item'] = 0;
                            $node['date'] = $invoice->date;
                            $node['paid'] = $paid;
                            $node['debit'] = $total_tax;
                            $node['customer'] = $invoice->clientid;
                            $node['credit'] = 0;
                            $node['description'] = '';
                            $node['rel_id'] = $data['id'];
                            $node['rel_type'] = 'invoice';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;

                            $node = [];
                            $node['split'] = $tax_deposit_to;
                            $node['customer'] = $invoice->clientid;
                            $node['date'] = $invoice->date;
                            $node['account'] = $tax_payment_account;
                            $node['tax'] = $_tax->id;
                            $node['item'] = 0;
                            $node['paid'] = $paid;
                            $node['debit'] = 0;
                            $node['credit'] = $total_tax;
                            $node['description'] = '';
                            $node['rel_id'] = $data['id'];
                            $node['rel_type'] = 'invoice';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;
                        }
                    } else {
                        $node = [];
                        $node['split'] = $tax_payment_account;
                        $node['account'] = $tax_deposit_to;
                        $node['item'] = 0;
                        $node['tax'] = 0;
                        $node['date'] = $invoice->date;
                        $node['paid'] = $paid;
                        $node['debit'] = $total_tax;
                        $node['customer'] = $invoice->clientid;
                        $node['credit'] = 0;
                        $node['description'] = '';
                        $node['rel_id'] = $data['id'];
                        $node['rel_type'] = 'invoice';
                        $node['datecreated'] = date('Y-m-d H:i:s');
                        $node['addedfrom'] = get_staff_user_id();
                        $data_insert[] = $node;

                        $node = [];
                        $node['split'] = $tax_deposit_to;
                        $node['customer'] = $invoice->clientid;
                        $node['account'] = $tax_payment_account;
                        $node['date'] = $invoice->date;
                        $node['tax'] = 0;
                        $node['item'] = 0;
                        $node['paid'] = $paid;
                        $node['debit'] = 0;
                        $node['credit'] = $total_tax;
                        $node['description'] = '';
                        $node['rel_id'] = $data['id'];
                        $node['rel_type'] = 'invoice';
                        $node['datecreated'] = date('Y-m-d H:i:s');
                        $node['addedfrom'] = get_staff_user_id();
                        $data_insert[] = $node;
                    }
                }
            }
        } else {
            $customer = 0;
            $date = date('Y-m-d');
            if ($data['type'] == 'payment') {
                $this->load->model('payments_model');
                $this->load->model('invoices_model');
                $payment = $this->payments_model->get($data['id']);
                $date = $payment->date;
                $invoice = $this->invoices_model->get($payment->invoiceid);

                $this->automatic_invoice_conversion($payment->invoiceid);

                $customer = $invoice->clientid;

                $this->load->model('currencies_model');
                $currency = $this->currencies_model->get_base_currency();

                if (isset($data['exchange_rate'])) {
                    $data['amount'] = round($data['amount'] * $data['exchange_rate'], 2);
                } elseif ($invoice->currency_name != $currency->name) {
                    $data['amount'] = round($this->currency_converter($invoice->currency_name, $currency->name, $data['amount']), 2);
                }
            } elseif ($data['type'] == 'expense') {
                $this->load->model('expenses_model');
                $expense = $this->expenses_model->get($data['id']);
                $date = $expense->date;
                $customer = $expense->clientid;

                $this->load->model('currencies_model');
                $currency = $this->currencies_model->get_base_currency();

                if (isset($data['exchange_rate'])) {
                    $data['amount'] = round($data['amount'] * $data['exchange_rate'], 2);
                } elseif ($expense->currency_data->name != $currency->name) {
                    $data['amount'] = round($this->currency_converter($expense->currency_data->name, $currency->name, $data['amount']), 2);
                }

                if (get_option('acc_tax_automatic_conversion') == 1) {
                    $tax_payment_account = get_option('acc_tax_payment_account');
                    $tax_deposit_to = get_option('acc_tax_deposit_to');

                    if ($expense->tax > 0) {
                        $this->db->where('id', $expense->tax);
                        $tax = $this->db->get(db_prefix() . 'taxes')->row();
                        $total_tax = 0;
                        if ($tax) {
                            $total_tax = ($tax->taxrate / 100) * $data['amount'];
                        }
                        $tax_mapping = $this->get_tax_mapping($expense->tax);
                        if ($tax_mapping) {
                            $node = [];
                            $node['split'] = $tax_mapping->expense_payment_account;
                            $node['account'] = $tax_mapping->expense_deposit_to;
                            $node['tax'] = $expense->tax;
                            $node['debit'] = $total_tax;
                            $node['credit'] = 0;
                            $node['customer'] = $expense->clientid;
                            $node['date'] = $expense->date;
                            $node['description'] = '';
                            $node['rel_id'] = $data['id'];
                            $node['rel_type'] = 'expense';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;

                            $node = [];
                            $node['split'] = $tax_mapping->expense_deposit_to;
                            $node['customer'] = $expense->clientid;
                            $node['account'] = $tax_mapping->expense_payment_account;
                            $node['tax'] = $expense->tax;
                            $node['date'] = $expense->date;
                            $node['debit'] = 0;
                            $node['credit'] = $total_tax;
                            $node['description'] = '';
                            $node['rel_id'] = $data['id'];
                            $node['rel_type'] = 'expense';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;
                        } else {
                            $node = [];
                            $node['split'] = $tax_payment_account;
                            $node['account'] = $tax_deposit_to;
                            $node['tax'] = $expense->tax;
                            $node['date'] = $expense->date;
                            $node['debit'] = $total_tax;
                            $node['customer'] = $expense->clientid;
                            $node['credit'] = 0;
                            $node['description'] = '';
                            $node['rel_id'] = $data['id'];
                            $node['rel_type'] = 'expense';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;

                            $node = [];
                            $node['split'] = $tax_deposit_to;
                            $node['customer'] = $expense->clientid;
                            $node['account'] = $tax_payment_account;
                            $node['date'] = $expense->date;
                            $node['tax'] = $expense->tax;
                            $node['debit'] = 0;
                            $node['credit'] = $total_tax;
                            $node['description'] = '';
                            $node['rel_id'] = $data['id'];
                            $node['rel_type'] = 'expense';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;
                        }
                    }

                    if ($expense->tax2 > 0) {
                        $this->db->where('id', $expense->tax2);
                        $tax = $this->db->get(db_prefix() . 'taxes')->row();
                        $total_tax = 0;
                        if ($tax) {
                            $total_tax = ($tax->taxrate / 100) * $data['amount'];
                        }
                        $tax_mapping = $this->get_tax_mapping($expense->tax2);
                        if ($tax_mapping) {
                            $node = [];
                            $node['split'] = $tax_mapping->expense_payment_account;
                            $node['account'] = $tax_mapping->expense_deposit_to;
                            $node['tax'] = $expense->tax2;
                            $node['debit'] = $total_tax;
                            $node['credit'] = 0;
                            $node['customer'] = $expense->clientid;
                            $node['date'] = $expense->date;
                            $node['description'] = '';
                            $node['rel_id'] = $expense_id;
                            $node['rel_type'] = 'expense';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;

                            $node = [];
                            $node['split'] = $tax_mapping->expense_deposit_to;
                            $node['customer'] = $expense->clientid;
                            $node['account'] = $tax_mapping->expense_payment_account;
                            $node['tax'] = $expense->tax2;
                            $node['date'] = $expense->date;
                            $node['debit'] = 0;
                            $node['credit'] = $total_tax;
                            $node['description'] = '';
                            $node['rel_id'] = $expense_id;
                            $node['rel_type'] = 'expense';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;
                        } else {
                            $node = [];
                            $node['split'] = $tax_payment_account;
                            $node['account'] = $tax_deposit_to;
                            $node['tax'] = $expense->tax2;
                            $node['date'] = $expense->date;
                            $node['debit'] = $total_tax;
                            $node['customer'] = $expense->clientid;
                            $node['credit'] = 0;
                            $node['description'] = '';
                            $node['rel_id'] = $expense_id;
                            $node['rel_type'] = 'expense';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;

                            $node = [];
                            $node['split'] = $tax_deposit_to;
                            $node['customer'] = $expense->clientid;
                            $node['account'] = $tax_payment_account;
                            $node['date'] = $expense->date;
                            $node['tax'] = $expense->tax2;
                            $node['debit'] = 0;
                            $node['credit'] = $total_tax;
                            $node['description'] = '';
                            $node['rel_id'] = $expense_id;
                            $node['rel_type'] = 'expense';
                            $node['datecreated'] = date('Y-m-d H:i:s');
                            $node['addedfrom'] = get_staff_user_id();
                            $data_insert[] = $node;
                        }
                    }
                }
            } elseif ($data['type'] == 'banking') {
                $banking = $this->get_transaction_banking($data['id']);
                if ($banking) {
                    $date = $banking->date;
                }
            }

            $node = [];
            $node['split'] = $data['payment_account'];
            $node['account'] = $data['deposit_to'];
            $node['debit'] = $data['amount'];
            $node['customer'] = $customer;
            $node['date'] = $date;
            $node['credit'] = 0;
            $node['tax'] = 0;
            $node['description'] = '';
            $node['rel_id'] = $data['id'];
            $node['rel_type'] = $data['type'];
            $node['datecreated'] = date('Y-m-d H:i:s');
            $node['addedfrom'] = get_staff_user_id();
            $data_insert[] = $node;

            $node = [];
            $node['split'] = $data['deposit_to'];
            $node['account'] = $data['payment_account'];
            $node['customer'] = $customer;
            $node['date'] = $date;
            $node['tax'] = 0;
            $node['debit'] = 0;
            $node['credit'] = $data['amount'];
            $node['description'] = '';
            $node['rel_id'] = $data['id'];
            $node['rel_type'] = $data['type'];
            $node['datecreated'] = date('Y-m-d H:i:s');
            $node['addedfrom'] = get_staff_user_id();
            $data_insert[] = $node;
        }

        $affectedRows = $this->db->insert_batch(db_prefix() . 'acc_account_history', $data_insert);

        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }

    /**
     * add transfer
     * @param array $data
     * @return boolean
     */
    public function add_transfer($data)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }
        $data['date'] = to_sql_date($data['date']);
        if (get_option('acc_close_the_books') == 1) {
            if (strtotime($data['date']) <= strtotime(get_option('acc_closing_date')) && strtotime(date('Y-m-d')) > strtotime(get_option('acc_closing_date'))) {
                return 'close_the_book';
            }
        }
        $data['transfer_amount'] = str_replace(',', '', $data['transfer_amount']);
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom'] = get_staff_user_id();

        $this->db->insert(db_prefix() . 'acc_transfers', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            $node = [];
            $node['split'] = $data['transfer_funds_to'];
            $node['account'] = $data['transfer_funds_from'];
            $node['debit'] = 0;
            $node['date'] = $data['date'];
            $node['credit'] = $data['transfer_amount'];
            $node['rel_id'] = $insert_id;
            $node['rel_type'] = 'transfer';
            $node['datecreated'] = date('Y-m-d H:i:s');
            $node['addedfrom'] = get_staff_user_id();

            $this->db->insert(db_prefix() . 'acc_account_history', $node);

            $node = [];
            $node['split'] = $data['transfer_funds_from'];
            $node['account'] = $data['transfer_funds_to'];
            $node['debit'] = $data['transfer_amount'];
            $node['date'] = $data['date'];
            $node['credit'] = 0;
            $node['rel_id'] = $insert_id;
            $node['rel_type'] = 'transfer';
            $node['datecreated'] = date('Y-m-d H:i:s');
            $node['addedfrom'] = get_staff_user_id();

            $this->db->insert(db_prefix() . 'acc_account_history', $node);

            return true;
        }

        return false;
    }


    /**
     * import xlsx banking
     * @param array $data
     * @return integer or boolean
     */
    public function import_xlsx_banking($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom'] = get_staff_user_id();
        $data['date'] = str_replace('/', '-', $data['date']);
        $data['date'] = date("Y-m-d", strtotime($data['date']));
        $this->db->insert(db_prefix() . 'acc_transaction_bankings', $data);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    /**
     * get transaction banking
     * @param string $id
     * @param array $where
     * @return array or object
     */
    public function get_transaction_banking($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'acc_transaction_bankings')->row();
        }

        $this->db->where($where);
        $this->db->order_by('id', 'desc');
        return $this->db->get(db_prefix() . 'acc_transaction_bankings')->result_array();
    }

    /**
     * get journal entry
     * @param integer $id
     * @return object
     */
    public function get_journal_entry($id)
    {
        $this->db->where('id', $id);
        $journal_entrie = $this->db->get(db_prefix() . 'acc_journal_entries')->row();

        if ($journal_entrie) {
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'journal_entry');
            $details = $this->db->get(db_prefix() . 'acc_account_history')->result_array();

            $data_details = [];
            foreach ($details as $key => $value) {
                $data_details[] = [
                    "account" => $value['account'],
                    "debit" => floatval($value['debit']),
                    "credit" => floatval($value['credit']),
                    "description" => $value['description']];
            }
            if (count($data_details) < 10) {

            }
            $journal_entrie->details = $data_details;
        }

        return $journal_entrie;
    }

    /**
     * delete journal entry
     * @param integer $id
     * @return boolean
     */

    public function delete_journal_entry($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'acc_journal_entries');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'journal_entry');
            $this->db->delete(db_prefix() . 'acc_account_history');

            return true;
        }
        return false;
    }


    /**
     * check format date Y-m-d
     *
     * @param String $date The date
     *
     * @return     boolean
     */
    public function check_format_date($date)
    {
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
            return true;
        } else {
            return false;
        }
    }


    public function delete_account($id)
    {
        $this->db->where('(account = ' . $id . ' or split = ' . $id . ')');
        $count = $this->db->count_all_results(db_prefix() . 'acc_account_history');

        if ($count > 0) {
            return 'have_transaction';
        }

        $this->db->where('id', $id);
        $this->db->where('default_account', 0);
        $this->db->delete(db_prefix() . 'acc_accounts');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('account', $id);
            $this->db->delete(db_prefix() . 'acc_account_history');

            return true;
        }
        return false;
    }

    /**
     * Change account status / active / inactive
     * @param mixed $id staff id
     * @param mixed $status status(0/1)
     */
    public function change_account_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'acc_accounts', [
            'active' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }


    /**
     * api get
     * @param string $url
     * @return string
     */
    public function api_get($url)
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);

        return curl_exec($curl);
    }


    /**
     * get account data tables
     * @param array $aColumns colunas da tabela
     * @param mixed $sIndexColumn indice principal para melhor performance
     * @param string $sTable nome da tabela
     * @param array $join join para outras tabelas
     * @param array $where executa where
     * @param array $additionalSelect seleciona campos adicionais
     * @param string $sGroupBy agrupado por
     * @return array
     */
    function obter_datatable_planocontas($aColumns, $sIndexColumn, $sTable, $join = [], $where = [], $additionalSelect = [], $sGroupBy = '', $searchAs = [])
    {
        $CI = &get_instance();
        $__post = $CI->input->post();
        $where = implode(' ', $where);
        $where = trim($where);
        if (startsWith($where, 'AND') || startsWith($where, 'OR')) {
            if (startsWith($where, 'OR')) {
                $where = substr($where, 2);
            } else {
                $where = substr($where, 3);
            }

            $this->db->where($where);
        }

        if (!$this->input->post('ft_account')) {
            $this->db->where('(conta_pai is null or conta_pai = 0)');
        }

        // Pega o somatorio de debito e creidto do histórico
        $debit = '(SELECT sum(debito) as debito FROM ' . db_prefix() .
            'fin_historico_contas where (conta_id = ' . db_prefix() . 'fin_planocontas.id or subconta_id = ' .
            db_prefix() . 'fin_planocontas.id))';
        $credit = '(SELECT sum(credito) as credito FROM ' . db_prefix() . 'fin_historico_contas where (conta_id = ' . db_prefix() . 'fin_planocontas.id or subconta_id = ' . db_prefix() . 'fin_planocontas.id))';

        $this->db->select('id, numeroconta, nomeconta, conta_pai, tipo_conta, saldo,chave_conta, ativo, descricao,  ' . $debit . ', ' . $credit );
        // $this->db->limit(intval($CI->input->post('length')), intval($CI->input->post('start')));
        $this->db->order_by('id', 'desc');

        $accounts = $this->db->get(db_prefix() . 'fin_planocontas')->result_array();

        $rResult = [];

        foreach ($accounts as $key => $value) {
            $rResult[] = $value;
            $rResult = $this->get_recursive_account($rResult, $value['id'], $where, 1);
        }

        /* Data set length after filtering */
        $sQuery = 'SELECT FOUND_ROWS()';
        $_query = $CI->db->query($sQuery)->result_array();
        $iFilteredTotal = $_query[0]['FOUND_ROWS()'];

        /* Total data set length */
        $sQuery = '
        SELECT COUNT(' . $sTable . '.' . $sIndexColumn . ")
        FROM $sTable " . ($where != '' ? 'WHERE ' . $where : $where);
        $_query = $CI->db->query($sQuery)->result_array();

        $iTotal = $_query[0]['COUNT(' . $sTable . '.' . $sIndexColumn . ')'];
        /*
         * Output
         */
        $output = [
            'draw' => $__post['draw'] ? intval($__post['draw']) : 0,
            'iTotalRecords' => $iTotal,
            'iTotalDisplayRecords' => $iTotal,
            'aaData' => [],
        ];

        return [
            'rResult' => $rResult,
            'output' => $output,
        ];
    }

    /**
     * get recursive account
     * @param array $accounts
     * @param integer $account_id
     * @param string $where
     * @param integer $number
     * @return array
     */
    public function get_recursive_account($accounts, $account_id, $where, $number)
    {
        $this->db->select('id, numeroconta, nomeconta, conta_pai, tipo_conta, saldo,chave_conta, ativo, descricao');
        if ($where != '')
        {
            $this->db->where($where);
        }

        $this->db->where('conta_pai', $account_id);
        $this->db->order_by('numero, nomeconta', 'asc');
        $account_list = $this->db->get(db_prefix() . 'fin_planocontas')->result_array();

        echo(var_dump($accoun_list));

        if ($account_list) {
            foreach ($account_list as $key => $value) {
                foreach ($accounts as $k => $val) {
                    if ($value['id'] == $val['id']) {
                        unset($accounts[$k]);
                    }
                }

                $value['level'] = $number;
                array_push($accounts, $value);
                $accounts = $this->get_recursive_account($accounts, $value['id'], $where, $number + 1);
            }
        }

        return $accounts;
    }


    /**
     * Obter tipos de Conta
     * @param integer $id member group id
     * @param array $where
     * @return object
     */
    public function obter_tipos_conta()
    {
        // Aplica filtro antes_tipo_contas?
        $tipos_de_conta = hooks()->apply_filters('before_get_account_types', [
            [
                'id' => 1,
                'name' => 'Crédito',
                'order' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Débito',
                'order' => 2,
            ],
        ]);

        // Ordena o array
        usort($tipos_de_conta, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $tipos_de_conta;
    }

    /**
     * get accounts
     * @param integer $id member group id
     * @param array $where
     * @return object
     */
    public function get_accounts($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'acc_accounts')->row();
        }

        $this->db->where($where);
        $this->db->where('active', 1);
        $this->db->order_by('account_type_id,account_detail_type_id', 'desc');
        $accounts = $this->db->get(db_prefix() . 'acc_accounts')->result_array();

        $account_types = $this->accounting_model->get_account_types();
        $detail_types = $this->accounting_model->get_account_type_details();

        $account_type_name = [];
        $detail_type_name = [];

        foreach ($account_types as $key => $value) {
            $account_type_name[$value['id']] = $value['name'];
        }

        foreach ($detail_types as $key => $value) {
            $detail_type_name[$value['id']] = $value['name'];
        }

        foreach ($accounts as $key => $value) {
            if ($value['name'] == '' && $value['key_name'] != '') {
                $accounts[$key]['name'] = _l($value['key_name']);
            }
            $_account_type_name = isset($account_type_name[$value['account_type_id']]) ? $account_type_name[$value['account_type_id']] : '';
            $_detail_type_name = isset($detail_type_name[$value['account_detail_type_id']]) ? $detail_type_name[$value['account_detail_type_id']] : '';
            $accounts[$key]['account_type_name'] = $_account_type_name;
            $accounts[$key]['detail_type_name'] = $_detail_type_name;
        }

        return $accounts;
    }


    /**
     * Obter as contas do Plano de Contas
     * @param  integer $id    member group id
     * @param  array  $where
     * @return object
     */
    public function obter_contas($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'fin_planocontas')->row();
        }

        $this->db->where($where);
        $this->db->where('ativo', 1);
        $this->db->order_by('tipo_conta', 'desc');
        $contas = $this->db->get(db_prefix() . 'fin_planocontas')->result_array();

        $tipos_conta = $this->planocontas_model->obter_tipos_conta();
        $nome_tipo_conta = [];

        foreach ($tipos_conta as $key => $value)
        {
            $nome_tipo_conta[$value['id']] = $value['name'];
        }


        foreach ($contas as $key => $value)
        {
            if ($value['nomeconta'] == '' && $value['chave_conta'] != '')
            {
                $contas[$key]['nomeconta'] = _l($value['chave_conta']);
            }
            $_nome_tipo_conta = isset($nome_tipo_conta[$value['tipo_conta']]) ? $nome_tipo_conta[$value['tipo_conta']] : '';
            $contas[$key]['nome_tipo_conta'] = $_nome_tipo_conta;

        }


        return $contas;

    }

   public function obter_contas_credito() {

   }

   public function obter_subcontas_credito() {

   }

}
