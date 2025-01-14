<?php

$client_plan = getClientPlan($client->userid);

if (SS_PERFEX_VERSION) {
    // Perfex version 3.2.0 dependency : Start
    $tabs = $this->app->get_settings_sections();
    $flattenedData = [];
    $flattenedData = array_reduce(array_column($tabs, 'children'), function ($carry, $subArray) {
        return array_merge($carry, $subArray);
    }, []);

    $settings_tabs = array_map(function ($key, $value) {
        if(in_array($value['id'], ["saas"])){
            return [];
        }
        return ['key' => $value['name'], 'value' => $value['view']];
    }, array_keys($flattenedData), $flattenedData);
    // Perfex version 3.2.0 dependency : Over
} else {
    $tabs = $this->app_tabs->get_settings_tabs();
    $settings_tabs = array_map(function ($key, $value) {
        if(in_array($value['slug'], ["saas"])){
            return [];
        }
        return ['key' => $value['name'], 'value' => $value['view']];
    }, array_keys($tabs), $tabs);
}

$settings_tabs = array_filter($settings_tabs);
?>
<?= render_select('settings', $settings_tabs, ['value', 'key'], 'settings'); ?>
<hr class="hr-panel-separator">
<?php echo form_open_multipart(admin_url('saas/superadmin/save_tenant_setting/' . $client->userid), ['id' => 'tanent_settings_form']) ?>
<div id="settings_view"></div>
<hr class="hr-panel-separator">
<button type="submit" class="btn btn-primary"><?= _l('save_settings') ?></button>
<?php echo form_close(); ?>

<script>
    var userid = "<?php echo $client->userid; ?>";
    var tenants_name = "<?php echo $client_plan->tenants_name; ?>";
</script>
<script defer src="<?php echo module_dir_url(SUPERADMIN_MODULE, 'assets/js/saas_client.bundle.js') . '?v=' . $this->app_scripts->core_version(); ?>"></script>
