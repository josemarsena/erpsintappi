<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $target = perfex_saas_is_tenant() ? 'target="_blank"' : ''; ?>

<!-- Invoice notification -->
<?php if (!empty($invoice)) : ?>
    <div class="ps <?= perfex_saas_is_tenant() ? 'col-md-12' : ''; ?>">
        <?php if ($on_trial) : ?>
            <div class="alert alert-<?= $days_left > 0 ? 'warning' : 'danger'; ?>  tw-mt-5">
                <?= $days_left > 0 ? _l('perfex_saas_trial_invoice_not', [$invoice->name, _d($invoice->duedate), $invoice_days_left]) : _l('perfex_saas_trial_invoice_over_not'); ?>
                <a onclick="return confirm('<?= _l('perfex_saas_upgrade_confirm_text'); ?>')" href="<?= APP_BASE_URL_DEFAULT . 'clients/packages/' . $invoice->slug . '/select'; ?>" class="fs-5 text-danger" <?= $target; ?>>
                    <?= _l('perfex_saas_click_here_to_subscribe'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!$on_trial && $invoice->status != Invoices_model::STATUS_PAID) :
        ?>
            <div class="alert alert-danger tw-mt-5">
                <?= _l('perfex_saas_outstanding_invoice_not'); ?> <a href="<?= APP_BASE_URL_DEFAULT . "invoice/$invoice->id/$invoice->hash"; ?>" <?= $target; ?>><?= _l('perfex_saas_click_here_to_pay'); ?></a>
            </div>
        <?php endif
        ?>
    </div>
<?php endif ?>