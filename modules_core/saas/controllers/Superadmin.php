<?php

defined('BASEPATH') || exit('No direct script access allowed');

class Superadmin extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('superadmin_model');
        $this->load->library(['superadmin_lib', 'encryption']);
        $this->load->helper('superadmin');

        $this->app_modules->is_inactive('saas') ? access_denied() : '';
    }

    public function validateTenantsName()
    {
        if ($this->input->is_ajax_request()) {
            $posted_data = $this->input->post();
            $where       = [];
            if (!empty($posted_data['userid'])) {
                $where['userid!='] = $posted_data['userid'];
            }
            if (isset($posted_data['tenants_name'])) {
                $where['tenants_name'] = trim($posted_data['tenants_name']);
                $check                 =  $this->superadmin_model->validateTenantsName($where);
            }
            echo json_encode($check ?? true);
        }
    }

    public function resetCustomerPlan()
    {
        echo json_encode($this->session->unset_userdata('selectedPlan'));
    }

    /* Change tenant status / active / inactive */
    public function change_tenant_status($id, $status)
    {
        if ($this->input->is_ajax_request()) {
            $this->superadmin_model->change_tenant_status($id, $status);
        }
    }

    /* Change Force HTTPS redirect status / enable / disable using CPANEL */
    public function change_redirect_status($id, $status)
    {
        if ($this->input->is_ajax_request()) {

            $i_have_c_panel = get_option('i_have_c_panel');
            $cpanel_theme = get_option('cpanel_theme');
            $cpanel_port = get_option('cpanel_port');
            $cpanel_username = get_option('cpanel_username');
            $cpanel_password = get_option('cpanel_password');

            if ($i_have_c_panel) {

                $client_plan = getClientPlan($id);

                $this->load->library(SUPERADMIN_MODULE . '/CpanelApi');

                /** @var CpanelApi $cpanel */
                $cpanel = $this->cpanelapi->init(
                    $cpanel_username,
                    $cpanel_password,
                    rtrim(base_url(), "/"),
                    $cpanel_port
                );

                $this->cpanelapi->toggleSslRedirect($client_plan->tenants_name . "." . $_SERVER['HTTP_HOST'], $status);

                $this->superadmin_model->change_https_redirect_status($id, $status);
            }
        }
    }

    public function get_settings_view_page()
    {
        if ($this->input->is_ajax_request()) {
            $clientid = $this->input->post('userid');
            $client_plan = getClientPlan($clientid);
            if (getTenantDbNameByClientID($clientid)) {
                $tenant_password = get_instance()->encryption->decrypt($client_plan->tenants_db_password);
                switchDatabase($client_plan->tenants_db, $client_plan->tenants_db_username, $tenant_password, get_option('mysql_host'), get_option('mysql_port'));
                $this->config->set_item("fetching_tenant", 1);
                $view_path = $this->input->post('view_path');
                $this->load->model('payment_modes_model');
                
                $this->load->model('taxes_model');
                $this->load->model('tickets_model');
                $this->load->model('leads_model');
                $this->load->model('currencies_model');
                $this->load->model('staff_model');
                $data['taxes']                                   = $this->taxes_model->get();
                $data['ticket_priorities']                       = $this->tickets_model->get_priority();
                $data['ticket_priorities']['callback_translate'] = 'ticket_priority_translate';
                $data['roles']                                   = $this->roles_model->get();
                $data['leads_sources']                           = $this->leads_model->get_source();
                $data['leads_statuses']                          = $this->leads_model->get_status();
                $data['title']                                   = _l('options');
                $data['staff']                                   = $this->staff_model->get('', ['active' => 1]);
                $data['payment_gateways']                        = $this->payment_modes_model->get_payment_gateways(true);
                $data['contacts_permissions']                    = get_contact_permissions();
                
                $data['view'] = $this->load->view($view_path, $data, true);
                
                
                switchDatabase();
                $this->config->set_item("fetching_tenant", 0);
            }
            echo json_encode($data);
        }
    }

    public function save_tenant_setting($user_id) {
        $client_plan = getClientPlan($user_id);
        if (getTenantDbNameByClientID($user_id)) {
            $post_data = $this->input->post();
            $tmpData   = $this->input->post(null, false);
            $tenant_password = get_instance()->encryption->decrypt($client_plan->tenants_db_password);
            switchDatabase($client_plan->tenants_db, $client_plan->tenants_db_username, $tenant_password, get_option('mysql_host'), get_option('mysql_port'));
            
            $logo_uploaded     = (handle_company_logo_upload($client_plan->tenants_name) ? true : false);
            $favicon_uploaded  = (handle_favicon_upload($client_plan->tenants_name) ? true : false);
            $signatureUploaded = (handle_company_signature_upload($client_plan->tenants_name) ? true : false);

            if (isset($post_data['settings']['email_header'])) {
                $post_data['settings']['email_header'] = $tmpData['settings']['email_header'];
            }

            if (isset($post_data['settings']['email_footer'])) {
                $post_data['settings']['email_footer'] = $tmpData['settings']['email_footer'];
            }

            if (isset($post_data['settings']['email_signature'])) {
                $post_data['settings']['email_signature'] = $tmpData['settings']['email_signature'];
            }

            if (isset($post_data['settings']['smtp_password'])) {
                $post_data['settings']['smtp_password'] = $tmpData['settings']['smtp_password'];
            }
            
            $this->load->model('payment_modes_model');
            $this->load->model('settings_model');
            $success = $this->settings_model->update($post_data);

            if ($success > 0) {
                set_alert('success', _l('settings_updated'));
            }
            switchDatabase();
            redirect(admin_url('clients/client/'.$user_id.'?group=tenants_setting'));
            
        }
    }

    public function delete_tag($id, $user_id)
    {
        $client_plan = getClientPlan($user_id);
        if (!$id) {
            redirect(admin_url('clients/client/'.$user_id.'?group=tenants_setting'));
        }

        $tenant_password = get_instance()->encryption->decrypt($client_plan->tenants_db_password);
        switchDatabase($client_plan->tenants_db, $client_plan->tenants_db_username, $tenant_password, get_option('mysql_host'), get_option('mysql_port'));

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'tags');
        $this->db->where('tag_id', $id);
        $this->db->delete(db_prefix() . 'taggables');

        switchDatabase();

        redirect(admin_url('clients/client/'.$user_id.'?group=tenants_setting'));
    }

    public function delete_queued_email($id, $user_id)
    {
        $client_plan = getClientPlan($user_id);

        $tenant_password = get_instance()->encryption->decrypt($client_plan->tenants_db_password);
        switchDatabase($client_plan->tenants_db, $client_plan->tenants_db_username, $tenant_password, get_option('mysql_host'), get_option('mysql_port'));
        
        $this->load->model('emails_model');
        $this->email->delete_queued_email($id);
        set_alert('success', _l('deleted', _l('email_queue')));

        switchDatabase();
        redirect(admin_url('clients/client/'.$user_id.'?group=tenants_setting'));
    }

    public function sent_smtp_test_email($user_id)
    {
        if ($this->input->post()) {
            $client_plan = getClientPlan($user_id);

            $tenant_password = get_instance()->encryption->decrypt($client_plan->tenants_db_password);
            switchDatabase($client_plan->tenants_db, $client_plan->tenants_db_username, $tenant_password, get_option('mysql_host'), get_option('mysql_port'));
            
            $this->load->config('email');
            // Simulate fake template to be parsed
            $template           = new StdClass();
            $template->message  = get_tenant_option('email_header') . 'This is test SMTP email. <br />If you received this message that means that your SMTP settings is set correctly.' . get_tenant_option('email_footer');
            $template->fromname = get_tenant_option('companyname') != '' ? get_tenant_option('companyname') : 'TEST';
            $template->subject  = 'SMTP Setup Testing';

            $template = parse_email_template($template);

            hooks()->do_action('before_send_test_smtp_email');
            $this->email->initialize();
            if (get_tenant_option('mail_engine') == 'phpmailer') {
                $this->email->set_debug_output(function ($err) {
                    if (!isset($GLOBALS['debug'])) {
                        $GLOBALS['debug'] = '';
                    }
                    $GLOBALS['debug'] .= $err . '<br />';

                    return $err;
                });

                $this->email->set_smtp_debug(3);
            }

            $this->email->set_newline(config_item('newline'));
            $this->email->set_crlf(config_item('crlf'));

            $this->email->from(get_tenant_option('smtp_email'), $template->fromname);
            $this->email->to($this->input->post('test_email'));

            $systemBCC = get_tenant_option('bcc_emails');

            if ($systemBCC != '') {
                $this->email->bcc($systemBCC);
            }

            $this->email->subject($template->subject);
            $this->email->message($template->message);

            if ($this->email->send(true)) {
                set_alert('success', 'Seems like your SMTP settings is set correctly. Check your email now.');
                hooks()->do_action('smtp_test_email_success');
            } else {
                set_debug_alert('<h1>Your SMTP settings are not set correctly here is the debug log.</h1><br />' . $this->email->print_debugger() . (isset($GLOBALS['debug']) ? $GLOBALS['debug'] : ''));

                hooks()->do_action('smtp_test_email_failed');
            }
            echo $this->email->print_debugger();
            switchDatabase();
        }
    }

    public function remove_signature_image($user_id)
    {
        $client_plan = getClientPlan($user_id);

        $tenant_password = get_instance()->encryption->decrypt($client_plan->tenants_db_password);
        switchDatabase($client_plan->tenants_db, $client_plan->tenants_db_username, $tenant_password, get_option('mysql_host'), get_option('mysql_port'));

        $sImage = get_tenant_option('signature_image');
        if (file_exists(get_upload_path_by_type('company') . '/' . $sImage)) {
            unlink(get_upload_path_by_type('company') . '/' . $sImage);
        }

        update_option('signature_image', '');

        switchDatabase();
        redirect(admin_url('clients/client/'.$user_id.'?group=tenants_setting'));
    }

    /* Remove company logo from settings / ajax */
    public function remove_company_logo($type = '', $user_id = null)
    {
        if(is_numeric($type) && is_null($user_id)){
            $user_id = $type;
        }

        $client_plan = getClientPlan($user_id);

        $tenant_password = get_instance()->encryption->decrypt($client_plan->tenants_db_password);
        switchDatabase($client_plan->tenants_db, $client_plan->tenants_db_username, $tenant_password, get_option('mysql_host'), get_option('mysql_port'));

        hooks()->do_action('before_remove_company_logo');

        $logoName = get_tenant_option('company_logo');
        if ($type == 'dark') {
            $logoName = get_tenant_option('company_logo_dark');
        }

        $path = get_upload_path_by_type('company') . '/' . $logoName;
        if (file_exists($path)) {
            unlink($path);
        }

        update_option('company_logo' . ($type == 'dark' ? '_dark' : ''), '');
        switchDatabase();
        redirect(admin_url('clients/client/'.$user_id.'?group=tenants_setting'));
    }

    public function remove_fv($user_id)
    {
        $client_plan = getClientPlan($user_id);

        $tenant_password = get_instance()->encryption->decrypt($client_plan->tenants_db_password);
        switchDatabase($client_plan->tenants_db, $client_plan->tenants_db_username, $tenant_password, get_option('mysql_host'), get_option('mysql_port'));

        hooks()->do_action('before_remove_favicon');
        if (file_exists(get_upload_path_by_type('company') . '/' . get_tenant_option('favicon'))) {
            unlink(get_upload_path_by_type('company') . '/' . get_tenant_option('favicon'));
        }
        update_option('favicon', '');
        switchDatabase();
        redirect(admin_url('clients/client/'.$user_id.'?group=tenants_setting'));
    }
}

/* End of file Superadmin.php */
