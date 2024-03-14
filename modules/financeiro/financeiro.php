<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Gestão Financeira
Description: Gerenciamento Financeiro da Organização
Version: 1.0.2
Requires at least: 1.0.*
Author: Synglia
Author URI: https://synglia.com.br
*/


define('FINANCEIRO_MODULE_NAME', 'financeiro');
define('FINANCEIRO_MODULE_UPLOAD_FOLDER', module_dir_path(FINANCEIRO_MODULE_NAME, 'uploads'));
define('FINANCEIRO_IMPORT_ITEM_ERROR', 'modules/financeiro/uploads/import_item_error/');
define('FINANCEIRO_ERROR', FCPATH );
define('FINANCEIRO_EXPORT_XLSX', 'modules/financeiro/uploads/export_xlsx/');

hooks()->add_action('app_admin_head', 'financeiro_add_head_component');
hooks()->add_action('app_admin_footer', 'financeiro_load_js');
hooks()->add_action('admin_init', 'financeiro_module_init_menu_items');
hooks()->add_action('admin_init', 'financeiro_permissions');

define('FINANCEIRO_REVISION', 101);


/**
 * *
 *  Registrar hook de ativação do módulo
 */

register_activation_hook(FINANCEIRO_MODULE_NAME, 'financeiro_module_activation_hook');

/**
 * Registre arquivos de idioma, deve ser registrado se o módulo estiver usando idiomas
 */
register_language_files(FINANCEIRO_MODULE_NAME, [FINANCEIRO_MODULE_NAME]);

/**
 * Gancho (hook) de Ativação ONline
 */
function financeiro_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}


/**
 * init add head component
 */
function financeiro_add_head_component()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    // Inicializa e cria o Cabeçalho do Modulo Dashboard/Financeiro
    if(!(strpos($viewuri,'admin/financeiro/dashboard') === false)){
        echo '<link href="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/css/box_loading.css') . '?v=' . FINANCEIRO_REVISION. '"  rel="stylesheet" type="text/css" />';
        echo '<link href="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/css/dashboard.css') . '?v=' . FINANCEIRO_REVISION. '"  rel="stylesheet" type="text/css" />';
    }


    if(!(strpos($viewuri,'admin/financeiro/planocontas') === false)){
        echo '<link href="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/css/chart_of_accounts.css') . '?v=' . ACCOUTING_REVISION. '"  rel="stylesheet" type="text/css" />';
    }
}

/**
 * init add footer component
 */
 

function financeiro_load_js()
{
    $CI          = &get_instance();
    $viewuri     = $_SERVER['REQUEST_URI'];
    $mediaLocale = get_media_locale();

 
    if (!(strpos($viewuri, '/admin/financeiro/dashboard') === false)) 
	{
		echo '<script src="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/plugins/highcharts/highcharts.js') . '"></script>';
		echo '<script src="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/plugins/highcharts/modules/variable-pie.js') . '"></script>';
		echo '<script src="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/plugins/highcharts/modules/export-data.js') . '"></script>';
		echo '<script src="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/plugins/highcharts/modules/accessibility.js') . '"></script>';
		echo '<script src="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/plugins/highcharts/modules/exporting.js') . '"></script>';
		echo '<script src="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/plugins/highcharts/highcharts-3d.js') . '"></script>';
        echo '<script src="' . module_dir_url(FINANCEIRO_MODULE_NAME, 'assets/js/financeiro.js') . '"></script>';
	}
}


/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function financeiro_module_init_menu_items()
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



    if (has_permission('financeiro_dashboard', '', 'view') || has_permission('financeiro_report', '', 'view') || has_permission('financeiro_setting', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('financeiro', [
            'name'     => _l('als_financeiro'),
            'icon'     => 'fa fa-usd',
            'position' => 12,
        ]);

        if (has_permission('financeiro_dashboard', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_dashboard',
                'name'     => _l('dashboard'),
                'icon'     => 'fa fa-home',
                'href'     => admin_url('financeiro/dashboard'),
                'position' => 1,
            ]);
        }

        if (has_permission('financeiro_bancos', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_bancos',
                'name'     => _l('bancos'),
                'icon'     => 'fa fa-ban',
                'href'     => admin_url('financeiro/bancos'),
                'position' => 2,
            ]);
        }


        if (has_permission('financeiro_contasbancarias', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_contasbancarias',
                'name'     => _l('contasbancarias'),
                'icon'     => 'fa fa-credit-card',
                'href'     => admin_url('financeiro/contasbancarias'),
                'position' => 3,
            ]);
        }
		
		if (has_permission('financeiro_planocontas', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_planocontas',
                'name'     => _l('planocontas'),
                'icon'     => 'fa fa-list-ol',
                'href'     => admin_url('financeiro/planocontas'),
                'position' => 4,
            ]);
        }

		if (has_permission('financeiro_contaspagar', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_contaspagar',
                'name'     => _l('contaspagar'),
                'icon'     => 'fa fa-paypal',
                'href'     => admin_url('financeiro/contaspagar'),
                'position' => 5,
            ]);
        }

		if (has_permission('financeiro_contasreceber', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_contasreceber',
                'name'     => _l('contasreceber'),
                'icon'     => 'fa fa-usd',
                'href'     => admin_url('financeiro/contasreceber'),
                'position' => 6,
            ]);
        }
		
		if (has_permission('financeiro_selecaopagamento', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_selecacopagamento',
                'name'     => _l('selecaopagamento'),
                'icon'     => 'fa fa-spinner',
                'href'     => admin_url('financeiro/selecaopagamento'),
                'position' => 7,
            ]);
        }

		if (has_permission('financeiro_faturas', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_faturas',
                'name'     => _l('faturas'),
                'icon'     => 'fa fa-file-invoice',
                'href'     => admin_url('financeiro/faturas'),
                'position' => 8,
            ]);
        }		

		if (has_permission('financeiro_fluxocaixa', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_fluxocaixa',
                'name'     => _l('fluxocaixa'),
                'icon'     => 'fa fa-line-chart',
                'href'     => admin_url('financeiro/fluxocaixa'),
                'position' => 9,
            ]);
        }		
		
		if (has_permission('financeiro_conciliacao', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_conciliacao',
                'name'     => _l('conciliacaobancaria'),
                'icon'     => 'fa fa-line-chart',
                'href'     => admin_url('financeiro/conciliacaobancaria'),
                'position' => 10,
            ]);
        }	

		if (has_permission('financeiro_budget', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_relatorios',
                'name'     => _l('budget'),
                'icon'     => 'fa fa-line-chart',
                'href'     => admin_url('financeiro/budget'),
                'position' => 11,
            ]);
        }			
		
		if (has_permission('financeiro_relatorios', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_relatorios',
                'name'     => _l('relatoriosfinanceiros'),
                'icon'     => 'fa fa-line-chart',
                'href'     => admin_url('financeiro/relatorios'),
                'position' => 12,
            ]);
        }		

		
        if (has_permission('financeiro_configuracoes', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('financeiro', [
                'slug'     => 'financeiro_configuracoes',
                'name'     => _l('configuracoes'),
                'icon'     => 'fa fa-cog',
                'href'     => admin_url('financeiro/configuracoes'),
                'position' => 13,
            ]);
        }
    }
}

/**
 * Inicia as permissões do módulo de Financeiro na configuração do hook admin_init
 */
function financeiro_permissions() {

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
    ];
    register_staff_capabilities('financeiro_dashboard', $capabilities, _l('financeiro_dashboard'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];


    register_staff_capabilities('financeiro_report', $capabilities, _l('financeiro_report'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'edit'   => _l('permission_edit'),
    ];
    register_staff_capabilities('financeiro_setting', $capabilities, _l('financeiro_setting'));
}
