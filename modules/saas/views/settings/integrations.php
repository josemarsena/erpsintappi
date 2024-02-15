<?php

defined('BASEPATH') or exit('No direct script access allowed');
?>

<div class="tw-flex tw-flex-col">

    <!-- Cpanel settings-->
    <h3><?= _l('perfex_saas_cpanel_settings'); ?></h3>
    <p class="tw-mb-4"><?= _l('perfex_saas_cpanel_settings_hint'); ?></p>
    <div class="row mtop25 tw-mb-4">
        <div class="col-md-3 border-right">
            <span><?php echo _l('perfex_saas_enabled?'); ?></span>
        </div>
        <div class="col-md-2">
            <div class="onoffswitch">
                <input type="checkbox" id="perfex_saas_cpanel_enabled" data-perm-id="3" class="onoffswitch-checkbox"
                    <?php if (get_option('perfex_saas_cpanel_enabled') == '1') {
                                                                                                                            echo 'checked';
                                                                                                                        } ?> value="1" name="settings[perfex_saas_cpanel_enabled]">
                <label class="onoffswitch-label" for="perfex_saas_cpanel_enabled"></label>
            </div>
        </div>
    </div>
    <?php
    $CI = &get_instance();
    $fields = ['perfex_saas_cpanel_login_domain', 'perfex_saas_cpanel_port', 'perfex_saas_cpanel_username', 'perfex_saas_cpanel_password'];
    $encrypted_fields = ['perfex_saas_cpanel_password'];
    $attrs = [
        'perfex_saas_cpanel_password' => ['type' => 'password'],
        'perfex_saas_cpanel_port' => ['data-default-value' => '2083']
    ];

    foreach ($fields as $key => $field) {
        $value = get_option($field);
        if (empty($value)) {
            $value = $attrs[$field]['data-default-value'] ?? '';
        }

        if (!empty($value) && in_array($field, $encrypted_fields))
            $value = $CI->encryption->decrypt($value);

        $label = _l($field) . perfex_saas_form_label_hint($field . '_hint');
        echo render_input('settings[' . $field . ']', $label, $value, $attrs[$field]['type'] ?? 'text', $attrs[$field] ?? []);
    } ?>

    <div class="tw-flex tw-justify-end">
        <button onclick="testCpanelIntegration()" class="btn btn-danger btn-sm"
            type="button"><?= _l('perfex_saas_test'); ?></button>
    </div>

    <?php $addondoamin_enabled = get_option('perfex_saas_cpanel_enable_addondomain') == '1'; ?>
    <div class="row mtop25 tw-mb-4">
        <div class="col-md-3 border-right">
            <span><?php echo _l('perfex_saas_cpanel_enable_addondomain'); ?></span>
        </div>
        <div class="col-md-2">
            <div class="onoffswitch">
                <input onchange="$('#addondomain_deps').toggleClass('hidden');" type="checkbox"
                    id="perfex_saas_cpanel_enable_addondomain" data-perm-id="3" class="onoffswitch-checkbox"
                    <?php if ($addondoamin_enabled) {
                                                                                                                                                                                                echo 'checked';
                                                                                                                                                                                            } ?> value="1"
                    name="settings[perfex_saas_cpanel_enable_addondomain]">
                <label class="onoffswitch-label" for="perfex_saas_cpanel_enable_addondomain"></label>
            </div>
        </div>
        <div class="col-md-12 text-warning"><?= _l('perfex_saas_cpanel_enable_addondomain_hint'); ?></div>
    </div>

    <div id="addondomain_deps" class="<?= $addondoamin_enabled ? '' : 'hidden'; ?>">
        <?php
        $fields = ['perfex_saas_cpanel_primary_domain', 'perfex_saas_cpanel_document_root'];
        $attrs = [
            'perfex_saas_cpanel_primary_domain' => ['data-default-value' => perfex_saas_get_saas_default_host()],
            'perfex_saas_cpanel_document_root' => ['data-default-value' => FCPATH],
        ];

        foreach ($fields as $key => $field) {
            $value = get_option($field);
            if (empty($value)) {
                $value = $attrs[$field]['data-default-value'] ?? '';
            }
            $label = _l($field) . perfex_saas_form_label_hint($field . '_hint');
            echo render_input('settings[' . $field . ']', $label, $value, $attrs[$field]['type'] ?? 'text', $attrs[$field] ?? []);
        } ?>
    </div>
    <div class="tw-mt-4 tw-mb-4">
        <hr />
    </div>

</div>

<script>
"use strict";

function testCpanelIntegration() {
    const data = {};

    $("#integrations input").each(function() {
        data[this.name] = this.value;
    });
    $("#integrations button").attr('disabled', true);
    $.post(admin_url + 'perfex_saas/packages/test_cpanel', data)
        .done(function(response) {
            $("#integrations button").removeAttr('disabled');
            response = JSON.parse(response);
            alert_float(response.status, response.message, 10000);
        }).fail(function(error) {
            $("#integrations button").removeAttr('disabled');
        });
}
</script>