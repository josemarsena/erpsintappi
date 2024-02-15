<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Helpdesk
Description: Gestão de Usuários com Contrato
Version: 1.0.1
Requires at least: 1.0.*
Author: I3Software
Author URI: https://i3software.i3c.com.br
*/


define('HELPDESK_MODULE_NAME', 'helpdesk');
define('HELPDESK_MODULE_UPLOAD_FOLDER', module_dir_path(HELPDESK_MODULE_NAME, 'uploads'));
define('HELPDESK_IMPORT_ITEM_ERROR', 'modules/helpdesk/uploads/import_item_error/');
define('HELPDESK_ERROR', FCPATH );
define('HELPDESK_EXPORT_XLSX', 'modules/helpdesk/uploads/export_xlsx/');

hooks()->add_action('app_admin_head', 'helpdesk_add_head_component');
hooks()->add_action('app_admin_footer', 'helpdesk_load_js');
hooks()->add_action('admin_init', 'helpdesk_module_init_menu_items');
hooks()->add_action('admin_init', 'helpdesk_permissions');

define('HELPDESK_REVISION', 101);


/**
 * *
 *  Registrar hook de ativação do módulo
 */

register_activation_hook(HELPDESK_MODULE_NAME, 'helpdesk_module_activation_hook');

/**
 * Registre arquivos de idioma, deve ser registrado se o módulo estiver usando idiomas
 */
register_language_files(HELPDESK_MODULE_NAME, [HELPDESK_MODULE_NAME]);

/**
 * Gancho (hook) de Ativação ONline
 */
function helpdesk_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}


/**
 * init add head component
 */
function helpdesk_add_head_component()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];
}

/**
 * init add footer component
 */
 

function helpdesk_load_js()
{
    $CI          = &get_instance();
    $viewuri     = $_SERVER['REQUEST_URI'];
    $mediaLocale = get_media_locale();

 
    if (!(strpos($viewuri, '/admin/helpdesk/dashboard') === false))
	{
		echo '<script src="' . module_dir_url(HELPDESK_MODULE_NAME, 'assets/plugins/highcharts/highcharts.js') . '"></script>';
		echo '<script src="' . module_dir_url(HELPDESK_MODULE_NAME, 'assets/plugins/highcharts/modules/variable-pie.js') . '"></script>';
		echo '<script src="' . module_dir_url(HELPDESK_MODULE_NAME, 'assets/plugins/highcharts/modules/export-data.js') . '"></script>';
		echo '<script src="' . module_dir_url(HELPDESK_MODULE_NAME, 'assets/plugins/highcharts/modules/accessibility.js') . '"></script>';
		echo '<script src="' . module_dir_url(HELPDESK_MODULE_NAME, 'assets/plugins/highcharts/modules/exporting.js') . '"></script>';
		echo '<script src="' . module_dir_url(HELPDESK_MODULE_NAME, 'assets/plugins/highcharts/highcharts-3d.js') . '"></script>';
	}
}


/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function helpdesk_module_init_menu_items()
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


    if (has_permission('helpdesk_dashboard', '', 'view') || has_permission('helpdesk_report', '', 'view') || has_permission('helpdesk_setting', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('helpdesk', [
            'name'     => _l('als_helpdesk'),
            'icon'     => 'fa fa-ambulance ',
            'position' => 13,
        ]);

        if (has_permission('helpdesk_dashboard', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('helpdesk', [
                'slug'     => 'helpdesk_dashboard',
                'name'     => _l('dashboard'),
                'icon'     => 'fa fa-home',
                'href'     => admin_url('helpdesk/dashboard'),
                'position' => 1,
            ]);
        }

        if (has_permission('helpdesk_inventario', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('helpdesk', [
                'slug'     => 'helpdesk_inventario',
                'name'     => _l('inventario'),
                'icon'     => 'fa fa-ellipsis-v',
                'href'     => admin_url('helpdesk/inventario'),
                'position' => 2,
            ]);
        }


        if (has_permission('helpdesk_ticket', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('helpdesk', [
                'slug'     => 'helpdesk_ticket',
                'name'     => _l('ticket'),
                'icon'     => 'fa fa-ticket',
                'href'     => admin_url('helpdesk/ticket'),
                'position' => 3,
            ]);
        }
		
		if (has_permission('helpdesk_suporteremoto', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('helpdesk', [
                'slug'     => 'helpdesk_suporteremoto',
                'name'     => _l('suporteremoto'),
                'icon'     => 'fa fa-ticket',
                'href'     => admin_url('helpdesk/suporteremoto'),
                'position' => 4,
            ]);
        }
		
    }
}

/**
 * Inicia as permissões do módulo de Financeiro na configuração do hook admin_init
 */
function helpdesk_permissions() {

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
    ];
    register_staff_capabilities('helpdesk_dashboard', $capabilities, _l('helpdesk_dashboard'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];


    register_staff_capabilities('helpdesk_report', $capabilities, _l('helpdesk_report'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'edit'   => _l('permission_edit'),
    ];
    register_staff_capabilities('helpdesk_setting', $capabilities, _l('helpdesk_setting'));
}
