<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Helpdesk
Description: Gestão de Usuários com Contrato
Version: 1.0.2
Requires at least: 1.0.*
Author: I3Software
Author URI: https://i3software.i3c.com.br
*/

// Define o nome do Módulo
define('HELPDESK_MODULE_NAME', 'helpdesk');
// Define a pasta para Uploads de Arquivo
define('HELPDESK_MODULE_UPLOAD_FOLDER', module_dir_path(HELPDESK_MODULE_NAME, 'uploads'));
// Define Pasta Importar Itens com Erro
define('HELPDESK_IMPORT_ITEM_ERROR', 'modules/helpdesk/uploads/import_item_error/');
// Define a Pasta para Erros
define('HELPDESK_ERROR', FCPATH );
// Define a Pasta de Exportação para o Excel
define('HELPDESK_EXPORT_XLSX', 'modules/helpdesk/uploads/excel/');

// Ganchos Iniciais
// Carrega a funcção helpdesk_add_head_component
hooks()->add_action('app_admin_head', 'helpdesk_add_head_component');
// Carrega a função helpdesk_load_js
hooks()->add_action('app_admin_footer', 'helpdesk_load_js');
// Carrega a função helpdesk_module_init_menu_items
hooks()->add_action('admin_init', 'helpdesk_module_init_menu_items');
// Carrega a função helpdesk_permissions
hooks()->add_action('admin_init', 'helpdesk_permissions');

define('HELPDESK_REVISION', 102);


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
* Itens de menu do módulo Helpdesk na configuração no gancho admin_init
 * @return null
 */
function helpdesk_module_init_menu_items()
{
    $CI = &get_instance();


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
                'href'     => admin_url('helpdesk/index/1'),
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

        if (has_permission('helpdesk_credenciais', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('helpdesk', [
                'slug'     => 'helpdesk_credenciais',
                'name'     => _l('credenciais'),
                'icon'     => 'fa fa-shield',
                'href'     => admin_url('helpdesk/credenciais'),
                'position' => 3,
            ]);
        }

        if (has_permission('helpdesk_ticket', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('helpdesk', [
                'slug'     => 'helpdesk_ticket',
                'name'     => _l('ticket'),
                'icon'     => 'fa fa-ticket',
                'href'     => admin_url('helpdesk/adicionar'),
                'position' => 4,
            ]);
        }
		
		if (has_permission('helpdesk_suporteremoto', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('helpdesk', [
                'slug'     => 'helpdesk_suporteremoto',
                'name'     => _l('suporteremoto'),
                'icon'     => 'fa fa-life-ring',
                'href'     => admin_url('helpdesk/suporteremoto'),
                'position' => 5,
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
