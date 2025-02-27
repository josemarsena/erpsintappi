<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_open(admin_url('financeiro/registra_pagamento_a_receber'), ['id' => 'record_payment_form']); ?>
<div class="col-md-12 no-padding animated fadeIn">
    <div class="panel_s">
        <?php echo form_hidden('invoiceid', $invoice->id); ?>
        <div class="panel-body">
            <h4 class="tw-my-0 tw-font-semibold">
                <?php echo _l('record_payment_for_invoice'); ?> <?php echo format_invoice_number($invoice->id); ?>
            </h4>
            <hr class="hr-panel-separator" />
            <div class="row">
                <div class="col-md-6">
                       <?php echo render_date_input('date', 'record_payment_date', _d(date('Y-m-d'))); ?>
                    <div class="form-group">
                        <label for="paymentmode" class="control-label"><?php echo _l('payment_mode'); ?></label>
                        <select class="selectpicker" name="paymentmode" id="paymentmode" data-width="100%" onchange="verificaSelecao()"
                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <option value=""></option>
                            <?php foreach ($payment_modes as $mode) { ?>
                            <?php
                            if (is_payment_mode_allowed_for_invoice($mode['id'], $invoice->id))     {
                                $totalAllowed++; ?>
                            <option value="<?php echo $mode['id']; ?>"><?php echo $mode['name']; ?></option>
                            <?php
                            } ?>
                            <?php } ?>
                        </select>
                    </div>

                    <div id="contabanco">
                        <?php
                            // Se Pagamento é PIX, Débito, Transferencia, Boleto: Mostra Banco, Conta de Banco e Conta e Subconta Financeira
                            $selected = (isset($invoice) ? $invoice->conta_id : '');
                            echo render_select('contasbancarias', $contasbancarias, ['id', 'conta'], 'Selecione a Conta Bancária', $selected, []);?>
                    </div>

                    <div id="contacaixa">
                        <?php
                        // Se Pagamenbto é Dinheiro: Mostra Caixa e Conta e SubConta Financeria
                            echo render_select('contascaixa', $contascaixa, ['id', 'nome'], 'Conta Caixa', $selected, []);
                        ?>
                    </div>
                    <div id="contacartao">
                        <?php
                        // Se Pagamenbto é Dinheiro: Mostra Caixa e Conta e SubConta Financeria
                        echo render_select('contascaixa', $contascaixa, ['id', 'nome'], 'Cartão de Crédito', $selected, []);
                        ?>
                    </div>
                    <?php
                        // Se Pagamento é Cartao de Credito: Mostra a Conta de Cartao e Conta e Subconta Financeira
                        echo render_select('contascredito', $contascredito, ['id', 'nomeconta'], 'Conta Financeira', $selected, []);
                        echo render_select('subcontascredito', $subcontascredito, ['id', 'nomeconta'], 'SubConta Financeira', $selected, []);
                    ?>

                    <?php
                    $amount       = $invoice->total_left_to_pay;
                    $totalAllowed = 0;?>
                    <div class="col-md-4">
                        <?php
                        echo render_input('amount', 'record_payment_amount_received', $amount, 'number', ['max' => $amount,'font-weight' => 'bold']); ?>
                    </div>


                    <?php
                    if ($totalAllowed === 0) {
                        ?>
                    <div class="alert alert-info">
                        Allowed payment modes not found for this invoice.<br />
                        Click <a
                            href="<?php echo admin_url('invoices/invoice/' . $invoice->id . '?allowed_payment_modes=1'); ?>">here</a>
                        to edit the invoice and allow payment modes.
                    </div>
                    <?php
                    } ?>
                </div>
                <div class="col-md-6">
                    <?php echo render_input('transactionid', 'payment_transaction_id'); ?>
                    <div class="form-gruoup">
                        <label for="note" class="control-label"><?php echo _l('record_payment_leave_note'); ?></label>
                        <textarea name="note" class="form-control" rows="8"
                            placeholder="<?php echo _l('invoice_record_payment_note_placeholder'); ?>"
                            id="note"></textarea>
                    </div>
                </div>
                <div class="col-md-12 tw-mt-3">
                    <?php
                    $pr_template = is_email_template_active('invoice-payment-recorded');
                    $sms_trigger = is_sms_trigger_active(SMS_TRIGGER_PAYMENT_RECORDED);
                    if ($pr_template || $sms_trigger) { ?>
                    <div class="checkbox checkbox-primary mtop15">
                        <input type="checkbox" name="do_not_send_email_template" id="do_not_send_email_template">
                        <label for="do_not_send_email_template">
                            <?php
                            if ($pr_template) {
                                echo _l('do_not_send_invoice_payment_email_template_contact');
                                if ($sms_trigger) {
                                    echo '/';
                                }
                            }
                            if ($sms_trigger) {
                                echo 'SMS' . ' ' . _l('invoice_payment_recorded');
                            }
                            ?>
                        </label>
                    </div>
                    <?php } ?>
                    <div class="checkbox checkbox-primary mtop15 do_not_redirect hide">
                        <input type="checkbox" name="do_not_redirect" id="do_not_redirect" checked>
                        <label for="do_not_redirect"><?php echo _l('do_not_redirect_payment'); ?></label>
                    </div>
                </div>
            </div>
            <?php
            hooks()->do_action('after_admin_last_record_payment_form_field', $invoice);
            if ($payments) { ?>
            <div class="mtop25 inline-block full-width">
                <h5 class="bold"><?php echo _l('invoice_payments_received'); ?></h5>
                <?php include_once(APPPATH . 'views/admin/invoices/invoice_payments_table.php'); ?>
            </div>
            <?php } ?>

            <?php hooks()->do_action('before_admin_add_payment_form_submit', $invoice); ?>
        </div>

        <div class="panel-footer text-right">
            <a href="#" class="btn btn-danger"
                onclick="init_invoice(<?php echo $invoice->id; ?>); return false;"><?php echo _l('cancel'); ?></a>
            <button type="submit" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>"
                data-form="#record_payment_form" class="btn btn-success"><?php echo _l('submit'); ?></button>
        </div>
    </div>
</div>
<?php echo form_close(); ?>
<style>
    /* Esconde a div por padrão */
    #contabanco {
        display: none;
        padding: 10px;
        margin-top: 10px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;
    }
    /* Esconde a div por padrão */
    #contacaixa {
        display: none;
        padding: 10px;
        margin-top: 10px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;
    }
    /* Esconde a div por padrão */
    #contacartao {
        display: none;
        padding: 10px;
        margin-top: 10px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;
    }
</style>
<script>
    function verificaSelecao() {

        let opcao = document.getElementById("paymentmode").value;
        let divcontabanco = document.getElementById("contabanco");
        let divcontacaixa = document.getElementById("contacaixa");
        let divcontacartao = document.getElementById("contacartao");

        // Exibe a div apenas se "opcao2" for selecionada

        if (opcao === "1") {    // Bancos
            divcontabanco.style.display = "block";
        } else {
            divcontabanco.style.display = "none";
            divcontacaixa.style.display = "none";
            divcontacartao.style.display = "none";
        }
    }
$(function() {
    init_selectpicker();
    init_datepicker();
    appValidateForm($('#record_payment_form'), {
        amount: 'required',
        date: 'required',
        paymentmode: 'required'
    });
    var $sMode = $('select[name="paymentmode"]');
    var total_available_payment_modes = $sMode.find('option').length - 1;
    if (total_available_payment_modes == 1) {
        $sMode.selectpicker('val', $sMode.find('option').eq(1).attr('value'));
        $sMode.trigger('change');
    }



});
</script>
