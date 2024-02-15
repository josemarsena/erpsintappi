<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $invoice = get_instance()->perfex_saas_model->get_company_invoice(get_client_user_id()); ?>

<script>
    "use strict";

    const PERFEX_SAAS_MODULE_NAME = '<?= PERFEX_SAAS_MODULE_NAME ?>';
    const PERFEX_SAAS_MAGIC_AUTH_BASE_URL = '<?= base_url('clients/ps_magic/'); ?>';
    const PERFEX_SAAS_DEFAULT_HOST = '<?= perfex_saas_get_saas_default_host(); ?>';
    const PERFEX_SAAS_ACTIVE_SEGMENT = (window.location
            .search.startsWith("?subscription") || window.location
            .search.startsWith("?companies")) ? window.location
        .search : '<?= empty($invoice) ? "?subscription" : "?companies"; ?>';
    const PERFEX_SAAS_CONTROL_CLIENT_MENU = <?= (int)get_option('perfex_saas_control_client_menu'); ?>;
    const PERFEX_SAAS_MAX_SLUG_LENGTH = <?= PERFEX_SAAS_MAX_SLUG_LENGTH; ?>;
</script>

<!-- Load client panel script and style -->
<script src="<?= perfex_saas_asset_url('assets/js/client.js'); ?>"></script>
<link rel="stylesheet" type="text/css" href="<?= perfex_saas_asset_url('assets/css/client.css'); ?>" />

<!-- style control for client menu visibility -->
<?php if ((int)get_option('perfex_saas_control_client_menu')) : ?>
    <style>
        .section-client-dashboard>dl:first-of-type,
        .projects-summary-heading,
        .submenu.customer-top-submenu {
            display: none;
        }
    </style>
<?php endif; ?>

<?php $CI = &get_instance(); ?>

<!-- load client widgets -->
<?php require_once(__DIR__ . '/widgets/index.php'); ?>

<?php if ($CI->session->has_userdata('magic_auth')) : ?>
    <style>
        #wrapper>#content {
            margin-top: 30px
        }
    </style>

    <?php if (isset($GLOBALS['has_outstanding']) && $GLOBALS['has_outstanding']) : ?>
        <script>
            let parent = window.parent;
            if (parent && parent.postMessage) {
                parent.postMessage("closedBridge", "<?= $CI->session->userdata('magic_auth')['source_url'] ?? ''; ?>");
            }
        </script>
    <?php endif; ?>
<?php endif; ?>