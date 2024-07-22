<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            if (isset($contabancaria)) {
                echo form_hidden('is_edit', 'true');
            }
            ?>
            <?php echo form_open_multipart($this->uri->uri_string(), ['id' => 'contabancaria-form', 'class' => 'dropzone dropzone-manual']) ; ?>
            <div class="col-md-8">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <hr class="hr-panel-separator" />
                        <?php hooks()->do_action('before_contabancaria_form_name', isset($contabancaria) ? $contabancaria : null); ?>
                        <?php $selected = (isset($contabancaria) ? $contabancaria->banco_id : ''); ?>
                        <?php echo render_select('banco_id', $bancos, ['id', 'nomebanco'],'Nome do Banco', $selected); ?>
                        <div class="col-md-4">
                            <?php $value = (isset($contabancaria) ? $contabancaria->agencia : ''); ?>
                            <?php echo render_input('agencia', 'Nome da Agência', $value, 'text'); ?>
                        </div>
                        <div class="col-md-8">
                            <?php $value = (isset($contabancaria) ? $contabancaria->conta : ''); ?>
                            <?php echo render_input('conta', 'Nro. da Conta', $value, 'text'); ?>
                        </div>
                        <?php $value = (isset($contabancaria) ? $contabancaria->gerente : ''); ?>
                        <?php echo render_input('gerente', 'Gerente', $value, 'text'); ?>
                        <?php $value = (isset($contabancaria) ? $contabancaria->endereco : ''); ?>
                        <?php echo render_textarea('endereco', 'Endereço', $value, ['rows' => 3]); ?>
                        <?php $value = (isset($contabancaria) ? $contabancaria->telefone : ''); ?>
                        <?php echo render_input('telefone', 'Telefone', $value, 'text'); ?>
                        <?php $value = (isset($contabancaria) ? $contabancaria->saldoinicial : ''); ?>
                        <div class="col-md-6">
                            <?php echo render_input('saldoinicial', 'Saldo Inicial', $value, 'number'); ?></div>
                        <?php $value = (isset($contabancaria) ? $contabancaria->datasaldoinicial : ''); ?>
                        <div class="col-md-6">
                            <?php echo render_date_input('datasaldoinicial', 'Data do Saldo Inicial', _d($value)); ?></div>
                        <?php $value = (isset($contabancaria) ? $contabancaria->ativo : ''); ?>
                        <div class="col-md-6 row">
                            <div class="row">
                                <div class="col-md-6 mtop10 border-right">
                                    <span><?php echo 'Conta Bancária Ativa?'; ?></span>
                                </div>
                                <div class="col-md-6 mtop10">
                                    <div class="onoffswitch">
                                        <input type="checkbox" id="ativo" data-perm-id="1"
                                               class="onoffswitch-checkbox" <?php if (isset($contabancaria) && $contabancaria->ativo == '1') {
                                            echo 'checked';
                                        } ?> value="ativo" name="ativo">
                                        <label class="onoffswitch-label" for="ativo"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php hooks()->do_action('before_contabancaria_form_template_close', $contabancaria ?? null); ?>
            <?php echo form_close(); ?>
        </div>
        <div class="btn-bottom-pusher"></div>
    </div>
</div>

<?php init_tail(); ?>



<script>
var customer_currency = '';
Dropzone.options.expenseForm = false;
var expenseDropzone;
init_ajax_project_search_by_customer_id();
var selectCurrency = $('select[name="currency"]');
<?php if (isset($customer_currency)) { ?>
var customer_currency = '<?php echo $customer_currency; ?>';
<?php } ?>
$(function() {
    $('body').on('change', '#project_id', function() {
        var project_id = $(this).val();
        if (project_id != '') {
            if (customer_currency != 0) {
                selectCurrency.val(customer_currency);
                selectCurrency.selectpicker('refresh');
            } else {
                set_base_currency();
            }
        } else {
            do_billable_checkbox();
        }
    });

    if ($('#dropzoneDragArea').length > 0) {
        expenseDropzone = new Dropzone("#expense-form", appCreateDropzoneOptions({
            autoProcessQueue: false,
            clickable: '#dropzoneDragArea',
            previewsContainer: '.dropzone-previews',
            addRemoveLinks: true,
            maxFiles: 1,
            success: function(file, response) {
                response = JSON.parse(response);
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length ===
                    0) {
                    window.location.assign(response.url);
                }
            },
        }));
    }

    appValidateForm($('#expense-form'), {
        category: 'required',
        date: 'required',
        amount: 'required',
        currency: 'required',
        repeat_every_custom: {
            min: 1
        },
    }, expenseSubmitHandler);

    $('input[name="billable"]').on('change', function() {
        do_billable_checkbox();
    });

    $('#repeat_every').on('change', function() {
        if ($(this).selectpicker('val') != '' && $('input[name="billable"]').prop('checked') == true) {
            $('.billable_recurring_options').removeClass('hide');
        } else {
            $('.billable_recurring_options').addClass('hide');
        }
    });

    // hide invoice recurring options on page load
    $('#repeat_every').trigger('change');

    $('select[name="clientid"]').on('change', function() {
        customer_init();
        do_billable_checkbox();
        $('input[name="billable"]').trigger('change');
    });

    <?php if (!isset($contabancaria)) { ?>
    $('select[name="tax"], select[name="tax2"]').on('change', function() {

        delay(function() {
            var $amount = $('#amount'),
                taxDropdown1 = $('select[name="tax"]'),
                taxDropdown2 = $('select[name="tax2"]'),
                taxPercent1 = parseFloat(taxDropdown1.find('option[value="' + taxDropdown1
                    .val() + '"]').attr('data-percent')),
                taxPercent2 = parseFloat(taxDropdown2.find('option[value="' + taxDropdown2
                    .val() + '"]').attr('data-percent')),
                total = $amount.val();

            if (total == 0 || total == '') {
                return;
            }

            if ($amount.attr('data-original-amount')) {
                total = $amount.attr('data-original-amount');
            }

            total = parseFloat(total);

            if (taxDropdown1.val() || taxDropdown2.val()) {

                $('#tax_subtract').removeClass('hide');

                var totalTaxPercentExclude = taxPercent1;
                if (taxDropdown2.val()) {
                    totalTaxPercentExclude += taxPercent2;
                }

                var totalExclude = accounting.toFixed(total - exclude_tax_from_amount(
                    totalTaxPercentExclude, total), app.options.decimal_places);
                $('#tax_subtract_total').html(accounting.toFixed(totalExclude, app.options
                    .decimal_places));
            } else {
                $('#tax_subtract').addClass('hide');
            }
            if ($('#tax1_included').prop('checked') == true) {
                subtract_tax_amount_from_expense_total();
            }
        }, 200);
    });

    $('#amount').on('blur', function() {
        $(this).removeAttr('data-original-amount');
        if ($(this).val() == '' || $(this).val() == '') {
            $('#tax1_included').prop('checked', false);
            $('#tax_subtract').addClass('hide');
        } else {
            var tax1 = $('select[name="tax"]').val();
            var tax2 = $('select[name="tax2"]').val();
            if (tax1 || tax2) {
                setTimeout(function() {
                    $('select[name="tax2"]').trigger('change');
                }, 100);
            }
        }
    })

    $('#tax1_included').on('change', function() {

        var $amount = $('#amount'),
            total = parseFloat($amount.val());

        // da pokazuva total za 2 taxes  Subtract TAX total (136.36) from expense amount
        if (total == 0) {
            return;
        }

        if ($(this).prop('checked') == false) {
            $amount.val($amount.attr('data-original-amount'));
            return;
        }

        subtract_tax_amount_from_expense_total();
    });
    <?php } ?>
});

function subtract_tax_amount_from_expense_total() {
    var $amount = $('#amount'),
        total = parseFloat($amount.val()),
        taxDropdown1 = $('select[name="tax"]'),
        taxDropdown2 = $('select[name="tax2"]'),
        taxRate1 = parseFloat(taxDropdown1.find('option[value="' + taxDropdown1.val() + '"]').attr('data-percent')),
        taxRate2 = parseFloat(taxDropdown2.find('option[value="' + taxDropdown2.val() + '"]').attr('data-percent'));

    var totalTaxPercentExclude = taxRate1;
    if (taxRate2) {
        totalTaxPercentExclude += taxRate2;
    }

    if ($amount.attr('data-original-amount')) {
        total = parseFloat($amount.attr('data-original-amount'));
    }

    $amount.val(exclude_tax_from_amount(totalTaxPercentExclude, total));

    if ($amount.attr('data-original-amount') == undefined) {
        $amount.attr('data-original-amount', total);
    }
}

function customer_init() {
    var customer_id = $('select[name="clientid"]').val();
    var projectAjax = $('select[name="project_id"]');
    var clonedProjectsAjaxSearchSelect = projectAjax.html('').clone();
    var projectsWrapper = $('.projects-wrapper');
    projectAjax.selectpicker('destroy').remove();
    projectAjax = clonedProjectsAjaxSearchSelect;
    $('#project_ajax_search_wrapper').append(clonedProjectsAjaxSearchSelect);
    init_ajax_project_search_by_customer_id();
    if (!customer_id) {
        set_base_currency();
        projectsWrapper.addClass('hide');
    }
    $.get(admin_url + 'expenses/get_customer_change_data/' + customer_id, function(response) {
        if (customer_id && response.customer_has_projects) {
            projectsWrapper.removeClass('hide');
        } else {
            projectsWrapper.addClass('hide');
        }
        var client_currency = parseInt(response.client_currency);
        if (client_currency != 0) {
            customer_currency = client_currency;
            do_billable_checkbox();
        } else {
            customer_currency = '';
            set_base_currency();
        }
    }, 'json');
}

function expenseSubmitHandler(form) {

    selectCurrency.prop('disabled', false);

    $('select[name="tax2"]').prop('disabled', false);
    $('input[name="billable"]').prop('disabled', false);
    $('input[name="date"]').prop('disabled', false);

    $.post(form.action, $(form).serialize()).done(function(response) {
        response = JSON.parse(response);
        if (response.expenseid) {
            if (typeof(expenseDropzone) !== 'undefined') {
                if (expenseDropzone.getQueuedFiles().length > 0) {
                    expenseDropzone.options.url = admin_url + 'expenses/add_expense_attachment/' + response
                        .expenseid;
                    expenseDropzone.processQueue();
                } else {
                    window.location.assign(response.url);
                }
            } else {
                window.location.assign(response.url);
            }
        } else {
            window.location.assign(response.url);
        }
    });
    return false;
}

function do_billable_checkbox() {
    var val = $('select[name="clientid"]').val();
    if (val != '') {
        $('.billable').removeClass('hide');
        if ($('input[name="billable"]').prop('checked') == true) {
            if ($('#repeat_every').selectpicker('val') != '') {
                $('.billable_recurring_options').removeClass('hide');
            } else {
                $('.billable_recurring_options').addClass('hide');
            }
            if (customer_currency != '') {
                selectCurrency.val(customer_currency);
                selectCurrency.selectpicker('refresh');
            } else {
                set_base_currency();
            }
        } else {
            $('.billable_recurring_options').addClass('hide');
            // When project is selected, the project currency will be used, either customer currency or base currency
            if ($('#project_id').selectpicker('val') == '') {
                set_base_currency();
            }
        }
    } else {
        set_base_currency();
        $('.billable').addClass('hide');
        $('.billable_recurring_options').addClass('hide');
    }
}

function set_base_currency() {
    selectCurrency.val(selectCurrency.data('base'));
    selectCurrency.selectpicker('refresh');
}
</script>
</body>

</html>
