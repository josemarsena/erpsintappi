<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="<?php if (!isset($fatura) || (isset($fatura) && count($invoices_to_merge) == 0 && (isset($fatura) && !isset($invoice_from_project) && count($expenses_to_bill) == 0 || $fatura->status == Faturas_model::STATUS_CANCELLED))) {
    echo ' hide';
} ?>" id="invoice_top_info">
    <div class="alert alert-info">
        <div class="row">
            <div id="merge" class="col-md-6">
                <?php
              if (isset($fatura)) {
                  $this->load->view('admin/invoices/merge_invoice', ['invoices_to_merge' => $invoices_to_merge]);
              }
            ?>
            </div>
            <!--  When invoicing from project area the expenses are not visible here because you can select to bill expenses while trying to invoice project -->
            <?php if (!isset($invoice_from_project)) { ?>
            <div id="expenses_to_bill" class="col-md-6">
                <?php if (isset($fatura) && $fatura->status != Faturas_model::STATUS_CANCELLED) {
                $this->load->view('admin/invoices/bill_expenses', ['expenses_to_bill' => $expenses_to_bill]);
            } ?>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<div class="panel_s invoice accounting-template">
    <div class="additional"></div>
    <div class="panel-body">
        <?php hooks()->do_action('before_render_invoice_template', $fatura ?? null); ?>
        <?php if (isset($fatura)) {
                echo form_hidden('merge_current_invoice', $fatura->id);
            } ?>
        <div class="row">
            <div class="col-md-6">
                    <label for="id_fornecedor"><?php echo 'Fornecedor'; ?></label>
                    <select name="id_fornecedor" id="id_fornecedor" class="selectpicker" onchange="estimate_by_vendor(this); return false;" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>" >
                        <option value=""></option>
                        <?php foreach($fornecedores as $s) { ?>
                            <option value="<?php echo html_entity_decode($s['userid']); ?>" <?php if(isset($fatura) && $fatura->id_fornecedor == $s['userid']){ echo 'selected'; }else{ if(isset($ven) && $ven == $s['userid']){ echo 'selected';} } ?>><?php echo html_entity_decode($s['company']); ?></option>
                        <?php } ?>
                    </select>
                <?php
                if (!isset($fatura)) { ?>
                    <div class="form-group select-placeholder projects-wrapper<?php if ((!isset($fatura)) || (isset($fatura) && !customer_has_projects($fatura->id_fornecedor))) {
                    echo (isset($id_fornecedor) && (!isset($id_projeto) || !$id_projeto)) ?  ' hide' : '';
                } ?>">
                    <label for="project_id"><?php echo _l('project'); ?></label>
                    <div id="project_ajax_search_wrapper">
                        <select name="id_projeto" id="id_projeto" class="projects ajax-search" data-live-search="true"
                            data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <?php
                    if (!isset($id_projeto)) {
                        $id_projeto = '';
                    }
                    if (isset($fatura) && $fatura->id_projeto) {
                        $id_projeto = $fatura->id_projeto;
                    }
                    if ($id_projeto) {
                        echo '<option value="' . $id_projeto . '" selected>' . get_project_name_by_id($id_projeto) . '</option>';
                    }
                   ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                <?php
               $prox_numero_fatura = get_option('proxima_fatura_a_pagar');
               $formato             = get_option('formato_fatura_a_pagar');

               if (isset($fatura)) {
                   $formato = $fatura->formatonumero;
               }

               $prefixo = get_option('prefixo_fatura');

               if ($formato == 1) {
                   $__numero = $prox_numero_fatura;
                   if (isset($fatura)) {
                       $__numero = $fatura->numero;
                       $prefixo   = '<span id="prefix">' . $fatura->prefixo . '</span>';
                   }
               } elseif ($formato == 2) {
                   if (isset($fatura)) {
                       $__numero = $fatura->numero;
                       $prefixo   = $fatura->prefixo;
                       $prefixo   = '<span id="prefix">' . $prefixo . '</span><span id="prefix_year">' . date('Y', strtotime($fatura->data)) . '</span>/';
                   } else {
                       $__numero = $prox_numero_fatura;
                       $prefixo   = $prefixo . '<span id="prefix_year">' . date('Y') . '</span>/';
                   }
               } elseif ($formato == 3) {
                   if (isset($fatura)) {
                       $yy       = date('y', strtotime($fatura->data));
                       $__numero = $fatura->numero;
                       $prefixo   = '<span id="prefix">' . $fatura->prefixo . '</span>';
                   } else {
                       $yy       = date('y');
                       $__numero = $prox_numero_fatura;
                   }
               } elseif ($formato == 4) {
                   if (isset($fatura)) {
                       $yyyy     = date('Y', strtotime($fatura->data));
                       $mm       = date('m', strtotime($fatura->data));
                       $__numero = $fatura->numero;
                       $prefixo   = '<span id="prefix">' . $fatura->prefixo . '</span>';
                   } else {
                       $yyyy     = date('Y');
                       $mm       = date('m');
                       $__numero = $prox_numero_fatura;
                   }
               }

               $_is_draft            = (isset($fatura) && $fatura->status == Invoices_model::STATUS_DRAFT) ? true : false;
               $_invoice_number      = str_pad($__numero, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
               $isedit               = isset($fatura) ? 'true' : 'false';
               $data_original_number = isset($fatura) ? $fatura->numero : 'false';

               ?>
                <div class="form-group">
                    <label for="numero">
                        <?php echo _l('invoice_add_edit_number'); ?>
                        <i class="fa-regular fa-circle-question" data-toggle="tooltip"
                            data-title="<?php echo _l('invoice_number_not_applied_on_draft') ?>"
                            data-placement="top"></i>
                    </label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <?php if (isset($fatura)) { ?>
                            <a href="#" onclick="return false;" data-toggle="popover"
                                data-container='._transaction_form' data-html="true"
                                data-content="<label class='control-label'><?php echo _l('settings_sales_invoice_prefix'); ?></label><div class='input-group'><input name='s_prefix' type='text' class='form-control' value='<?php echo $fatura->prefixo; ?>'></div><button type='button' onclick='save_sales_number_settings(this); return false;' data-url='<?php echo admin_url('invoices/update_number_settings/' . $fatura->id); ?>' class='btn btn-primary btn-block mtop15'><?php echo _l('submit'); ?></button>">
                                <i class="fa fa-cog"></i>
                            </a>
                            <?php }
                    echo $prefixo;
                  ?>
                        </span>
                        <input type="text" name="numero" class="form-control"
                            value="<?php echo ($_is_draft) ? 'DRAFT' : $_invoice_number; ?>"
                            data-isedit="<?php echo $isedit; ?>"
                            data-original-number="<?php echo $data_original_number; ?>"
                            <?php echo ($_is_draft) ? 'disabled' : '' ?>>
                        <?php if ($formato == 3) { ?>
                        <span class="input-group-addon">
                            <span id="prefix_year" class="format-n-yy"><?php echo $yy; ?></span>
                        </span>
                        <?php } elseif ($formato == 4) { ?>
                        <span class="input-group-addon">
                            <span id="prefix_month" class="format-mm-yyyy"><?php echo $mm; ?></span>
                            /
                            <span id="prefix_year" class="format-mm-yyyy"><?php echo $yyyy; ?></span>
                        </span>
                        <?php } ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <?php $value = (isset($fatura) ? _d($fatura->data) : _d(date('Y-m-d')));
                  $date_attrs        = [];
                  if (isset($fatura) && $fatura->recorrente > 0 && $fatura->ultima_data_recorrencia != null) {
                      $date_attrs['disabled'] = true;
                  }
                  ?>
                        <?php echo render_date_input('data', 'invoice_add_edit_date', $value, $date_attrs); ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                  $value = '';
                  if (isset($fatura)) {
                      $value = _d($fatura->datavencimento);
                  } else {
                      if (get_option('invoice_due_after') != 0) {
                          $value = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
                      }
                  }
                   ?>
                        <?php echo render_date_input('datavencimento', 'invoice_add_edit_duedate', $value); ?>
                    </div>
                </div>
                <?php if (is_invoices_overdue_reminders_enabled()) { ?>
                <div class="form-group">
                    <div class="checkbox checkbox-danger">
                        <input type="checkbox" <?php if (isset($fatura) && $fatura->cancelar_lembretes_atraso == 1) {
                       echo 'checked';
                   } ?> id="cancelar_lembretes_atraso" name="cancelar_lembretes_atraso">
                        <label
                            for="cancelar_lembretes_atraso"><?php echo _l('cancel_overdue_reminders_invoice') ?></label>
                    </div>
                </div>
                <?php } ?>
                <div class="form-group">
                    <div class="checkbox checkbox-danger">
                        <input type="checkbox"  id="marca_provisao" name="marca_provisao">
                        <label for="marca_provisao"><?php echo "É Provisão?" ?></label>
                    </div>
                </div>
                <?php $rel_id = (isset($fatura) ? $fatura->id : false); ?>
                <?php
                  if (isset($custom_fields_rel_transfer)) {
                      $rel_id = $custom_fields_rel_transfer;
                  }
               ?>
                <?php echo render_custom_fields('invoice', $rel_id); ?>
            </div>
            <div class="col-md-6">
                <div class="tw-ml-3">
                    <div class="form-group">
                        <label for="tags" class="control-label"><i class="fa fa-tag" aria-hidden="true"></i>
                            <?php echo _l('tags'); ?></label>
                        <input type="text" class="tagsinput" id="tags" name="tags"
                            value="<?php echo(isset($fatura) ? prep_tags_input(get_tags_in($fatura->id, 'invoice')) : ''); ?>"
                            data-role="tagsinput">
                    </div>
                    <div class="form-group mbot15<?= count($payment_modes) > 0 ? ' select-placeholder' : ''; ?>">
                        <label for="modos_pagamento_permitidos"
                            class="control-label"><?php echo _l('invoice_add_edit_allowed_payment_modes'); ?></label>
                        <br />
                        <?php if (count($payment_modes) > 0) { ?>
                        <select class="selectpicker"
                            data-toggle="<?php echo $this->input->get('allowed_payment_modes'); ?>"
                            name="allowed_payment_modes[]" data-actions-box="true" multiple="true" data-width="100%"
                            data-title="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <?php foreach ($payment_modes as $mode) {
                   $selected = '';
                   if (isset($fatura)) {
                       if ($fatura->modos_pagamento_permitidos) {
                           $inv_modes = unserialize($fatura->modos_pagamento_permitidos);
                           if (is_array($inv_modes)) {
                               foreach ($inv_modes as $_allowed_payment_mode) {
                                   if ($_allowed_payment_mode == $mode['id']) {
                                       $selected = ' selected';
                                   }
                               }
                           }
                       }
                   } else {
                       if ($mode['selected_by_default'] == 1) {
                           $selected = ' selected';
                       }
                   } ?>
                            <option value="<?php echo $mode['id']; ?>" <?php echo $selected; ?>>
                                <?php echo $mode['name']; ?></option>
                            <?php
               } ?>
                        </select>
                        <?php } else { ?>
                        <p class="tw-text-neutral-500">
                            <?php echo _l('invoice_add_edit_no_payment_modes_found'); ?>
                        </p>
                        <a class="btn btn-primary btn-sm" href="<?php echo admin_url('paymentmodes'); ?>">
                            <?php echo _l('new_payment_mode'); ?>
                        </a>
                        <?php } ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?php
                        $currency_attr = ['disabled' => true, 'data-show-subtext' => true];
                        $currency_attr = apply_filters_deprecated('invoice_currency_disabled', [$currency_attr], '2.3.0', 'invoice_currency_attributes');

                        foreach ($moedas as $currency) {
                            if ($currency['isdefault'] == 1) {
                                $currency_attr['data-base'] = $currency['id'];
                            }
                            if (isset($fatura)) {
                                if ($currency['id'] == $fatura->currency) {
                                    $selected = $currency['id'];
                                }
                            } else {
                                if ($currency['isdefault'] == 1) {
                                    $selected = $currency['id'];
                                }
                            }
                        }
                        $currency_attr = hooks()->apply_filters('invoice_currency_attributes', $currency_attr);
                        ?>
                            <?php echo render_select('moeda', $moedas, ['id', 'name', 'symbol'], 'invoice_add_edit_currency', $selected, $currency_attr); ?>
                        </div>
                        <div class="col-md-6">
                            <?php
                                $selected = !isset($fatura) && get_option('automatically_set_logged_in_staff_sales_agent') == '1' ? get_staff_user_id() : '';
                                foreach ($equipe as $member) {
                                    if (isset($fatura)) {
                                        if ($fatura->sale_agent == $member['staffid']) {
                                            $selected = $member['staffid'];
                                        }
                                    }
                                }
                                echo render_select('id_comprador', $equipe, ['staffid', ['firstname', 'lastname']], 'Responsável/Comprador', $selected);
                            ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group select-placeholder"
                                <?php if (isset($fatura) && !empty($fatura->e_recorrente_de)) { ?>
                                data-toggle="tooltip"
                                data-title="<?php echo _l('create_recurring_from_child_error_message', [_l('invoice_lowercase'), _l('invoice_lowercase'), _l('invoice_lowercase')]); ?>"
                                <?php } ?>>
                                <label for="recurring" class="control-label">
                                    <?php echo _l('invoice_add_edit_recurring'); ?>
                                </label>
                                <select class="selectpicker" data-width="100%" name="recorrente"
                                    data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" <?php
                        // The problem is that this invoice was generated from previous recurring invoice
                        // Then this new invoice you set it as recurring but the next invoice date was still taken from the previous invoice.
                        if (isset($fatura) && !empty($fatura->e_recorrente_de)) {
                            echo 'disabled';
                        } ?>>
                                    <?php for ($i = 0; $i <= 12; $i++) { ?>
                                    <?php
                              $selected = '';
                              if (isset($fatura)) {
                                  if ($fatura->custom_recorrencia == 0) {
                                      if ($fatura->recorrente == $i) {
                                          $selected = 'selected';
                                      }
                                  }
                              }
                              if ($i == 0) {
                                  $reccuring_string = _l('invoice_add_edit_recurring_no');
                              } elseif ($i == 1) {
                                  $reccuring_string = _l('invoice_add_edit_recurring_month', $i);
                              } else {
                                  $reccuring_string = _l('invoice_add_edit_recurring_months', $i);
                              }
                              ?>
                                    <option value="<?php echo $i; ?>" <?php echo $selected; ?>>
                                        <?php echo $reccuring_string; ?></option>
                                    <?php } ?>
                                    <option value="custom" <?php if (isset($fatura) && $fatura->recorrente != 0 && $fatura->custom_recorrencia == 1) {
                                  echo 'selected';
                              } ?>><?php echo _l('recurring_custom'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group select-placeholder">
                                <label for="tipo_descto"
                                    class="control-label"><?php echo _l('discount_type'); ?></label>
                                <select name="tipo_descto" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                    <option value="" selected><?php echo _l('no_discount'); ?></option>
                                    <option value="before_tax" <?php
                              if (isset($fatura)) {
                                  if ($fatura->tipo_descto == 'before_tax') {
                                      echo 'selected';
                                  }
                              } ?>><?php echo _l('discount_type_before_tax'); ?></option>
                                    <option value="after_tax" <?php if (isset($fatura)) {
                                  if ($fatura->tipo_descto == 'after_tax') {
                                      echo 'selected';
                                  }
                              } ?>><?php echo _l('discount_type_after_tax'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="recurring_custom <?php if ((isset($fatura) && $fatura->custom_recorrencia != 1) || (!isset($fatura))) {
                                  echo 'hide';
                              } ?>">
                            <div class="col-md-6">
                                <?php $value = (isset($fatura) && $fatura->custom_recorrencia == 1 ? $fatura->recorrente : 1); ?>
                                <?php echo render_input('repeat_every_custom', '', $value, 'number', ['min' => 1]); ?>
                            </div>
                            <div class="col-md-6">
                                <select name="repeat_type_custom" id="repeat_type_custom" class="selectpicker"
                                    data-width="100%"
                                    data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                    <option value="day" <?php if (isset($fatura) && $fatura->custom_recorrencia == 1 && $fatura->tipo_recorrencia == 'day') {
                                  echo 'selected';
                              } ?>><?php echo _l('invoice_recurring_days'); ?></option>
                                    <option value="week" <?php if (isset($fatura) && $fatura->custom_recorrencia == 1 && $fatura->tipo_recorrencia == 'week') {
                                  echo 'selected';
                              } ?>><?php echo _l('invoice_recurring_weeks'); ?></option>
                                    <option value="month" <?php if (isset($fatura) && $fatura->custom_recorrencia == 1 && $fatura->tipo_recorrencia == 'month') {
                                  echo 'selected';
                              } ?>><?php echo _l('invoice_recurring_months'); ?></option>
                                    <option value="year" <?php if (isset($fatura) && $fatura->custom_recorrencia == 1 && $fatura->tipo_recorrencia == 'year') {
                                  echo 'selected';
                              } ?>><?php echo _l('invoice_recurring_years'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div id="cycles_wrapper" class="<?php if (!isset($fatura) || (isset($fatura) && $fatura->recorrente == 0)) {
                                  echo ' hide';
                              }?>">
                            <div class="col-md-12">
                                <?php $value = (isset($fatura) ? $fatura->ciclos : 0); ?>
                                <div class="form-group recurring-cycles">
                                    <label for="cycles"><?php echo _l('recurring_total_cycles'); ?>
                                        <?php if (isset($fatura) && $fatura->total_ciclos > 0) {
                                  echo '<small>' . _l('cycles_passed', $fatura->total_ciclos) . '</small>';
                              }
                            ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" <?php if ($value == 0) {
                                echo ' disabled';
                            } ?> name="cycles" id="cycles" value="<?php echo $value; ?>" <?php if (isset($fatura) && $fatura->total_ciclos > 0) {
                                echo 'min="' . ($fatura->total_ciclos) . '"';
                            } ?>>
                                        <div class="input-group-addon">
                                            <div class="checkbox">
                                                <input type="checkbox" <?php if ($value == 0) {
                                echo ' checked';
                            } ?> id="unlimited_cycles">
                                                <label
                                                    for="unlimited_cycles"><?php echo _l('cycles_infinity'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $value = (isset($fatura) ? $fatura->adminnote : ''); ?>
                    <?php echo render_textarea('nota_admin', 'invoice_add_edit_admin_note', $value); ?>

                </div>
            </div>
        </div>
    </div>

    <hr class="hr-panel-separator" />

    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <?php $this->load->view('admin/invoice_items/item_select'); ?>
            </div>
            <?php if (!isset($invoice_from_project) && isset($billable_tasks)) { ?>
            <div class="col-md-3">
                <div class="form-group select-placeholder input-group-select form-group-select-task_select popover-250">
                    <div class="input-group input-group-select">
                        <select name="task_select" data-live-search="true" id="task_select"
                            class="selectpicker no-margin _select_input_group" data-width="100%"
                            data-none-selected-text="<?php echo _l('bill_tasks'); ?>">
                            <option value=""></option>
                            <?php foreach ($billable_tasks as $task_billable) { ?>
                            <option value="<?php echo $task_billable['id']; ?>"
                                <?php if ($task_billable['started_timers'] == true) { ?>disabled class="text-danger"
                                data-subtext="<?php echo _l('invoice_task_billable_timers_found'); ?>" <?php } else {
                                $task_rel_data  = get_relation_data($task_billable['rel_type'], $task_billable['rel_id']);
                                $task_rel_value = get_relation_values($task_rel_data, $task_billable['rel_type']); ?>
                                data-subtext="<?php echo $task_billable['rel_type'] == 'project' ? '' : $task_rel_value['name']; ?>" <?php
                            } ?>><?php echo $task_billable['name']; ?></option>
                            <?php } ?>
                        </select>
                        <div class="input-group-addon input-group-addon-bill-tasks-help">
                            <?php
                    if (isset($fatura) && !empty($fatura->project_id)) {
                        $help_text = _l('showing_billable_tasks_from_project') . ' ' . get_project_name_by_id($fatura->project_id);
                    } else {
                        $help_text = _l('invoice_task_item_project_tasks_not_included');
                    }
                                echo '<span class="pointer popover-invoker" data-container=".form-group-select-task_select"
                      data-trigger="click" data-placement="top" data-toggle="popover" data-content="' . $help_text . '">
                      <i class="fa-regular fa-circle-question"></i></span>'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                            } ?>
            <div class="col-md-<?php if (!isset($invoice_from_project)) {
                                echo 5;
                            } else {
                                echo 8;
                            } ?> text-right show_quantity_as_wrapper">
                <div class="mtop10">
                    <span><?php echo _l('show_quantity_as'); ?> </span>
                    <div class="radio radio-primary radio-inline">
                        <input type="radio" value="1" id="sq_1" name="show_quantity_as"
                            data-text="<?php echo _l('invoice_table_quantity_heading'); ?>" <?php if (isset($fatura) && $fatura->show_quantity_as == 1) {
                                echo 'checked';
                            } elseif (!isset($hours_quantity) && !isset($qty_hrs_quantity)) {
                                echo'checked';
                            } ?>>
                        <label for="sq_1"><?php echo _l('quantity_as_qty'); ?></label>
                    </div>
                    <div class="radio radio-primary radio-inline">
                        <input type="radio" value="2" id="sq_2" name="show_quantity_as"
                            data-text="<?php echo _l('invoice_table_hours_heading'); ?>" <?php if (isset($fatura) && $fatura->show_quantity_as == 2 || isset($hours_quantity)) {
                                echo 'checked';
                            } ?>>
                        <label for="sq_2"><?php echo _l('quantity_as_hours'); ?></label>
                    </div>
                    <div class="radio radio-primary radio-inline">
                        <input type="radio" value="3" id="sq_3" name="show_quantity_as"
                            data-text="<?php echo _l('invoice_table_quantity_heading'); ?>/<?php echo _l('invoice_table_hours_heading'); ?>" <?php if (isset($fatura) && $invoice->show_quantity_as == 3 || isset($qty_hrs_quantity)) {
                                echo 'checked';
                            } ?>>
                        <label
                            for="sq_3"><?php echo _l('invoice_table_quantity_heading'); ?>/<?php echo _l('invoice_table_hours_heading'); ?></label>
                    </div>
                </div>
            </div>
        </div>
        <?php if (isset($invoice_from_project)) {
                                echo '<hr class="no-mtop" />';
                            } ?>
        <div class="table-responsive s_table">
            <table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
                <thead>
                    <tr>
                        <th></th>
                        <th width="20%" align="left"><i class="fa-solid fa-circle-exclamation tw-mr-1"
                                aria-hidden="true" data-toggle="tooltip"
                                data-title="<?php echo _l('item_description_new_lines_notice'); ?>"></i>
                            <?php echo _l('invoice_table_item_heading'); ?></th>
                        <th width="25%" align="left"><?php echo _l('invoice_table_item_description'); ?></th>
                        <?php
                  $custom_fields = get_custom_fields('items');
                  foreach ($custom_fields as $cf) {
                      echo '<th width="15%" align="left" class="custom_field">' . $cf['name'] . '</th>';
                  }
                     $qty_heading = _l('invoice_table_quantity_heading');
                     if (isset($fatura) && $fatura->show_quantity_as == 2 || isset($hours_quantity)) {
                         $qty_heading = _l('invoice_table_hours_heading');
                     } elseif (isset($fatura) && $fatura->show_quantity_as == 3) {
                         $qty_heading = _l('invoice_table_quantity_heading') . '/' . _l('invoice_table_hours_heading');
                     }
                     ?>
                        <th width="10%" align="right" class="qty"><?php echo $qty_heading; ?></th>
                        <th width="15%" align="right"><?php echo _l('invoice_table_rate_heading'); ?></th>
                        <th width="20%" align="right"><?php echo _l('invoice_table_tax_heading'); ?></th>
                        <th width="10%" align="right"><?php echo _l('invoice_table_amount_heading'); ?></th>
                        <th align="center"><i class="fa fa-cog"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="main">
                        <td></td>
                        <td>
                            <textarea name="description" class="form-control" rows="4"
                                placeholder="<?php echo _l('item_description_placeholder'); ?>"></textarea>
                        </td>
                        <td>
                            <textarea name="long_description" rows="4" class="form-control"
                                placeholder="<?php echo _l('item_long_description_placeholder'); ?>"></textarea>
                        </td>
                        <?php echo render_custom_fields_items_table_add_edit_preview(); ?>
                        <td>
                            <input type="number" name="quantity" min="0" value="1" class="form-control"
                                placeholder="<?php echo _l('item_quantity_placeholder'); ?>">
                            <input type="text" placeholder="<?php echo _l('unit'); ?>" data-toggle="tooltip"
                                data-title="e.q kg, lots, packs" name="unit"
                                class="form-control input-transparent text-right">
                        </td>
                        <td>
                            <input type="number" name="rate" class="form-control"
                                placeholder="<?php echo _l('item_rate_placeholder'); ?>">
                        </td>
                        <td>
                            <?php
                        $default_tax = unserialize(get_option('default_tax'));
                        $select      = '<select class="selectpicker display-block tax main-tax" data-width="100%" name="taxname" multiple data-none-selected-text="' . _l('no_tax') . '">';
                      //  $select .= '<option value=""'.(count($default_tax) == 0 ? ' selected' : '').'>'._l('no_tax').'</option>';
                        foreach ($impostos as $tax) {
                            $selected = '';
                            if (is_array($default_tax)) {
                                if (in_array($tax['name'] . '|' . $tax['taxrate'], $default_tax)) {
                                    $selected = ' selected ';
                                }
                            }
                            $select .= '<option value="' . $tax['name'] . '|' . $tax['taxrate'] . '"' . $selected . 'data-taxrate="' . $tax['taxrate'] . '" data-taxname="' . $tax['name'] . '" data-subtext="' . $tax['name'] . '">' . $tax['taxrate'] . '%</option>';
                        }
                        $select .= '</select>';
                        echo $select;
                        ?>
                        </td>
                        <td></td>
                        <td>
                            <?php
                        $new_item = 'undefined';
                        if (isset($fatura)) {
                            $new_item = true;
                        }
                        ?>
                            <button type="button"
                                onclick="add_item_to_table('undefined','undefined',<?php echo $new_item; ?>); return false;"
                                class="btn pull-right btn-primary"><i class="fa fa-check"></i></button>
                        </td>
                    </tr>
                    <?php if (isset($fatura) || isset($add_items)) {
                            $i               = 1;
                            $items_indicator = 'newitems';
                            if (isset($fatura)) {
                                $add_items       = $fatura->items;
                                $items_indicator = 'items';
                            }
                            foreach ($add_items as $item) {
                                $manual    = false;
                                $table_row = '<tr class="sortable item">';
                                $table_row .= '<td class="dragger">';
                                if (!is_numeric($item['qty'])) {
                                    $item['qty'] = 1;
                                }
                                $invoice_item_taxes = get_invoice_item_taxes($item['id']);
                                // passed like string
                                if ($item['id'] == 0) {
                                    $invoice_item_taxes = $item['taxname'];
                                    $manual             = true;
                                }
                                $table_row .= form_hidden('' . $items_indicator . '[' . $i . '][itemid]', $item['id']);
                                $amount = $item['rate'] * $item['qty'];
                                $amount = app_format_number($amount);
                                // order input
                                $table_row .= '<input type="hidden" class="order" name="' . $items_indicator . '[' . $i . '][order]">';
                                $table_row .= '</td>';
                                $table_row .= '<td class="bold description"><textarea name="' . $items_indicator . '[' . $i . '][description]" class="form-control" rows="5">' . clear_textarea_breaks($item['description']) . '</textarea></td>';
                                $table_row .= '<td><textarea name="' . $items_indicator . '[' . $i . '][long_description]" class="form-control" rows="5">' . clear_textarea_breaks($item['long_description']) . '</textarea></td>';

                                $table_row .= render_custom_fields_items_table_in($item, $items_indicator . '[' . $i . ']');

                                $table_row .= '<td><input type="number" min="0" onblur="calculate_total();" onchange="calculate_total();" data-quantity name="' . $items_indicator . '[' . $i . '][qty]" value="' . $item['qty'] . '" class="form-control">';

                                $unit_placeholder = '';
                                if (!$item['unit']) {
                                    $unit_placeholder = _l('unit');
                                    $item['unit']     = '';
                                }

                                $table_row .= '<input type="text" placeholder="' . $unit_placeholder . '" name="' . $items_indicator . '[' . $i . '][unit]" class="form-control input-transparent text-right" value="' . $item['unit'] . '">';

                                $table_row .= '</td>';
                                $table_row .= '<td class="rate"><input type="number" data-toggle="tooltip" title="' . _l('numbers_not_formatted_while_editing') . '" onblur="calculate_total();" onchange="calculate_total();" name="' . $items_indicator . '[' . $i . '][rate]" value="' . $item['rate'] . '" class="form-control"></td>';
                                $table_row .= '<td class="taxrate">' . $this->misc_model->get_taxes_dropdown_template('' . $items_indicator . '[' . $i . '][taxname][]', $invoice_item_taxes, 'invoice', $item['id'], true, $manual) . '</td>';
                                $table_row .= '<td class="amount" align="right">' . $amount . '</td>';
                                $table_row .= '<td><a href="#" class="btn btn-danger pull-left" onclick="delete_item(this,' . $item['id'] . '); return false;"><i class="fa fa-times"></i></a></td>';
                                if (isset($item['task_id'])) {
                                    if (!is_array($item['task_id'])) {
                                        $table_row .= form_hidden('billed_tasks[' . $i . '][]', $item['task_id']);
                                    } else {
                                        foreach ($item['task_id'] as $task_id) {
                                            $table_row .= form_hidden('billed_tasks[' . $i . '][]', $task_id);
                                        }
                                    }
                                } elseif (isset($item['expense_id'])) {
                                    $table_row .= form_hidden('billed_expenses[' . $i . '][]', $item['expense_id']);
                                }
                                $table_row .= '</tr>';
                                echo $table_row;
                                $i++;
                            }
                        }
                  ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-8 col-md-offset-4">
            <table class="table text-right">
                <tbody>
                    <tr id="subtotal">
                        <td>
                            <span class="bold tw-text-neutral-700"><?php echo _l('invoice_subtotal'); ?> :</span>
                        </td>
                        <td class="subtotal">
                        </td>
                    </tr>
                    <tr id="discount_area">
                        <td>
                            <div class="row">
                                <div class="col-md-7">
                                    <span class="bold tw-text-neutral-700">
                                        <?php echo _l('invoice_discount'); ?>
                                    </span>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group" id="discount-total">

                                        <input type="number"
                                            value="<?php echo(isset($fatura) ? $fatura->discount_percent : 0); ?>"
                                            class="form-control pull-left input-discount-percent<?php if (isset($fatura) && !is_sale_discount($fatura, 'percent') && is_sale_discount_applied($fatura)) {
                      echo ' hide';
                  } ?>" min="0" max="100" name="discount_percent">

                                        <input type="number" data-toggle="tooltip"
                                            data-title="<?php echo _l('numbers_not_formatted_while_editing'); ?>"
                                            value="<?php echo(isset($fatura) ? $fatura->discount_total : 0); ?>"
                                            class="form-control pull-left input-discount-fixed<?php if (!isset($fatura) || (isset($fatura) && !is_sale_discount($fatura, 'fixed'))) {
                      echo ' hide';
                  } ?>" min="0" name="discount_total">

                                        <div class="input-group-addon">
                                            <div class="dropdown">
                                                <a class="dropdown-toggle" href="#" id="dropdown_menu_tax_total_type"
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                    <span class="discount-total-type-selected">
                                                        <?php if (!isset($fatura) || isset($fatura) && (is_sale_discount($fatura, 'percent') || !is_sale_discount_applied($fatura))) {
                      echo '%';
                  } else {
                      echo _l('discount_fixed_amount');
                  }
                                        ?>
                                                    </span>
                                                    <span class="caret"></span>
                                                </a>
                                                <ul class="dropdown-menu" id="discount-total-type-dropdown"
                                                    aria-labelledby="dropdown_menu_tax_total_type">
                                                    <li>
                                                        <a href="#" class="discount-total-type discount-type-percent<?php if (!isset($fatura) || (isset($fatura) && is_sale_discount($fatura, 'percent')) || (isset($fatura) && !is_sale_discount_applied($fatura))) {
                                            echo ' selected';
                                        } ?>">%</a>
                                                    </li>
                                                    <li><a href="#" class="discount-total-type discount-type-fixed<?php if (isset($fatura) && is_sale_discount($fatura, 'fixed')) {
                                            echo ' selected';
                                        } ?>">
                                                            <?php echo _l('discount_fixed_amount'); ?>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="discount-total"></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="row">
                                <div class="col-md-7">
                                    <span
                                        class="bold tw-text-neutral-700"><?php echo _l('invoice_adjustment'); ?></span>
                                </div>
                                <div class="col-md-5">
                                    <input type="number" data-toggle="tooltip"
                                        data-title="<?php echo _l('numbers_not_formatted_while_editing'); ?>" value="<?php if (isset($fatura)) {
                                            echo $fatura->adjustment;
                                        } else {
                                            echo 0;
                                        } ?>" class="form-control pull-left" name="adjustment">
                                </div>
                            </div>
                        </td>
                        <td class="adjustment"></td>
                    </tr>
                    <tr>
                        <td><span class="bold tw-text-neutral-700"><?php echo _l('invoice_total'); ?> :</span>
                        </td>
                        <td class="total">
                        </td>
                    </tr>
                    <?php hooks()->do_action('after_admin_invoice_form_total_field', $fatura ?? null); ?>
                </tbody>
            </table>

        </div>


        <div id="removed-items"></div>
        <div id="billed-tasks"></div>
        <div id="billed-expenses"></div>
        <?php echo form_hidden('task_id'); ?>
        <?php echo form_hidden('expense_id'); ?>

    </div>

    <hr class="hr-panel-separator" />

    <div class="panel-body">
        <?php $value = (isset($fatura) ? $fatura->clientnote : get_option('predefined_clientnote_invoice')); ?>
        <?php echo render_textarea('clientnote', 'invoice_add_edit_client_note', $value); ?>
        <?php $value = (isset($fatura) ? $fatura->terms : get_option('predefined_terms_invoice')); ?>
        <?php echo render_textarea('terms', 'terms_and_conditions', $value, [], [], 'mtop15'); ?>
    </div>

    <?php hooks()->do_action('after_render_invoice_template', isset($fatura) ? $fatura : false); ?>
</div>

<div class="btn-bottom-pusher"></div>
<div class="btn-bottom-toolbar text-right">
    <?php if (!isset($fatura)) { ?>
    <button class="btn-tr btn btn-default mright5 text-right invoice-form-submit save-as-draft transaction-submit">
        <?php echo _l('save_as_draft'); ?>
    </button>
    <?php } ?>
    <div class="btn-group dropup">
        <button type="button"
            class="btn-tr btn btn-primary invoice-form-submit transaction-submit"><?php echo _l('submit'); ?></button>
        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right width200">
            <li>
                <a href="#" class="invoice-form-submit save-and-send transaction-submit">
                    <?php echo _l('save_and_send'); ?>
                </a>
            </li>
            <?php if (!isset($fatura)) { ?>
            <li>
                <a href="#" class="invoice-form-submit save-and-send-later transaction-submit">
                    <?php echo _l('save_and_send_later'); ?>
                </a>
            </li>
            <li>
                <a href="#" class="invoice-form-submit save-and-record-payment transaction-submit">
                    <?php echo _l('save_and_record_payment'); ?>
                </a>
            </li>
            <?php } ?>
        </ul>
    </div>
</div>
