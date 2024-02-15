<?php

defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
$packages = isset($packages) ? $packages : $CI->perfex_saas_model->packages();
$currency_name = get_base_currency()->name;
$is_client = is_client_logged_in();
$showing_subscribed_card = isset($list_active_only) && $list_active_only;

?>


<div
    class="<?= $showing_subscribed_card ? '' : 'tw-grid tw-gap-3 tw-grid-cols-1 sm:tw-grid-cols-2 md:tw-grid-cols-3'; ?>">
    <?php foreach ($packages as $package) :

        $storage = (int)($package->metadata->storage_limit->size ?? 0);
        $limitations = array_merge(
            (array)$package->metadata->limitations ?? [],
            ['storage' => $storage === -1 ? _l('perfex_saas_unlimited') : $storage . ' ' . ($package->metadata->storage_limit->unit ?? 'B')],
        );

        $custom_repeat = $package->metadata->invoice->recurring == 'custom';
        $interval = $custom_repeat ? $package->metadata->invoice->repeat_every_custom : $package->metadata->invoice->recurring;
        $interval_type = $custom_repeat ? $package->metadata->invoice->repeat_type_custom . 's' : 'months';

        $subscribed = !empty($invoice->{perfex_saas_column('packageid')}) && $invoice->{perfex_saas_column('packageid')} == $package->id;
        if ($showing_subscribed_card && !$subscribed) continue;
        if ($is_client && $package->is_private && !$subscribed) continue;
    ?>

    <div class=" panel_s tw-p-4 tw-py-2
    <?= $package->is_default == '1' ? 'tw-bg-neutral-300' : 'tw-bg-neutral-100' ?> tw-flex tw-flex-col
    tw-justify-between">
        <div class="panel_body tw-flex tw-flex-col tw-items-center tw-justify-center text-center">
            <h3>
                <?= $package->name; ?>
                <?php if ($subscribed) : ?>
                <i class="fa fa-check-circle text-success"></i>
                <?php endif; ?>
            </h3>
            <div>
                <span class="tw-bg-neutral-700 tw-text-lg tw-text-white badge badge-primary tw-font-bold">
                    <?= $package->price; ?>
                    <?= $currency_name; ?>
                    <small class="text-lowercase">/
                        <?= $interval > 1 ? $interval : ''; ?>
                        <?= _l($interval > 1 ? "perfex_saas" . $interval_type : "perfex_saas" . $interval_type . '_singular'); ?>
                    </small>
                </span>
            </div>

            <div class="tw-mt-2 tw-mb-4"><?= $package->description; ?></div>

            <!-- modules and limiation list -->
            <?php $module_display_option = ($package->metadata->show_modules_list ?? 'yes'); ?>
            <?php if ($module_display_option !== 'no') : ?>
            <div class="tw-flex tw-justify-center tw-w-full">
                <ul class="tw-grid tw-grid-cols-2 tw-gap-2">
                    <?php
                            $key = 0;
                            if (!empty($package->modules)) :
                                foreach ($package->modules as $key => $value) : ?>
                    <li class="text-left text-capitalize <?= ((int)$key + 1) % 2 ? 'tw-mr-2' : 'tw-ml-2'; ?>">
                        <?= $module_display_option === "yes_4" ? '<span><i class="fa fa-check"></i></span>' : ''; ?>
                        <?= $CI->perfex_saas_model->get_module_custom_name($value); ?>
                    </li>
                    <?php
                                endforeach;
                            endif; ?>

                    <?php
                            $limit_display_option = $package->metadata->show_limits_on_package ?? 'yes_3';
                            if ($limit_display_option !== 'no' && !empty($limitations)) : ?>
                    <?php $key = isset($key) ? ((int)$key + 1) : 0;
                                foreach ($limitations as $feature => $limit) : ?>
                    <li class="text-left text-capitalize <?= ($key + 1) % 2 ? 'tw-mr-2' : 'tw-ml-2'; ?>">
                        <?= $limit_display_option === "yes_2" ||  $limit_display_option === "yes_4" ? '<span><i class="fa fa-check"></i></span>' : ''; ?>
                        <?= $limit_display_option === "yes_2" ||  $limit_display_option === "yes_3" ? ((int)$limit === -1 ? _l('perfex_saas_unlimited') : $limit) : ''; ?>
                        <?= $feature; ?>
                    </li>
                    <?php $key++;
                                endforeach;
                            endif;
                            ?>

                </ul>
            </div>
            <?php endif ?>
        </div>

        <!-- Package action -->
        <?php if ($is_client) : ?>

        <div class="panel_footer tw-flex tw-justify-center tw-mt-4">

            <?php if (empty($invoice)) : ?>

            <a href="<?= base_url('clients/packages/' . $package->slug . '/select'); ?>" class="btn btn-primary">
                <?= $package->trial_period > 0 ? _l('perfex_saas_start_trial', $package->trial_period) : _l('perfex_saas_subscribe'); ?>
            </a>

            <?php elseif ($subscribed) : ?>

            <div class="tw-flex tw-flex-col">
                <?php if ($on_trial) : ?>

                <a href="<?= base_url('clients/packages/' . $invoice->slug . '/select'); ?>" class="btn btn-danger">
                    <i class="fa fa-check"></i>
                    <?= $days_left > 0 ? _l('perfex_saas_view_subscription_invoice_trial', $days_left) : _l('perfex_saas_view_subscription_trial_over'); ?>
                </a>
                <?php else : ?>

                <a class="text-center" href="<?= base_url("invoice/$invoice->id/$invoice->hash"); ?>">
                    <?php require('next_invoice_date.php'); ?>
                </a>

                <?php endif ?>
                <?php if (perfex_saas_is_single_package_mode() || ($package->metadata->allow_customization ?? 'yes') !== 'no') : ?>
                <a href="<?= base_url('clients/my_account'); ?>" class="btn btn-info mtop10">
                    <i class="fa fa-cogs"></i>
                    <?= _l('perfex_saas_pricing_customize'); ?>
                </a>
                <?php endif ?>
            </div>

            <?php else : ?>

            <a onclick="return confirm('<?= _l('perfex_saas_upgrade_confirm_text'); ?>')"
                href="<?= base_url('clients/packages/' . $package->slug . '/select'); ?>" class="btn btn-primary">
                <?= _l('perfex_saas_upgrade'); ?>
            </a>

            <?php endif ?>

        </div>
        <?php else : ?>
        <div class="panel_footer tw-flex tw-justify-between tw-mt-4">
            <div class="tw-flex  tw-space-x-2">
                <?php $stat = $CI->perfex_saas_model->package_stats((int)$package->id); ?>
                <?php if (in_array($package->db_scheme, ['single_pool', 'shard'])) : ?>
                <span data-title="<?= _l('perfex_saas_total_db_pools'); ?>" data-toggle="tooltip">
                    <i class="fa fa-database"></i>
                    <?= count($package->db_pools); ?>
                </span>

                <span data-title="<?= _l('perfex_saas_total_instances_on_pool'); ?>" data-toggle="tooltip">
                    <i class="fa fa-users"></i>
                    <?= $stat->total_pool_population; ?>
                </span>
                <?php endif; ?>

                <span data-title="<?= _l('perfex_saas_package_total_attached_invoices'); ?>" data-toggle="tooltip">
                    <i class="fa fa-dollar"></i>
                    <?= $stat->total_invoices; ?>
                </span>


            </div>
            <div class="tw-flex tw-space-x-2">

                <?php if (has_permission('perfex_saas_packages', '', 'create')) : ?>
                <!-- copy to clipboad -->
                <a href="#" data-success-text="<?= _l('perfex_saas_copied'); ?>"
                    data-text="<?= site_url('authentication/register') . '?ps_plan=' . $package->slug; ?>"
                    onclick="return false;" data-toggle="tooltip"
                    data-title="<?= _l('perfex_saas_package_copy_to_clipboard'); ?>"
                    class="btn btn-secondary btn-xs copy-to-clipboard">
                    <i class="fa fa-share-alt"></i>
                </a>
                <!-- clone -->
                <a href="<?= admin_url('perfex_saas/packages/clone/' . $package->id); ?>" data-toggle="tooltip"
                    data-title="<?= _l('perfex_saas_clone'); ?>" class="btn btn-secondary btn-xs"><i
                        class="fa fa-copy"></i></a>
                <?php endif ?>

                <?php if (has_permission('perfex_saas_packages', '', 'edit')) : ?>
                <a href="<?= admin_url('perfex_saas/packages/edit/' . $package->id); ?>" data-toggle="tooltip"
                    data-title="<?= _l('perfex_saas_edit'); ?>" class="btn btn-primary btn-xs"><i
                        class="fa fa-pen"></i></a>
                <?php endif ?>

                <?php if (has_permission('perfex_saas_packages', '', 'delete')) : ?>
                <?= form_open(admin_url('perfex_saas/packages/delete')); ?>
                <?= form_hidden('id', $package->id); ?>
                <button class="btn btn-danger btn-xs  _delete" data-toggle="tooltip"
                    data-title="<?= _l('perfex_saas_delete'); ?>"><i class="fa fa-trash"></i></button>
                <?= form_close(); ?>
                <?php endif ?>

            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach ?>
</div>