<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$tenant = !empty($tenant) ? $tenant : perfex_saas_tenant();
$package_quota = $tenant->package_invoice->metadata->limitations ?? [];
$usage_limits = perfex_saas_get_tenant_quota_usage($tenant);
$display  = $tenant->package_invoice->metadata->dashboard_quota_visibility ?? '';
if (is_admin() && $display === "no" &&  $tenant->package_invoice->metadata->client_theme !== "agency") return;
$disabled_default_modules = perfex_saas_tenant_disabled_default_modules($tenant);
?>

<div class="widget relative tw-mb-4" id="widget-perfex_saas_top_stats" data-name="<?= _l('perfex_saas_tenant_quota_dashboard'); ?>">
    <div class="widget-dragger ui-sortable-handle"></div>
    <h4><?= _l('perfex_saas_tenant_quota_dashboard'); ?></h4>
    <div class="row">

        <?php foreach ($usage_limits as $resources => $usage) : ?>
            <?php
            $quota = perfex_saas_tenant_resources_quota($tenant, $resources);

            $unlimited = $quota === -1;
            $usage_percent = $unlimited ? 0 : ($quota > 0 ? number_format(($usage * 100) / $quota, 2) : 0);
            $color = $usage_percent < 50 ? "green" : ($usage_percent > 90 ? 'red' : '#ca8a03');

            if ($unlimited && $display === 'limited-only' || in_array($resources, $disabled_default_modules)) continue;
            ?>
            <div class="col-xs-12 col-md-3 col-sm-4 tw-mb-2">
                <div class="top_stats_wrapper" <?= $usage_percent > 95 ? "style='border-color:$color;'" : ''; ?>>
                    <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                        <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center mr-2">
                            <?= _l('perfex_saas_limit_' . $resources); ?>
                        </div>
                        <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?= $usage; ?>/<?= $unlimited ? '<i class="fa fa-infinity"></i>' : $quota; ?></span>
                    </div>
                    <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
                        <div class="progress-bar no-percent-text not-dynamic" style="background:<?= $color; ?>" role="progressbar" aria-valuenow="<?= $usage_percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-percent="<?php echo $usage_percent; ?>">
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- storage -->
        <?php
        $quota = perfex_saas_tenant_storage_limit($tenant);
        $quota_in_byte = perfex_saas_convert_formatted_size_to_bytes($quota);
        $usage_in_byte = perfex_saas_tenant_used_storage($tenant);
        $usage = perfex_saas_format_storage_size($usage_in_byte);
        $usage_percent = $quota_in_byte  > 0 ? number_format(($usage_in_byte * 100) / $quota_in_byte, 2) : 0;
        $color = $usage_percent < 50 ? "green" : ($usage_percent > 90 ? 'red' : '#ca8a03');
        ?>
        <div class="col-xs-12 col-md-3 col-sm-4 tw-mb-2">
            <div class="top_stats_wrapper" <?= $usage_percent > 95 ? "style='border-color:$color;'" : ''; ?>>
                <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                    <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center mr-2">
                        <?= _l('perfex_saas_limit_storage'); ?>
                    </div>
                    <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?= $usage; ?>/<?= $quota; ?></span>
                </div>
                <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
                    <div class="progress-bar no-percent-text not-dynamic" style="background:<?= $color; ?>" role="progressbar" aria-valuenow="<?= $usage_percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-percent="<?php echo $usage_percent; ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>