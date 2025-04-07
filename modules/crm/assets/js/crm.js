
// Initing relation tasks tables
function init_rel_invoicescontrato_table(selector, id) {
    if (typeof selector == "undefined") {
        selector = ".table-invoicescontrato";
    }
    selector = ".table-invoicescontrato";

    var $selector = $("body").find(selector);
    if ($selector.length === 0) {
        return;
    }



    var TasksServerParams = {},
        tasksRelationTableNotSortable = [0], // bulk actions
        TasksFilters;

    TasksFilters = $("body").find(
        "._hidden_inputs._filters._tasks_filters input"
    );

    $.each(TasksFilters, function () {
        TasksServerParams[$(this).attr("name")] =
            '[name="' + $(this).attr("name") + '"]';
    });


    var url = admin_url + "crm/table_invoicescontrato/" + id;

    initDataTable(
        $selector,
        url,
        "undefined",
        "undefined",
        [],
        [2, "asc"]
    );
}

// Invoices quick total stats
function init_faturascontrato_total(manual) {
    if ($("#faturascontrato_total").length === 0) {
        return;
    }
    var _inv_total_inline = $(".invoices-total-inline");
    var _inv_total_href_manual = $(".faturascontrato_total");

    if (
        $("body").hasClass("invoices-total-manual") &&
        typeof manual == "undefined" &&
        !_inv_total_href_manual.hasClass("initialized")
    ) {
        return;
    }

    if (
        _inv_total_inline.length > 0 &&
        _inv_total_href_manual.hasClass("initialized")
    ) {
        // On the next request won't be inline in case of currency change
        // Used on dashboard
        _inv_total_inline.removeClass("invoices-total-inline");
        return;
    }

    _inv_total_href_manual.addClass("initialized");
    var _years = $("body")
        .find('select[name="invoices_total_years"]')
        .selectpicker("val");
    var years = [];
    $.each(_years, function (i, _y) {
        if (_y !== "") {
            years.push(_y);
        }
    });

    var currency = $("body").find('select[name="total_currency"]').val();
    var data = {
        currency: currency,
        years: years,
        init_total: true,
    };

    var project_id = $('input[name="project_id"]').val();
    var customer_id = $('.customer_profile input[name="userid"]').val();
    if (typeof project_id != "undefined") {
        data.project_id = project_id;
    } else if (typeof customer_id != "undefined") {
        data.customer_id = customer_id;
    }
    $.post(admin_url + "crm/obter_faturascontrato_total", data).done(function (
        response
    ) {
        $("#faturascontrato_total").html(response);
    });
}