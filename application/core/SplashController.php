<?php

defined('BASEPATH') or exit('No direct script access allowed');

define('SITE_AREA', true);

class SplashController extends App_Controller
{

    public $data = [];

    public function __construct()
    {
        parent::__construct();
       // $this->load->library('parser');


        if (is_staff_logged_in()
            && $this->app->is_db_upgrade_required($this->current_db_version)) {
            redirect(admin_url());
        }
        
        
    }

    public function data($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $this->data = array_merge($this->data, $data);

        return $this;
    }    
    
    public function render($view, $data = null)
    {
        if ($data === NULL) {
            $data = array();
        }

        // render views bottom-to-top while layout specified and not equal to default_layout
        do
        {
            // erase previous layout
            $this->viewbag->layout = '';

            // render current view/layout
            $data['CONTENT'] = $this->parser->parse($view, $data, TRUE);

            // determine next layout to render. If $this->viewbag->layout was not specified while last rendering then use default_layout
            $view = !empty($this->viewbag->layout) ? $this->viewbag->layout : config_item('default_layout');

        } while ($view !== config_item('default_layout'));

        $this->parser->parse($view, $data);
    }

    public function error()
    {
        $this->render("errors/error");
    }

    public function error_403()
    {
        $this->render("errors/error_403");
    }

    public function error_404()
    {
        $this->render("errors/error_404");
    }

    protected function view_exists($view)
    {
        return !empty($view) && file_exists(VIEWPATH.$view.'.php');
    }


    public function layout($notInThemeViewFiles = false)
    {
        /**
         * Navigation and submenu
         * @deprecated 2.3.2
         * @var boolean
         */

        $this->data['use_navigation'] = $this->use_navigation == true;
        $this->data['use_submenu']    = $this->use_submenu == true;

        /**
         * @since  2.3.2 new variables
         * @var array
         */
        $this->data['navigationEnabled'] = $this->use_navigation == true;
        $this->data['subMenuEnabled']    = $this->use_submenu == true;

        /**
         * Theme head file
         * @var string
         */
        $this->template['head'] = $this->load->view('themes/' . active_clients_theme() . '/head', $this->data, true);

        $GLOBALS['customers_head'] = $this->template['head'];

        /**
         * Load the template view
         * @var string
         */
        $module                       = CI::$APP->router->fetch_module();
        $this->data['current_module'] = $module;
        $viewPath                     = !is_null($module) || $notInThemeViewFiles ?
            $this->view :
            $this->createThemeViewPath($this->view);

        $this->template['view']    = $this->load->view($viewPath, $this->data, true);
        $GLOBALS['customers_view'] = $this->template['view'];

        /**
         * Theme footer
         * @var string
         */
        $this->template['footer'] = $this->use_footer == true
            ? $this->load->view('themes/' . active_clients_theme() . '/footer', $this->data, true)
            : '';
        $GLOBALS['customers_footer'] = $this->template['footer'];

        /**
         * @deprecated 2.3.0
         * Theme scripts.php file is no longer used since vresion 2.3.0, add app_customers_footer() in themes/[theme]/index.php
         * @var string
         */
        $this->template['scripts'] = '';
        if (file_exists(VIEWPATH . 'themes/' . active_clients_theme() . '/scripts.php')) {
            if (ENVIRONMENT != 'production') {
                trigger_error(sprintf('%1$s', 'Clients area theme file scripts.php file is no longer used since version 2.3.0, add app_customers_footer() in themes/[theme]/index.php. You can check the original theme index.php for example.'));
            }

            $this->template['scripts'] = $this->load->view('themes/' . active_clients_theme() . '/scripts', $this->data, true);
        }

        /**
         * Load the theme compiled template
         */
        $this->load->view('themes/' . active_clients_theme() . '/index', $this->template);
    }
}
