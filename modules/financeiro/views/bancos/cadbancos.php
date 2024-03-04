<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
                <div class="col-md-12">
                    <div class="panel_s mbot10">
                        <div class="panel-body _buttons">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="col-md-3">
                                        <?php if(has_permission('reminder','','create')){ ?>
                                            <a data-toggle="modal" data-target="#reminderAddModal" class="btn btn-info pull-left display-block">
                                                <?php echo _l('reminder_new'); ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="display-block text-right">
                                            <div class="btn-group pull-right mleft4 btn-with-tooltip-group _filter_data" data-toggle="tooltip" data-title="<?php echo _l('filter_by'); ?>">
                                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fa fa-filter" aria-hidden="true"></i>
                                                </button>
                                                <ul class="dropdown-menu width300">
                                                    <li>
                                                        <a href="#" data-cview="all" onclick="dt_custom_view('','.table-reminder',''); return false;">
                                                            <?php echo _l('proposals_list_all'); ?>
                                                        </a>
                                                    </li>
                                                    <li class="divider"></li>
                                                    <?php if(count($years) > 0){ ?>
                                                        <?php foreach($years as $year){ ?>
                                                            <li class="active">
                                                                <a href="#" data-cview="year_<?php echo $year['year']; ?>" onclick="dt_custom_view(<?php echo $year['year']; ?>,'.table-reminder','year_<?php echo $year['year']; ?>'); return false;"><?php echo $year['year']; ?>
                                                                </a>
                                                            </li>
                                                        <?php } ?>
                                                        <li class="divider"></li>
                                                    <?php } ?>
                                                    <li>
                                                        <a href="#" data-cview="isnotified" onclick="dt_custom_view('1','.table-reminder','isnotified'); return false;">
                                                            <?php echo _l('show_notified_reminder'); ?>
                                                        </a>
                                                    </li>
                                                    <?php if(count($reminder_sale_agents) > 0){ ?>
                                                        <div class="clearfix"></div>
                                                        <li class="divider"></li>
                                                        <li class="dropdown-submenu pull-left">
                                                            <a href="#" tabindex="-1"><?php echo _l('sale_agent_string'); ?></a>
                                                            <ul class="dropdown-menu dropdown-menu-left">
                                                                <?php foreach($reminder_sale_agents as $agent){ ?>
                                                                    <li>
                                                                        <a href="#" data-cview="sale_agent_<?php echo $agent['sale_agent']; ?>" onclick="dt_custom_view('sale_agent_<?php echo $agent['sale_agent']; ?>','.table-reminder','sale_agent_<?php echo $agent['sale_agent']; ?>'); return false;"><?php echo get_staff_full_name($agent['sale_agent']); ?>
                                                                        </a>
                                                                    </li>
                                                                <?php } ?>
                                                            </ul>
                                                        </li>
                                                    <?php } ?>
                                                    <?php if(count($clients) > 0){ ?>
                                                        <div class="clearfix"></div>
                                                        <li class="divider"></li>
                                                        <li class="dropdown-submenu pull-left">
                                                            <a href="#" tabindex="-1"><?php echo _l('customers'); ?></a>
                                                            <ul class="dropdown-menu dropdown-menu-left">
                                                                <?php foreach($clients as $cust){ ?>
                                                                    <li>
                                                                        <a href="#" data-cview="customer_<?php echo $cust['userid']; ?>" onclick="dt_custom_view('customer_<?php echo $cust['userid']; ?>','.table-reminder','customer_<?php echo $cust['userid']; ?>'); return false;"><?php echo $cust['company']; ?>
                                                                        </a>
                                                                    </li>
                                                                <?php } ?>
                                                            </ul>
                                                        </li>
                                                    <?php } ?>
                                                    <div class="clearfix"></div>
                                                    <li class="divider"></li>
                                                    <li class="dropdown-submenu pull-left">
                                                        <a href="#" tabindex="-1"><?php echo _l('reminder_rel_type'); ?></a>
                                                        <ul class="dropdown-menu dropdown-menu-left">
                                                            <li>
                                                                <a href="#" data-cview="rel_type_quotes" onclick="dt_custom_view('rel_type_quotes','.table-reminder','rel_type_quotes'); return false;"><?php echo _l('rm_proposals'); ?>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="#" data-cview="rel_type_estimate" onclick="dt_custom_view('rel_type_estimate','.table-reminder','rel_type_estimate'); return false;"><?php echo _l('rm_estimates'); ?>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="#" data-cview="rel_type_invoice" onclick="dt_custom_view('rel_type_invoice','.table-reminder','rel_type_invoice'); return false;"><?php echo _l('rm_invoices'); ?>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="#" data-cview="rel_type_credit_note" onclick="dt_custom_view('rel_type_credit_note','.table-reminder','rel_type_credit_note'); return false;"><?php echo _l('rm_credit_notes'); ?>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="#" data-cview="rel_type_tickets" onclick="dt_custom_view('rel_type_tickets','.table-reminder','rel_type_tickets'); return false;"><?php echo _l('rm_tickets'); ?>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </li>
                                                    <?php if(count($created_ids) > 0 && is_admin()){ ?>
                                                        <div class="clearfix"></div>
                                                        <li class="divider"></li>
                                                        <li class="dropdown-submenu pull-left">
                                                            <a href="#" tabindex="-1"><?php echo _l('reminder_created_by_th'); ?></a>
                                                            <ul class="dropdown-menu dropdown-menu-left">
                                                                <?php foreach($created_ids as $id){ ?>
                                                                    <li>
                                                                        <a href="#" data-cview="created_by_<?php echo $id['by_staff']; ?>" onclick="dt_custom_view('created_by_<?php echo $id['by_staff']; ?>','.table-reminder','created_by_<?php echo $id['by_staff']; ?>'); return false;"><?php echo $id['full_name']; ?>
                                                                        </a>
                                                                    </li>
                                                                <?php } ?>
                                                            </ul>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                            <a href="#" class="btn btn-default btn-with-tooltip toggle-small-view hidden-xs" onclick="reminder_toggle_small_view('.table-reminder','#reminder'); return false;" data-toggle="tooltip" title="<?php echo _l('invoices_toggle_table_tooltip'); ?>"><i class="fa fa-angle-double-left"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12" id="small-table">
                            <div class="panel_s">
                                <div class="panel-body">
                                    <div class="panel-table-full tw-mt-10">
                                        <?php $this->load->view('financeiro/bancos/table_html'); ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 small-table-right-col">
                            <div id="reminder" class="hide">
                            </div>
                        </div>
                    </div>
                </div>

        </div>
    </div>
</div>

<?php init_tail(); ?>
</body>
</html>
