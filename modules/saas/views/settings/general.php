<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('emails_model');

$reserved_slugs = get_option('perfex_saas_reserved_slugs');

$label2 = _l('perfex_saas_reserved_slugs') . perfex_saas_form_label_hint('perfex_saas_reserved_slugs_hint');
$email_templates = $CI->emails_model->get(["`slug` LIKE" => "company-instance%", 'language' => 'english'], 'result');
?>

<div class="tw-flex tw-flex-col">

    <!-- dont add 'settings' array, will be added by the function 'render_yes_no_option' -->
    <?php render_yes_no_option('perfex_saas_enable_single_package_mode', _l('perfex_saas_enable_single_package_mode'), _l('perfex_saas_enable_single_package_mode_hint')); ?>
    <?php render_yes_no_option('perfex_saas_enable_auto_trial', _l('perfex_saas_enable_auto_trial'), _l('perfex_saas_enable_auto_trial_hint')); ?>
    <?php render_yes_no_option('perfex_saas_autocreate_first_company', _l('perfex_saas_autocreate_first_company'), _l('perfex_saas_autocreate_first_company_hint')); ?>
    <div class="tw-mt-4 tw-mb-4">
        <hr />
    </div>
    <?php render_yes_no_option('perfex_saas_control_client_menu', _l('perfex_saas_control_client_menu'), _l('perfex_saas_control_client_menu_hint')); ?>
    <?php render_yes_no_option('perfex_saas_enable_preloader', _l('perfex_saas_enable_preloader'), _l('perfex_saas_enable_preloader_hint')); ?>

    <div class="tw-mt-4 tw-mb-4">
        <hr />
    </div>

    <?php echo render_input('settings[perfex_saas_reserved_slugs]', $label2, empty($reserved_slugs) ? 'www,app,deal,controller,master,ww3,hack' : $reserved_slugs); ?>

    <div class="tw-mt-4 tw-mb-4">
        <hr />
    </div>

    <?php render_yes_no_option('perfex_saas_enable_subdomain_input_on_signup_form', _l('perfex_saas_enable_subdomain_input_on_signup_form')); ?>
    <?php render_yes_no_option('perfex_saas_enable_customdomain_input_on_signup_form', _l('perfex_saas_enable_customdomain_input_on_signup_form')); ?>

    <div class="tw-mt-4 tw-mb-4">
        <hr />
    </div>
    <div class="form-group">
        <h4><?= _l('perfex_saas_landing_page_settings'); ?>:</h4>
        <br />
        <div class="proxy row">
            <div class="col-sm-8">
                <?php $value = get_option('perfex_saas_landing_page_url'); ?>
                <?= render_input('settings[perfex_saas_landing_page_url]', _l('perfex_saas_landing_page_url') . perfex_saas_form_label_hint('perfex_saas_landing_page_url_hint'), $value, 'text', ['placeholder' => 'https://mycrm.com/home']); ?>
            </div>
            <div class="col-sm-4">
                <?php $value = get_option('perfex_saas_landing_page_url_mode'); ?>
                <?= render_select('settings[perfex_saas_landing_page_url_mode]', [['key' => 'proxy'], ['key' => 'redirection']], ['key', ['key']], _l('perfex_saas_landing_page_url_mode') . perfex_saas_form_label_hint('perfex_saas_landing_page_url_mode_hint'), empty($value) ? 'proxy' : $value); ?>
            </div>
        </div>
        <div class="tw-mt-5">
            <div class="w-full text-center tw-text-xl"><strong><?= _l('perfex_saas_or'); ?></strong></div>
        </div>
        <div class="row tw-flex">
            <div class="col-sm-8">
                <?= render_select('settings[perfex_saas_landing_page_theme]', perfex_saas_get_landing_pages(), ['file', ['name']], 'perfex_saas_select_active_landing_page', get_option('perfex_saas_landing_page_theme')); ?>
            </div>
            <div class="col-sm-4 tw-flex tw-items-center">
                <a href="<?= admin_url('perfex_saas/landingpage/builder'); ?>"
                    target="_blank"><?= _l('perfex_saas_edit_landing_pages'); ?></a>
            </div>
        </div>
    </div>
    <div class="tw-mt-4 tw-mb-4">
        <hr />
    </div>
    <?php render_yes_no_option('perfex_saas_force_redirect_to_dashboard', _l('perfex_saas_force_redirect_to_dashboard'), _l('perfex_saas_force_redirect_to_dashboard_hint')); ?>
    <div class="tw-mt-4 tw-mb-4">
        <hr />
    </div>
    <?php render_yes_no_option('perfex_saas_enable_client_bridge', _l('perfex_saas_enable_client_bridge'), _l('perfex_saas_enable_client_bridge_hint')); ?>
    <?php render_yes_no_option('perfex_saas_enable_cross_domain_bridge', _l('perfex_saas_enable_cross_domain_bridge'), _l('perfex_saas_enable_cross_domain_bridge_hint')); ?>
    <?php render_yes_no_option('perfex_saas_enable_instance_switch', _l('perfex_saas_enable_instance_switch'), _l('perfex_saas_enable_instance_switch_hint')); ?>
    <div class="tw-mt-4 tw-mb-4">
        <hr />
    </div>
    <div class="">
        <label><?= _l('perfex_saas_email_templates'); ?></label>
        <ul class="tw-mt-4">
            <?php foreach ($email_templates as $t) : ?>
            <li>
                <a href="<?= admin_url('emails/email_template/' . $t->emailtemplateid); ?>" target="_blank">
                    <i class="fa fa-pen"></i><!-- <i class="fa fa-external-link"></i>--> <?= $t->name ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>