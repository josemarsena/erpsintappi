<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- shared settings selection -->
<div class="tw-mt-10 tw-mb-10 options">

    <?php
    $CI = &get_instance();
    $options = $CI->perfex_saas_model->shared_options();
    $default_options = $CI->perfex_saas_model->sensitive_shared_options();
    $default_options = array_column($default_options, 'key');
    $selected_options = get_option('perfex_saas_sensitive_options');
    $selected_options = empty($selected_options) ? $default_options : (array)json_decode($selected_options);
    ?>
    <button type="button" class="btn btn-danger pull-right" onclick="perfexSaasResetSeedOptionsSelection()"><?= _l('perfex_saas_reset_to_default'); ?></button>
    <label for="perfex_saas_filter w-full">
        <?= _l('perfex_saas_settings_sensitive_options'); ?>
    </label>
    <br />
    <small class="text-danger"><?= _l('perfex_saas_settings_sensitive_options_hint'); ?></small>
    <input type="text" class="form-control tw-mb-2 tw-mt-4 perfex_saas_filter" placeholder="<?= _l('perfex_saas_filter_tenants_seed'); ?>" />

    <div class="perfex_saas_filterables tw-overflow-y-auto" style="height:35vh">
        <div class="row w-ml-1 tw-mr-1">
            <?php foreach ($options as $option) :
                $key = $option->key;
                $name = $option->name;
                if (strpos($key, PERFEX_SAAS_MODULE_NAME)) continue; ?>
                <div class="col-md-4 col-sm-6 col-xs-12 item">
                    <div class="tw-flex form-group share-row">
                        <label class="tw-capitalize text-capitalize" data-toggle="tooltip" data-title="<?= $key; ?>">
                            <div class="checkbox checkbox-inline share-checkbox">
                                <input type="checkbox" name="settings[perfex_saas_sensitive_options][]" <?= in_array($key, $selected_options) ? 'checked' : ''; ?> value="<?= $key ?>" />
                                <!-- ensure white space between the label -->
                                <label class="tw-capitalize"> </label>
                            </div>
                            <?= $name ?>
                        </label>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</div>


<!-- shared tables selection for setting up tenants -->
<div class="tw-mt-10 tw-mb-10 tables">
    <?php $tables = get_instance()->db->list_tables(); ?>
    <?php
    $dbprefix = perfex_saas_master_db_prefix();
    $default_tables = array_keys(perfex_saas_default_seed_tables());
    $selected_tables = get_option('perfex_saas_tenants_seed_tables');
    $selected_tables = empty($selected_tables) ? $default_tables : (array)json_decode($selected_tables);
    $restricted_tables = [$dbprefix . 'options', $dbprefix . 'sessions', $dbprefix . 'activity_log', $dbprefix . 'vault', $dbprefix . 'staff'];
    ?>
    <button type="button" class="btn btn-danger pull-right" onclick="perfexSaasResetSeedSelection()"><?= _l('perfex_saas_reset_to_default'); ?></button>
    <label for="perfex_saas_filter w-full">
        <?= _l('perfex_saas_settings_tenants_seed'); ?>
    </label>
    <br />
    <small class="text-danger"><?= _l('perfex_saas_settings_tenants_seed_hint'); ?></small>
    <input type="text" class="form-control tw-mb-2 tw-mt-4 perfex_saas_filter" placeholder="<?= _l('perfex_saas_filter_tenants_seed'); ?>" />

    <div class="perfex_saas_filterables tw-overflow-y-auto" style="height:50vh">
        <div class="row w-ml-1 tw-mr-1">
            <?php foreach ($tables as $table) :
                if (in_array($table, $restricted_tables) || !str_starts_with($table, $dbprefix)) continue;
                $key = $table;
                $name = str_ireplace([$dbprefix, '_'], ["", " "], $table); ?>
                <div class="col-md-4 col-sm-6 col-xs-12 item">
                    <div class="tw-flex form-group share-row">
                        <label class="tw-capitalize text-capitalize" data-toggle="tooltip" data-title="<?= $key; ?>">
                            <div class="checkbox checkbox-inline share-checkbox">
                                <input type="checkbox" name="settings[perfex_saas_tenants_seed_tables][]" <?= in_array($key, $selected_tables) ? 'checked' : ''; ?> value="<?= $key ?>" />
                                <!-- ensure white space between the label -->
                                <label class="tw-capitalize"> </label>
                            </div>
                            <?= $name ?>
                        </label>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</div>

<script>
    "use strict";

    function perfexSaasResetSeedSelection() {
        let defautlTables = <?= json_encode($default_tables); ?>;
        $('.perfex_saas_filterables input:checkbox').prop('checked', false);
        for (let i = 0; i < defautlTables.length; i++) {
            const table = defautlTables[i];
            $(`.perfex_saas_filterables input:checkbox[value=${table}]`).prop('checked');
        }
    }

    function perfexSaasResetSeedOptionsSelection() {
        let defaultOptions = <?= json_encode($default_options); ?>;
        $(this).attr("disabled", "disabled");
        $('.options .perfex_saas_filterables input:checkbox').prop('checked', false);
        for (let i = 0; i < defaultOptions.length; i++) {
            const option = defaultOptions[i];
            $(`.options .perfex_saas_filterables input:checkbox[value=${option}]`).prop('checked', true);
        }
    }
</script>