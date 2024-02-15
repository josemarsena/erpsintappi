<?php

if ($invoice->recurring > 0 || $invoice->is_recurring_from != null) {
    $recurring_invoice           = $invoice;
    $show_recurring_invoice_info = true;

    if ($invoice->is_recurring_from != null) {
        $recurring_invoice = $this->invoices_model->get($invoice->is_recurring_from);
        // Maybe recurring invoice not longer recurring?
        if ($recurring_invoice->recurring == 0) {
            $show_recurring_invoice_info = false;
        } else {
            $next_recurring_date_compare = $recurring_invoice->last_recurring_date;
        }
    } else {
        $next_recurring_date_compare = $recurring_invoice->date;
        if ($recurring_invoice->last_recurring_date) {
            $next_recurring_date_compare = $recurring_invoice->last_recurring_date;
        }
    }
    if ($show_recurring_invoice_info) {
        if ($recurring_invoice->custom_recurring == 0) {
            $recurring_invoice->recurring_type = 'MONTH';
        }
        $next_date = date('Y-m-d', strtotime('+' . $recurring_invoice->recurring . ' ' . strtoupper($recurring_invoice->recurring_type), strtotime($next_recurring_date_compare)));
    } ?>

    <div class="mbot10">
        <?php if ($show_recurring_invoice_info) {
            if ($recurring_invoice->cycles == 0 || $recurring_invoice->cycles != $recurring_invoice->total_cycles) {

                $datediff = strtotime($next_date) - time();
                $next_days_left = ($datediff / (60 * 60 * 24));
                echo '<span class="label label-' . ($next_days_left < 5 ? 'warning' : 'success') . ' tw-ml-3" data-toggle="tooltip" data-title="' . _l('perfex_saas_view_subscription_invoice') . '"><i class="fa-regular fa-eye fa-fw tw-mr-1"></i> ' . _l('next_invoice_date', '&nbsp;<b>' . _d($next_date) . '</b>') . '</span>';
            }
        } ?>
    </div>
<?php
} ?>