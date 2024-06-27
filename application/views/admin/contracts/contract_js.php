<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/**
 * Included in application/views/admin/clients/client.php
 */
?>
<script>
Dropzone.options.clientAttachmentsUpload = false;
var contract_id = $('input[name="id"]').val();
$(function() {

    if ($('#client-attachments-upload').length > 0) {
        new Dropzone('#client-attachments-upload', appCreateDropzoneOptions({
            paramName: "file",
            accept: function(file, done) {
                done();
            },
            success: function(file, response) {
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length ===
                    0) {
                    window.location.reload();
                }
            }
        }));
    }

    // Save button not hidden if passed from url ?tab= we need to re-click again
    if (tab_active) {
        $('body').find('.nav-tabs [href="#' + tab_active + '"]').click();
    }

    if(tab_active === 'customer_admins') {
        $('#profile-save-section').addClass('hide');
    }

    $('a[href="#customer_admins"]').on('click', function() {
        $('#profile-save-section').addClass('hide');
    });

    $('.customer-profile-tabs  a').not('a[href="#customer_admins"]').on('click', function() {
        $('#profile-save-section').removeClass('hide');
    });

    $("input[name='tasks_related_to[]']").on('change', function() {
        var tasks_related_values = []
        $('#tasks_related_filter :checkbox:checked').each(function(i) {
            tasks_related_values[i] = $(this).val();
        });
        $('input[name="tasks_related_to"]').val(tasks_related_values.join());
        $('.table-rel-tasks').DataTable().ajax.reload();
    });

    var contact_id = get_url_param('contactid');
    if (contact_id) {
        contact(customer_id, contact_id);
    }

    // consents=CONTACT_ID
    var consents = get_url_param('consents');
    if (consents) {
        view_contact_consent(consents);
    }

    // If user clicked save and add new contact
    if (get_url_param('new_contact')) {
        contact(customer_id);
    }

    $('body').on('change', '.onoffswitch input.customer_file', function(event, state) {
        var invoker = $(this);
        var checked_visibility = invoker.prop('checked');
        var share_file_modal = $('#customer_file_share_file_with');
        setTimeout(function() {
            $('input[name="file_id"]').val(invoker.attr('data-id'));
            if (checked_visibility && share_file_modal.attr('data-total-contacts') > 1) {
                share_file_modal.modal('show');
            } else {
                do_share_file_contacts();
            }
        }, 200);
    });

    $('.customer-form-submiter').on('click', function() {
        var form = $('.client-form');
        if (form.valid()) {
            if ($(this).hasClass('save-and-add-contact')) {
                form.find('.additional').html(hidden_input('save_and_add_contact', 'true'));
            } else {
                form.find('.additional').html('');
            }
            form.submit();
        }
    });

    if (typeof(Dropbox) != 'undefined' && $('#dropbox-chooser').length > 0) {
        document.getElementById("dropbox-chooser").appendChild(Dropbox.createChooseButton({
            success: function(files) {
                saveCustomerProfileExternalFile(files, 'dropbox');
            },
            linkType: "preview",
            extensions: app.options.allowed_files.split(','),
        }));
    }

    /* Customer profile tickets table */
    $('.table-tickets-single').find('#th-submitter').removeClass('toggleable');

    initDataTable('.table-tickets-single', admin_url + 'tickets/index/false/' + customer_id, undefined,
        undefined, 'undefined', [$('table thead .ticket_created_column').index(), 'desc']);

    /* Customer profile contracts table */
    initDataTable('.table-contracts-single-client', admin_url + 'contracts/table/' + customer_id, undefined,
        undefined, 'undefined', [6, 'desc']);

    /* Custome profile contacts table */
    var contactsNotSortable = [];
    <?php if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') { ?>
    contactsNotSortable.push($('#th-consent').index());
    <?php } ?>
    _table_api = initDataTable('.table-contacts', admin_url + 'clients/contacts/' + customer_id,
        contactsNotSortable, contactsNotSortable);
    if (_table_api) {
        <?php if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') { ?>
        _table_api.on('draw', function() {
            var tableData = $('.table-contacts').find('tbody tr');
            $.each(tableData, function() {
                $(this).find('td:eq(1)').addClass('bg-neutral');
            });
        });
        <?php } ?>
    }
    /* Customer profile invoices table */
    initDataTable('.table-faturas-contrato',
        admin_url + 'invoices/table_proposta/' + contract_id,
        'undefined',
        'undefined',
        'undefined', [
            [3, 'desc'],
            [0, 'desc']
        ]);

    var vRules = {};
    if (app.options.company_is_required == 1) {
        vRules = {
            company: 'required',
        }
    }

    appValidateForm($('.client-form'), vRules);

    if (typeof(customer_id) == 'undefined') {
        $('#company').on('blur', function() {
            var company = $(this).val();
            var $companyExistsDiv = $('#company_exists_info');

            if (company == '') {
                $companyExistsDiv.addClass('hide');
                return;
            }

            $.post(admin_url + 'clients/check_duplicate_customer_name', {
                    company: company
                })
                .done(function(response) {
                    if (response) {
                        response = JSON.parse(response);
                        if (response.exists == true) {
                            $companyExistsDiv.removeClass('hide');
                            $companyExistsDiv.html('<div class="alert alert-info">' + response
                                .message + '</div>');
                        } else {
                            $companyExistsDiv.addClass('hide');
                        }
                    }
                });
        });
    }

    $('.billing-same-as-customer').on('click', function(e) {
        e.preventDefault();
        $('textarea[name="billing_street"]').val($('textarea[name="address"]').val());
        $('input[name="billing_city"]').val($('input[name="city"]').val());
        $('input[name="billing_state"]').val($('input[name="state"]').val());
        $('input[name="billing_zip"]').val($('input[name="zip"]').val());
        $('select[name="billing_country"]').selectpicker('val', $('select[name="country"]')
            .selectpicker('val'));
    });

    $('.customer-copy-billing-address').on('click', function(e) {
        e.preventDefault();
        $('textarea[name="shipping_street"]').val($('textarea[name="billing_street"]').val());
        $('input[name="shipping_city"]').val($('input[name="billing_city"]').val());
        $('input[name="shipping_state"]').val($('input[name="billing_state"]').val());
        $('input[name="shipping_zip"]').val($('input[name="billing_zip"]').val());
        $('select[name="shipping_country"]').selectpicker('val', $('select[name="billing_country"]')
            .selectpicker('val'));
    });

    $('body').on('hidden.bs.modal', '#contact', function() {
        $('#contact_data').empty();
    });

    $('.client-form').on('submit', function() {
        $('select[name="default_currency"]').prop('disabled', false);
    });

});





</script>