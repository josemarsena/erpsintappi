<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: ServiceDesk
Description: Serviços de TI
Version: 1.0.2
Requires at least: 1.0.*
Author: Synglia
Author URI: https://synglia.com.br
*/


define('SERVICEDESK_MODULE_NAME', 'servicedesk');
define('SERVICEDESK_MODULE_UPLOAD_FOLDER', module_dir_path(SERVICEDESK_MODULE_NAME, 'uploads'));
define('SERVICEDESK_IMPORT_ITEM_ERROR', 'modules/servicedesk/uploads/import_item_error/');
define('SERVICEDESK_ERROR', FCPATH );
define('SERVICEDESK_EXPORT_XLSX', 'modules/servicedesk/uploads/export_xlsx/');

hooks()->add_action('app_admin_head', 'servicedesk_add_head_component');
hooks()->add_action('app_admin_footer', 'servicedesk_load_js');
hooks()->add_action('admin_init', 'servicedesk_module_init_menu_items');
hooks()->add_action('admin_init', 'servicedesk_permissions');

define('SERVICEDESK_REVISION', 101);


/**
 * *
 *  Registrar hook de ativação do módulo
 */

register_activation_hook(SERVICEDESK_MODULE_NAME, 'servicedesk_module_activation_hook');

/**
 * Registre arquivos de idioma, deve ser registrado se o módulo estiver usando idiomas
 */
register_language_files(SERVICEDESK_MODULE_NAME, [SERVICEDESK_MODULE_NAME]);

/**
 * Gancho (hook) de Ativação ONline
 */
function servicedesk_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}


/**
 * init add head component
 */
function servicedesk_add_head_component()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];
}

/**
 * init add footer component
 */
 

function servicedesk_load_js()
{
    $CI          = &get_instance();
    $viewuri     = $_SERVER['REQUEST_URI'];
    $mediaLocale = get_media_locale();

 
    if (!(strpos($viewuri, '/admin/servicedesk/dashboard') === false))
	{
		echo '<script src="' . module_dir_url(SERVICEDESK_MODULE_NAME, 'assets/plugins/highcharts/highcharts.js') . '"></script>';
		echo '<script src="' . module_dir_url(SERVICEDESK_MODULE_NAME, 'assets/plugins/highcharts/modules/variable-pie.js') . '"></script>';
		echo '<script src="' . module_dir_url(SERVICEDESK_MODULE_NAME, 'assets/plugins/highcharts/modules/export-data.js') . '"></script>';
		echo '<script src="' . module_dir_url(SERVICEDESK_MODULE_NAME, 'assets/plugins/highcharts/modules/accessibility.js') . '"></script>';
		echo '<script src="' . module_dir_url(SERVICEDESK_MODULE_NAME, 'assets/plugins/highcharts/modules/exporting.js') . '"></script>';
		echo '<script src="' . module_dir_url(SERVICEDESK_MODULE_NAME, 'assets/plugins/highcharts/highcharts-3d.js') . '"></script>';
	}
}


/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function servicedesk_module_init_menu_items()
{
    $CI = &get_instance();



    if (has_permission('servicedesk_dashboard', '', 'view') || has_permission('servicedesk_report', '', 'view') || has_permission('servicedesk_setting', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('servicedesk', [
            'name'     => _l('als_servicedesk'),
            'icon'     => 'fa fa-usd',
            'position' => 14,
        ]);

        if (has_permission('servicedesk_dashboard', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('servicedesk', [
                'slug'     => 'servicedesk_dashboard',
                'name'     => _l('dashboard'),
                'icon'     => 'fa fa-home',
                'href'     => admin_url('servicedesk/dashboard'),
                'position' => 1,
            ]);
        }

        if (has_permission('servicedesk_ordemservico', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('servicedesk', [
                'slug'     => 'servicedesk_ordemservico',
                'name'     => _l('ordemservico'),
                'icon'     => 'fa fa-piggy',
                'href'     => admin_url('servicedesk/ordemservico'),
                'position' => 2,
            ]);
        }


        if (has_permission('servicedesk_manutencaopreventiva', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('servicedesk', [
                'slug'     => 'servicedesk_manutencaopreventiva',
                'name'     => _l('manutencaopreventiva'),
                'icon'     => 'fa money-check-dollar',
                'href'     => admin_url('servicedesk/manutencaopreventiva'),
                'position' => 3,
            ]);
        }
		
		if (has_permission('servicedesk_ordemfabricacao', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('servicedesk', [
                'slug'     => 'servicedesk_ordemfabricacao',
                'name'     => _l('ordemfabricacao'),
                'icon'     => 'fa fa-list-ol',
                'href'     => admin_url('servicedesk/ordemfabricacao'),
                'position' => 4,
            ]);
        }

		if (has_permission('servicedesk_checklist', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('servicedesk', [
                'slug'     => 'servicedesk_checklist',
                'name'     => _l('checklist'),
                'icon'     => 'fa file-invoice-dollar',
                'href'     => admin_url('servicedesk/checklist'),
                'position' => 5,
            ]);
        }

		if (has_permission('servicedesk_monitoramento', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('servicdesk', [
                'slug'     => 'servicedesk_monitoramento',
                'name'     => _l('monitoramento'),
                'icon'     => 'fa file-invoice',
                'href'     => admin_url('servicedesk/monitoramento'),
                'position' => 6,
            ]);
        }
		
		if (has_permission('servicedesk_backup', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('servicedesk', [
                'slug'     => 'servicedesk_backup',
                'name'     => _l('backup'),
                'icon'     => 'fa money-check-dollar',
                'href'     => admin_url('servicedesk/backup'),
                'position' => 7,
            ]);
        }

    }
}

/**
 * Inicia as permissões do módulo de Financeiro na configuração do hook admin_init
 */
function servicedesk_permissions() {

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
    ];
    register_staff_capabilities('servicedesk_dashboard', $capabilities, _l('servicedesk_dashboard'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];


    register_staff_capabilities('servicedesk_report', $capabilities, _l('servicedesk_report'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'edit'   => _l('permission_edit'),
    ];
    register_staff_capabilities('servicedesk_setting', $capabilities, _l('servicedesk_setting'));
}
