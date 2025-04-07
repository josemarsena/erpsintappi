<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get contract short_url
 * @since  Version 2.7.3
 * @param  object $contract
 * @return string Url
 */
function get_contract_shortlink($contract)
{
    $long_url = site_url("contract/{$contract->id}/{$contract->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if contract has short link, if yes return short link
    if (!empty($contract->short_link)) {
        return $contract->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url' => $long_url,
        'title'    => 'Contract #' . $contract->id,
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $contract->id);
        $CI->db->update(db_prefix() . 'contracts', [
            'short_link' => $short_link,
        ]);

        return $short_link;
    }

    return $long_url;
}

/**
 * Check the contract view restrictions
 *
 * @param  int $id
 * @param  string $hash
 *
 * @return void
 */
function check_contract_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('contracts_model');

    if (!$hash || !$id) {
        show_404();
    }

    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_contract_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }

    $contract = $CI->contracts_model->get($id);

    if (!$contract || ($contract->hash != $hash)) {
        show_404();
    }

    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_contract_only_logged_in') == 1) {
            if ($contract->client != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Function that will search possible contracts templates in applicaion/views/admin/contracts/templates
 * Will return any found files and user will be able to add new template
 *
 * @return array
 */
function get_contract_templates()
{
    $contract_templates = [];
    if (is_dir(VIEWPATH . 'admin/contracts/templates')) {
        foreach (list_files(VIEWPATH . 'admin/contracts/templates') as $template) {
            $contract_templates[] = $template;
        }
    }

    return $contract_templates;
}

/**
 * Send contract signed notification to staff members
 *
 * @param  int $contract_id
 *
 * @return void
 */
function send_contract_signed_notification_to_staff($contract_id)
{
    $CI = &get_instance();
    $CI->db->where('id', $contract_id);
    $contract = $CI->db->get(db_prefix() . 'contracts')->row();

    if (!$contract) {
        return false;
    }

    // Get creator
    $CI->db->select('staffid, email');
    $CI->db->where('staffid', $contract->addedfrom);
    $staff_contract = $CI->db->get(db_prefix() . 'staff')->result_array();

    $notifiedUsers = [];

    foreach ($staff_contract as $member) {
        $notified = add_notification([
            'description'     => 'not_contract_signed',
            'touserid'        => $member['staffid'],
            'fromcompany'     => 1,
            'fromuserid'      => 0,
            'link'            => 'contracts/contract/' . $contract->id,
            'additional_data' => serialize([
                '<b>' . $contract->subject . '</b>',
            ]),
        ]);

        if ($notified) {
            array_push($notifiedUsers, $member['staffid']);
        }

        send_mail_template('contract_signed_to_staff', $contract, $member);
    }

    pusher_trigger_notification($notifiedUsers);
}

/**
 * Get the recently created contracts in the given days
 *
 * @param  integer $days
 * @param  integer|null $staffId
 *
 * @return integer
 */
function count_recently_created_contracts($days = 7, $staffId = null)
{
    $diff1     = date('Y-m-d', strtotime('-' . $days . ' days'));
    $diff2     = date('Y-m-d', strtotime('+' . $days . ' days'));
    $staffId   = is_null($staffId) ? get_staff_user_id() : $staffId;
    $where_own = [];

    if (staff_cant('view', 'contracts')) {
        $where_own = ['addedfrom' => $staffId];
    }

    return total_rows(db_prefix() . 'contracts', 'dateadded BETWEEN "' . $diff1 . '" AND "' . $diff2 . '" AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : ''));
}

/**
 * Get total number of active contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function count_active_contracts($staffId = null)
{
    $where_own = [];
    $staffId   = is_null($staffId) ? get_staff_user_id() : $staffId;

    if (staff_cant('view', 'contracts')) {
        $where_own = ['addedfrom' => $staffId];
    }

    return total_rows(db_prefix() . 'contracts', '(DATE(dateend) >"' . date('Y-m-d') . '" AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : '') . ') OR (DATE(dateend) IS NULL AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : '') . ')');
}

/**
 * Get total number of expired contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function count_expired_contracts($staffId = null)
{
    $where_own = [];
    $staffId   = is_null($staffId) ? get_staff_user_id() : $staffId;

    if (staff_cant('view', 'contracts')) {
        $where_own = ['addedfrom' => $staffId];
    }

    return total_rows(db_prefix() . 'contracts', array_merge(['DATE(dateend) <' => date('Y-m-d'), 'trash' => 0], $where_own));
}

/**
 * Get total number of trash contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function count_trash_contracts($staffId = null)
{
    $where_own = [];
    $staffId   = is_null($staffId) ? get_staff_user_id() : $staffId;

    if (staff_cant('view', 'contracts')) {
        $where_own = ['addedfrom' => $staffId];
    }

    return total_rows(db_prefix() . 'contracts', array_merge(['trash' => 1], $where_own));
}


/**
 * Tasks html table used all over the application for relation tasks
 * This table is not used for the main tasks table
 * @param  array  $table_attributes
 * @return string
 */
function init_relation_contracts_table($table_attributes = [], $filtersWrapperId = 'vueApp', $filtersDetached = false)
{
    $table_data = array(
        'Fatura#',
        _l('invoice_dt_table_heading_date'),
        _l('invoice_dt_table_heading_duedate'),
        _l('invoice_dt_table_heading_amount'),
        _l('invoice_total_tax'),
        _l('invoice_dt_table_heading_status'));


    $custom_fields = get_custom_fields('invoice',array('show_on_table'=>1));
    foreach($custom_fields as $field){
        array_push($table_data, [
            'name' => $field['name'],
            'th_attrs' => array('data-type'=>$field['type'], 'data-custom-field'=>1)
        ]);
    }

    $table_data = hooks()->apply_filters('contracts_related_table_columns', $table_data);

    $name = 'rel-tasks';
    if ($table_attributes['data-new-rel-type'] == 'lead') {
        $name = 'rel-tasks-leads';
    }

    $tasks_table = App_table::find('invoices');

    $table      = '';
    $CI         = &get_instance();
    $table_name = '.table-' . $name;

    $CI->load->view('admin/tasks/filters', [
        'tasks_table'=>$tasks_table,
        'filters_wrapper_id'=>$filtersWrapperId,
        'detached'=>$filtersDetached,
    ]);

    if (staff_can('create',  'invoices')) {
        $disabled   = '';
        $table_name = addslashes($table_name);
        if ($table_attributes['data-new-rel-type'] == 'customer' && is_numeric($table_attributes['data-new-rel-id'])) {
            if (total_rows(db_prefix() . 'clients', [
                    'active' => 0,
                    'userid' => $table_attributes['data-new-rel-id'],
                ]) > 0) {
                $disabled = ' disabled';
            }
        }
        // projects have button on top
        if ($table_attributes['data-new-rel-type'] != 'project') {
            echo "<a href='#' class='btn btn-primary pull-left mright5 new-task-relation" . $disabled . "' onclick=\"new_task_from_relation('$table_name'); return false;\" data-rel-id='" . $table_attributes['data-new-rel-id'] . "' data-rel-type='" . $table_attributes['data-new-rel-type'] . "'><i class=\"fa-regular fa-plus tw-mr-1\"></i>" . _l('new_task') . '</a>';
        }
    }

    if ($table_attributes['data-new-rel-type'] == 'project') {
        echo "<div class='tw-mb-4 tw-space-x-3 rtl:tw-space-x-reverse'><a href='" . admin_url('tasks/list_tasks?project_id=' . $table_attributes['data-new-rel-id'] . '&kanban=true') . "' class='btn btn-default mright5 hidden-xs' data-toggle='tooltip' data-title='" . _l('view_kanban') . "' data-placement='top'><i class='fa-solid fa-grip-vertical'></i></a>";
        echo "<a href='" . admin_url('tasks/detailed_overview?project_id=' . $table_attributes['data-new-rel-id']) . "' class='tw-text-neutral-600 hover:tw-text-neutral-800 focus:tw-text-neutral-800 tw-font-semibold'>" . _l('detailed_overview') . ' &rarr;</a></div>';
        echo '<div class="clearfix"></div>';
        echo $CI->load->view('admin/tasks/_bulk_actions', ['table' => '.table-rel-tasks'], true);
        echo '<div class="tw-mb-4">';
        echo $CI->load->view('admin/tasks/_summary', ['rel_id' => $table_attributes['data-new-rel-id'], 'rel_type' => 'project', 'table' => $table_name], true);
        echo '</div>';
        echo '<a href="#" data-toggle="modal" data-target="#tasks_bulk_actions" class="hide bulk-actions-btn table-btn" data-table=".table-rel-tasks">' . _l('bulk_actions') . '</a>';
    } elseif ($table_attributes['data-new-rel-type'] == 'customer') {
        echo '<div class="clearfix"></div>';
        echo '<div id="tasks_related_filter" class="mtop15">';
        echo '<p class="bold">' . _l('task_related_to') . ': </p>';

        echo '<div class="checkbox checkbox-inline">
        <input type="checkbox" checked value="customer" disabled id="ts_rel_to_customer" name="tasks_related_to[]">
        <label for="ts_rel_to_customer">' . _l('client') . '</label>
        </div>

        <div class="checkbox checkbox-inline">
        <input type="checkbox" value="project" id="ts_rel_to_project" name="tasks_related_to[]">
        <label for="ts_rel_to_project">' . _l('projects') . '</label>
        </div>

        <div class="checkbox checkbox-inline">
        <input type="checkbox" value="invoice" id="ts_rel_to_invoice" name="tasks_related_to[]">
        <label for="ts_rel_to_invoice">' . _l('invoices') . '</label>
        </div>

        <div class="checkbox checkbox-inline">
        <input type="checkbox" value="estimate" id="ts_rel_to_estimate" name="tasks_related_to[]">
        <label for="ts_rel_to_estimate">' . _l('estimates') . '</label>
        </div>

        <div class="checkbox checkbox-inline">
        <input type="checkbox" value="contract" id="ts_rel_to_contract" name="tasks_related_to[]">
        <label for="ts_rel_to_contract">' . _l('contracts') . '</label>
        </div>

        <div class="checkbox checkbox-inline">
        <input type="checkbox" value="ticket" id="ts_rel_to_ticket" name="tasks_related_to[]">
        <label for="ts_rel_to_ticket">' . _l('tickets') . '</label>
        </div>

        <div class="checkbox checkbox-inline">
        <input type="checkbox" value="expense" id="ts_rel_to_expense" name="tasks_related_to[]">
        <label for="ts_rel_to_expense">' . _l('expenses') . '</label>
        </div>

        <div class="checkbox checkbox-inline">
        <input type="checkbox" value="proposal" id="ts_rel_to_proposal" name="tasks_related_to[]">
        <label for="ts_rel_to_proposal">' . _l('proposals') . '</label>
        </div>';
        echo form_hidden('tasks_related_to');
        echo '</div>';
    }
    echo "<div class='clearfix'></div>";

    // If new column is added on tasks relations table this will not work fine
    // In this case we need to add new identifier eq task-relation
    $table_attributes['data-last-order-identifier'] = 'invoices';
    $table_attributes['data-default-order']         = get_table_last_order('invoices');
    if ($table_attributes['data-new-rel-type'] != 'project') {
        echo '<hr />';
    }
    $table_attributes['id'] = 'related_tasks';

    $table .= render_datatable($table_data, $name, ['number-index-1'], $table_attributes);

    return $table;
}