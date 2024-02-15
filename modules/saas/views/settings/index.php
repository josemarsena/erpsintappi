<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>

<div class="horizontal-scrollable-tabs panel-full-width-tabs">
    <div class="scroller arrow-left tw-mt-px"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right tw-mt-px"><i class="fa fa-angle-right"></i></div>
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
            <?php
            // Define tab data as an array for better maintainability
            $tabs = [
                ['id' => 'general', 'label' => _l('settings_group_general')],
                ['id' => 'tenants_seed', 'label' => _l('perfex_saas_tenants_seed')],
                ['id' => 'modules', 'label' => _l('perfex_saas_settings_modules')],
                ['id' => 'integrations', 'label' => _l('perfex_saas_settings_integrations')],
            ];

            // Loop through the tabs to generate navigation
            foreach ($tabs as $tab) {
                $isActive = ($tab['id'] === 'general') ? 'active' : '';
            ?>
                <li role="presentation" class="<?php echo $isActive; ?>">
                    <a href="#<?php echo $tab['id']; ?>" aria-controls="<?php echo $tab['id']; ?>" role="tab" data-toggle="tab">
                        <?php echo $tab['label']; ?>
                    </a>
                </li>
            <?php
            }
            ?>
        </ul>
    </div>
</div>

<div class="tab-content mtop30">
    <?php
    // Define an array of tab content files for better organization
    $tabContents = [
        'general' => 'general.php',
        'tenants_seed' => 'tenants_seed.php',
        'modules' => 'modules.php',
        'integrations' => 'integrations.php',
    ];

    // Loop through the tab content files
    foreach ($tabContents as $tabId => $tabFile) {
        $isActive = ($tabId === 'general') ? 'active' : '';
    ?>
        <div role="tabpanel" class="tab-pane <?php echo $isActive; ?>" id="<?php echo $tabId; ?>">
            <?php require($tabFile); ?>
        </div>
    <?php
    }
    ?>
</div>