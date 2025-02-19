// Predefined global variables
let table_contasreceber, table_contaspagar;
Dropzone.autoDiscover = false;

var fnServerParams = {};



(function($) {
    "use strict";
    table_contasreceber = $(".table-contasreceber");
    table_contaspagar = $(".table-contaspagar");

    var Params = {
        "from_date": 'input[name="from_date"]',
        "to_date": 'input[name="to_date"]',
        "vendor": "[name='vendor[]']",
        "status": "[name='status[]']",
        "item_filter": "[name='item_filter[]']",
    };

    var Sales_table_ServerParams = {};

    initDataTable('.table-contasreceber', admin_url + 'financeiro/table_contasreceber', undefined, undefined,
        'undefined',[1, 'desc']);
    init_contasreceber();

    $.each(Params, function(i, obj) {
        $('select' + obj).on('change', function() {
            table_contasreceber.DataTable().ajax.reload()
                .columns.adjust()
                .responsive.recalc();
        });
    });


    initDataTable('.table-contaspagar', admin_url + 'financeiro/table_contaspagar', undefined, undefined,
        'undefined',[1, 'desc']);

    // init_contaspagar();


    $.each(Params, function(i, obj) {
        $('select' + obj).on('change', function() {
            table_contaspagar.DataTable().ajax.reload()
                .columns.adjust()
                .responsive.recalc();
        });
    });

    // revisar
    $('input[name="from_date"]').on('change', function() {
        table_contasreceber.DataTable().ajax.reload()
            .columns.adjust()
            .responsive.recalc();
    });

    //revisar
    $('input[name="to_date"]').on('change', function() {
        table_contasreceber.DataTable().ajax.reload()
            .columns.adjust()
            .responsive.recalc();
    });

    // revisar
    if ($('#pur_order-expense-form').length > 0) {
        expenseDropzone = new Dropzone("#pur_order-expense-form", appCreateDropzoneOptions({
            autoProcessQueue: false,
            clickable: '#dropzoneDragArea',
            previewsContainer: '.dropzone-previews',
            addRemoveLinks: true,
            maxFiles: 1,
            success: function(file, response) {
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                    window.location.reload();
                }
            }
        }));
    }
    // revisar
 //   appValidateForm($('#pur_order-expense-form'), {
  //      category: 'required',
 //       date: 'required',
 //       amount: 'required'
//    }, projectExpenseSubmitHandler);


})(jQuery);

// Init single invoice
function init_contaspagar(id) {

    load_small_table_item(id,
        '#contas_pagar',
        'id_fatura',
        'financeiro/get_contaspagar_data_ajax',
        '.table-contas_pagar');
}



// Init single invoice
function init_contasreceber(id) {

    load_small_table_item(id, '#invoice', 'invoiceid', 'financeiro/get_contasreceber_data_ajax', '.table-invoices');
}

function load_small_contaspagar_table_item(id, selector, input_name, url, table) {
    "use strict";
    var _tmpID = $('input[name="' + input_name + '"]').val();
    // Check if id passed from url, hash is prioritized becuase is last
    if (_tmpID !== '' && !window.location.hash) {
        id = _tmpID;
        // Clear the current id value in case user click on the left sidebar credit_note_ids
        $('input[name="' + input_name + '"]').val('');
    } else {
        // check first if hash exists and not id is passed, becuase id is prioritized
        if (window.location.hash && !id) {
            id = window.location.hash.substring(1); //Puts hash in variable, and removes the # character
        }
    }
    if (typeof(id) == 'undefined' || id === '') { return; }
    destroy_dynamic_scripts_in_element($(selector))
    if (!$("body").hasClass('small-table')) { toggle_small_pur_order_view(table, selector); }
    $('input[name="' + input_name + '"]').val(id);
    do_hash_helper(id);
    $(selector).load(admin_url + url + '/' + id);
    if (is_mobile()) {
        $('html, body').animate({
            scrollTop: $(selector).offset().top + 150
        }, 600);
    }
}
/**

(function($) {
    "use strict";
    initDataTable('table.table-contasreceber', admin_url + 'financeiro/table_contasreceber');
    appValidateForm($('#send_rq-form'),{subject:'required',attachment:'required'});
})(jQuery);


    // Invoices tables
    initDataTable('table.table-contasreceber', admin_url + 'financeiro/table_contasreceber');

 */


// Registra um Pagamento do Contas a Receber
function registra_pagamento_areceber(id) {
    if (typeof id == "undefined" || id === "") {
        return;
    }
    $("#invoice").load(admin_url + "financeiro/registra_pagamento_areceber_ajax/" + id);
}