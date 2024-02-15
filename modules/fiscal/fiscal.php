<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Gestão Fiscal
Description: Gerencia a Eissão de NFe/NFS-e, CT-e, etc, etc
Version: 1.0.1
Requires at least: 1.0.*
Author: I3Software
Author URI: https://i3software.i3c.com.br
*/


define('FISCAL_MODULE_NAME', 'fiscal');
define('FISCALF_MODULE_UPLOAD_FOLDER', module_dir_path(FISCAL_MODULE_NAME, 'uploads'));
define('FISCAL_IMPORT_ITEM_ERROR', 'modules/fiscal/uploads/import_item_error/');
define('FISCAL_ERROR', FCPATH );
define('FISCAL_EXPORT_XLSX', 'modules/fiscal/uploads/export_xlsx/');

hooks()->add_action('app_admin_head', 'fiscal_add_head_component');
hooks()->add_action('app_admin_footer', 'fiscal_load_js');
hooks()->add_action('admin_init', 'fiscal_module_init_menu_items');
hooks()->add_action('admin_init', 'fiscal_permissions');

define('fiscal_REVISION', 101);


/**
 * *
 *  Registrar hook de ativação do módulo
 */

register_activation_hook(FISCAL_MODULE_NAME, 'fiscal_module_activation_hook');

/**
 * Registre arquivos de idioma, deve ser registrado se o módulo estiver usando idiomas
 */
register_language_files(FISCAL_MODULE_NAME, [FISCAL_MODULE_NAME]);

/**
 * Gancho (hook) de Ativação ONline
 */
function fiscal_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}


/**
 * init add head component
 */
function fiscal_add_head_component()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    // Inicializa e cria o Cabeçalho do Modulo Dashboard/FATURAMENTO
    if(!(strpos($viewuri,'admin/fiscal/dashboard') === false)){
        echo '<link href="' . module_dir_url(FISCAL_MODULE_NAME, 'assets/css/box_loading.css') . '?v=' . FISCAL_REVISION. '"  rel="stylesheet" type="text/css" />';
        echo '<link href="' . module_dir_url(FISCAL_MODULE_NAME, 'assets/css/dashboard.css') . '?v=' . FISCAL_REVISION. '"  rel="stylesheet" type="text/css" />';
    }
}

/**
 * init add footer component
 */
 

function fiscal_load_js()
{
    $CI          = &get_instance();
    $viewuri     = $_SERVER['REQUEST_URI'];
    $mediaLocale = get_media_locale();

 
    if (!(strpos($viewuri, '/admin/FISCAL/dashboard') === false)) 
	{
		echo '<script src="' . module_dir_url(FISCAL_MODULE_NAME, 'assets/plugins/highcharts/highcharts.js') . '"></script>';
		echo '<script src="' . module_dir_url(FISCAL_MODULE_NAME, 'assets/plugins/highcharts/modules/variable-pie.js') . '"></script>';
		echo '<script src="' . module_dir_url(FISCAL_MODULE_NAME, 'assets/plugins/highcharts/modules/export-data.js') . '"></script>';
		echo '<script src="' . module_dir_url(FISCAL_MODULE_NAME, 'assets/plugins/highcharts/modules/accessibility.js') . '"></script>';
		echo '<script src="' . module_dir_url(FISCAL_MODULE_NAME, 'assets/plugins/highcharts/modules/exporting.js') . '"></script>';
		echo '<script src="' . module_dir_url(FISCAL_MODULE_NAME, 'assets/plugins/highcharts/highcharts-3d.js') . '"></script>';
	}
}


/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function fiscal_module_init_menu_items()
{
    $CI = &get_instance();


// Criar o itens do Menu
// Contas a Pagar/Pagas	
// Contas a Receber/Recebidas
// Dashboard
// Plano de Contas FATURAMENTO
// Bancos
// Contas Bancárias
// Cartões de Crédito
// Seleção de Pagamento
// Fluxo de Caixa FATURAMENTO
// Pagamentos



    if (has_permission('fiscal_dashboard', '', 'view') || has_permission('fiscal_report', '', 'view') || has_permission('fiscal_setting', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('faturamento', [
            'name'     => _l('als_FATURAMENTO'),
            'icon'     => 'fa fa-usd',
            'position' => 12,
        ]);

        if (has_permission('fiscal_dashboard', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_dashboard',
                'name'     => _l('dashboard'),
                'icon'     => 'fa fa-home',
                'href'     => admin_url('faturamento/dashboard'),
                'position' => 1,
            ]);
        }

        if (has_permission('fiscal_bancos', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_bancos',
                'name'     => _l('bancos'),
                'icon'     => 'fa fa-piggy',
                'href'     => admin_url('faturamento/bancos'),
                'position' => 2,
            ]);
        }


        if (has_permission('fiscal_contasbancarias', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_contasbancarias',
                'name'     => _l('contasbancarias'),
                'icon'     => 'fa money-check-dollar',
                'href'     => admin_url('faturamento/contasbancarias'),
                'position' => 3,
            ]);
        }
		
		if (has_permission('FISCAL_planocontas', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'FISCAL_planocontas',
                'name'     => _l('planocontas'),
                'icon'     => 'fa fa-list-ol',
                'href'     => admin_url('faturamento/planocontas'),
                'position' => 4,
            ]);
        }

		if (has_permission('fiscal_contaspagar', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_contaspagar',
                'name'     => _l('contaspagar'),
                'icon'     => 'fa file-invoice-dollar',
                'href'     => admin_url('faturamento/contaspagar'),
                'position' => 5,
            ]);
        }

		if (has_permission('fiscal_contasreceber', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_contasreceber',
                'name'     => _l('contasreceber'),
                'icon'     => 'fa file-invoice',
                'href'     => admin_url('faturamento/contasreceber'),
                'position' => 6,
            ]);
        }
		
		if (has_permission('fiscal_selecaopagamento', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_selecacopagamento',
                'name'     => _l('selecaopagamento'),
                'icon'     => 'fa money-check-dollar',
                'href'     => admin_url('faturamento/selecaopagamento'),
                'position' => 7,
            ]);
        }

		if (has_permission('fiscal_faturas', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_faturas',
                'name'     => _l('faturas'),
                'icon'     => 'fa fa-file-invoice',
                'href'     => admin_url('faturamento/faturas'),
                'position' => 8,
            ]);
        }		

		if (has_permission('fiscal_fluxocaixa', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_fluxocaixa',
                'name'     => _l('fluxocaixa'),
                'icon'     => 'fa fa-line-chart',
                'href'     => admin_url('faturamento/fluxocaixa'),
                'position' => 9,
            ]);
        }		
		
		if (has_permission('fiscal_conciliacao', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_conciliacao',
                'name'     => _l('conciliacaobancaria'),
                'icon'     => 'fa fa-line-chart',
                'href'     => admin_url('faturamento/conciliacaobancaria'),
                'position' => 10,
            ]);
        }	

		if (has_permission('fiscal_budget', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_relatorios',
                'name'     => _l('budget'),
                'icon'     => 'fa fa-line-chart',
                'href'     => admin_url('faturamento/budget'),
                'position' => 11,
            ]);
        }			
		
		if (has_permission('fiscal_relatorios', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_relatorios',
                'name'     => _l('relatoriosfaturamentos'),
                'icon'     => 'fa fa-line-chart',
                'href'     => admin_url('faturamento/relatorios'),
                'position' => 12,
            ]);
        }		

		
        if (has_permission('fiscal_configuracoes', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('faturamento', [
                'slug'     => 'fiscal_configuracoes',
                'name'     => _l('configuracoes'),
                'icon'     => 'fa fa-cog',
                'href'     => admin_url('faturamento/configuracoes'),
                'position' => 13,
            ]);
        }
    }
}

/**
 * Inicia as permissões do módulo de FATURAMENTO na configuração do hook admin_init
 */
function fiscal_permissions() {

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
    ];
    register_staff_capabilities('fiscal_dashboard', $capabilities, _l('fiscal_dashboard'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];


    register_staff_capabilities('fiscal_report', $capabilities, _l('fiscal_report'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'edit'   => _l('permission_edit'),
    ];
    register_staff_capabilities('fiscal_setting', $capabilities, _l('fiscal_setting'));
}
