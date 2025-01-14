<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: CRM
Description: Relacionamento com o Cliente
Version: 1.0.1
Requires at least: 1.0.*
Author: I3Software
Author URI: https://i3software.i3c.com.br
*/


define('CRM_MODULE_NAME', 'crm');
define('CRM_MODULE_UPLOAD_FOLDER', module_dir_path(CRM_MODULE_NAME, 'uploads'));
define('CRM_IMPORT_ITEM_ERROR', 'modules/crm/uploads/import_item_error/');
define('CRM_ERROR', FCPATH );
define('CRM_EXPORT_XLSX', 'modules/crm/uploads/export_xlsx/');

hooks()->add_action('app_admin_head', 'crm_add_head_component');
hooks()->add_action('app_admin_footer', 'crm_load_js');
hooks()->add_action('admin_init', 'crm_module_init_menu_items');
hooks()->add_action('admin_init', 'crm_permissions');

define('CRM_REVISION', 101);


/**
 * *
 *  Registrar hook de ativação do módulo
 */

register_activation_hook(CRM_MODULE_NAME, 'crm_module_activation_hook');

/**
 * Registre arquivos de idioma, deve ser registrado se o módulo estiver usando idiomas
 */
register_language_files(CRM_MODULE_NAME, [CRM_MODULE_NAME]);

/**
 * Gancho (hook) de Ativação ONline
 */
function crm_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}


/**
 * init add head component
 */
function crm_add_head_component()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    // Inicializa e cria o Cabeçalho do Modulo Dashboard/CRM
    if(!(strpos($viewuri,'admin/financeiro/dashboard') === false)){
        echo '<link href="' . module_dir_url(CRM_MODULE_NAME, 'assets/css/box_loading.css') . '?v=' . CRM_REVISION. '"  rel="stylesheet" type="text/css" />';
        echo '<link href="' . module_dir_url(CRM_MODULE_NAME, 'assets/css/dashboard.css') . '?v=' . CRM_REVISION. '"  rel="stylesheet" type="text/css" />';
    }
}

/**
 * init add footer component
 */
 

function crm_load_js()
{
    $CI          = &get_instance();
    $viewuri     = $_SERVER['REQUEST_URI'];
    $mediaLocale = get_media_locale();

 
    if (!(strpos($viewuri, '/admin/financeiro/dashboard') === false)) 
	{
		echo '<script src="' . module_dir_url(CRM_MODULE_NAME, 'assets/plugins/highcharts/highcharts.js') . '"></script>';
		echo '<script src="' . module_dir_url(CRM_MODULE_NAME, 'assets/plugins/highcharts/modules/variable-pie.js') . '"></script>';
		echo '<script src="' . module_dir_url(CRM_MODULE_NAME, 'assets/plugins/highcharts/modules/export-data.js') . '"></script>';
		echo '<script src="' . module_dir_url(CRM_MODULE_NAME, 'assets/plugins/highcharts/modules/accessibility.js') . '"></script>';
		echo '<script src="' . module_dir_url(CRM_MODULE_NAME, 'assets/plugins/highcharts/modules/exporting.js') . '"></script>';
		echo '<script src="' . module_dir_url(CRM_MODULE_NAME, 'assets/plugins/highcharts/highcharts-3d.js') . '"></script>';
	}
}


/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function crm_module_init_menu_items()
{
    $CI = &get_instance();


// Criar o itens do Menu
// Contas a Pagar/Pagas	
// Contas a Receber/Recebidas
// Dashboard
// Plano de Contas Financeiro
// Bancos
// Contas Bancárias
// Cartões de Crédito
// Seleção de Pagamento
// Fluxo de Caixa Financeiro
// Pagamentos


    if (has_permission('crm_dashboard', '', 'view') || has_permission('crmreport', '', 'view') || has_permission('crm_setting', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('crm', [
            'name'     => _l('als_crm'),
            'icon'     => 'fa fa-ambulance ',
            'position' => 13,
        ]);

        if (has_permission('crm_dashboard', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('crm', [
                'slug'     => 'crm_dashboard',
                'name'     => _l('dashboard'),
                'icon'     => 'fa fa-home',
                'href'     => admin_url('crm/dashboard'),
                'position' => 1,
            ]);
        }

    }
}

/**
 * Inicia as permissões do módulo de Financeiro na configuração do hook admin_init
 */
function crm_permissions() {

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
    ];
    register_staff_capabilities('crm_dashboard', $capabilities, _l('crm_dashboard'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];


    register_staff_capabilities('crm_report', $capabilities, _l('crm_report'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'edit'   => _l('permission_edit'),
    ];
    register_staff_capabilities('crm_setting', $capabilities, _l('crm_setting'));
}
