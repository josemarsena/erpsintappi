<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Show options for notifications in Setup->Settings->Whatsapp Notifications
 */

$module_enabled = get_option(PERFEX_MOBILE_APP_API . '_enabled'); ?>
<div class="form-group">
    <label for="enable_option" class="control-label clearfix">
        <?php echo _l('module_enable_option'); ?>
    </label>
    <div class="radio radio-primary radio-inline">
        <input type="radio" id="y_opt_1_whatsapp_notification_enable_chat" name="settings[".PERFEX_MOBILE_APP_API."_enabled]" value="1" <?= ($module_enabled == '1') ? ' checked' : '' ?>>
        <label for="y_opt_1_whatsapp_notification_enable_chat"><?php echo _l('settings_yes'); ?></label>
    </div>
    <div class="radio radio-primary radio-inline">
        <input type="radio" id="y_opt_2_whatsapp_notification_enable_chat" name="settings[".PERFEX_MOBILE_APP_API."_enabled]" value="0" <?= ($module_enabled == '0') ? ' checked' : '' ?>>
        <label for="y_opt_2_whatsapp_notification_enable_chat">
            <?php echo _l('settings_no'); ?>
        </label>
    </div>
</div>