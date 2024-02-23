<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Perfex mobile app api
Description: An api module for perfex mobile app
Version: 1.0.0
Author: Divesh Ahuja
Author URI: https://codecanyon.net/item/perfex-android-app-lead-management-app/41921554
Requires at least: 2.3.2
*/

use app\services\utilities\Date;

include_once 'BaseApiController.php';

class Perfex_mobile_app_api extends BaseApiController
{
    /**
     * @return void
     */
    public function login()
    {
        $requiredFieldsArray = ['email', 'password'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            $form_data = $this->formData;
            $email = $form_data['email'];
            $password = $form_data['password'];

            $where['email'] = $email;
            $user = $this->get_staff($where);

            if ($user) {
                if (!app_hasher()->CheckPassword($password, $user->password)) {
                    $response = ['success' => 0, 'message' => 'Invalid Password'];
                } else {
                    //get available permissions
                    $permissions = $this->get_permissions($user->staffid);
                    $this->load->model('Authentication_model');
                    $user->authentication_token = $this->Authentication_model->encrypt($user->staffid);
                    unset($user->password);
                    $response = ['success' => 1, 'message' => 'Login Successful', 'user' => $user, 'permissions' => $permissions];
                }
            } else {
                $response = ['success' => 0, 'message' => 'Invalid User'];
            }
            $this->log_api($response);
        } else {
            $this->authentication_error("Some Required fields are missing");
        }
    }

    public function validate_session()
    {
        $requiredFieldsArray = ['authentication_token', 'staff_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            $this->load->model('Authentication_model');
            $staffId = $this->Authentication_model->decrypt($this->formData['authentication_token']);
            $response = ['success' => 0, 'message' => 'Session expired!'];
            if ($this->formData['staff_id'] == $staffId) {
                $user = $this->get_staff(['staffid' => $staffId]);
                $response = ['success' => 1, 'message' => 'Session validated!', 'user' => $user, 'permissions' => []];
            }
            $this->log_api($response);
        }
    }

    /**
     * @param $staffid
     * @return mixed
     */
    private function get_permissions($staffid)
    {
        $this->db->select('feature,capability');
        $this->db->where('staff_id', $staffid);
        return $this->db->get($this->dbPrefix . 'staff_permissions')->result_array();
    }


    /**
     * @return void
     */
    public function get_my_leads()
    {
        $requiredFieldsArray = ['authentication_token', 'end_to', 'start_from'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $canViewLeads = $this->has_permission('leads', 'view');
                $formData = $this->formData;

                $start_from = $formData['start_from'];
                $end_to = $formData['end_to'];

                $date_from = !empty($formData['date_from']) ? $formData['date_from'] : "";
                $date_to = !empty($formData['date_to']) ? $formData['date_to'] : "";
                $assigned_to = !empty($formData['assigned_to']) ? $formData['assigned_to'] : 0;

                if ($assigned_to > 0) {
                    $this->db->where('assigned', $formData['assigned_to']);
                }

                if ($date_from != "" && $date_to != "") {
                    $this->db->where('dateadded >=', date("Y-m-d", strtotime($date_from)) . " 00:00:00");
                    $this->db->where('dateadded <=', date("Y-m-d", strtotime($date_to)) . " 23:59:59");
                }

                if (!$canViewLeads) {
                    $this->db->group_start();
                    $this->db->where('assigned', $this->currentUser->staffid)
                        ->or_where('addedfrom', $this->currentUser->staffid)
                        ->or_where('is_public', 1);
                    $this->db->group_end();
                }
                $this->db->select($this->dbPrefix . 'leads.*,' . $this->dbPrefix . 'countries.short_name as countryName');
                $this->db->join($this->dbPrefix . 'countries', $this->dbPrefix . 'countries.country_id=' . $this->dbPrefix . 'leads.country', 'left');
                $this->db->order_by($this->dbPrefix . 'leads.id', 'DESC')->limit($end_to, $start_from);
                $leads = $this->db->get($this->dbPrefix . 'leads')->result_array();
                $final = [];
                if (!empty($leads)) {
                    foreach ($leads as $lead) {
                        $data = $lead;

                        if (isset($data['dateadded']) && $data['dateadded'] != "") {
                            $data['dateadded'] = substr($data['dateadded'], 0, 10);
                        }

                        if (isset($data['source']) && $data['source'] > 0) {
                            $exist = $this->db->get_where($this->dbPrefix . 'leads_sources', ['id' => $data['source']])->row();
                            if ($exist) {
                                $data['source'] = $exist->name;
                            }
                        } else {
                            $data['source'] = "";
                        }
                        if (isset($data['status']) && $data['status'] > 0) {
                            $exist = $this->db->get_where($this->dbPrefix . 'leads_status', ['id' => $data['status']])->row();
                            if ($exist) {
                                $data['status'] = $exist->name;
                            }
                        } else {
                            $data['status'] = "";
                        }

                        if (isset($data['assigned']) && $data['assigned'] > 0) {
                            $exist = $this->db->get_where($this->dbPrefix . 'staff', ['staffid' => $data['assigned']])->row();
                            if ($exist) {
                                $data['assigned'] = $exist->firstname . ' ' . $exist->lastname;
                            } else {
                                $data['assigned'] = "";
                            }
                        } else {
                            $data['assigned'] = "";
                        }

                        $final[] = $data;
                    }
                }
                $response = ['status' => 1, 'message' => 'Leads loaded', 'leads' => $final];
                $this->log_api($response);

            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function get_lead()
    {
        $requiredFieldsArray = ['authentication_token', 'lead_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $canViewLeads = $this->has_permission('leads', 'view');
                if (!$canViewLeads) {
                    $this->db->group_start();
                    $this->db->where('assigned', $this->currentUser->staffid)
                        ->or_where('addedfrom', $this->currentUser->staffid)
                        ->or_where('is_public', 1);
                    $this->db->group_end();
                }
                $this->db->select($this->dbPrefix . 'leads.*,' . $this->dbPrefix . 'countries.short_name as countryName');
                $this->db->join($this->dbPrefix . 'countries', $this->dbPrefix . 'countries.country_id=' . $this->dbPrefix . 'leads.country', 'left');
                $this->db->where($this->dbPrefix . 'leads.id', $this->formData['lead_id']);
                $this->db->order_by($this->dbPrefix . 'leads.id', 'DESC');
                $lead = $this->db->get($this->dbPrefix . 'leads')->row_array();
                $response = ['status' => 0, 'message' => 'Lead not found', 'lead' => null];
                if (!empty($lead)) {
                    $data = $lead;

                    if (isset($data['dateadded']) && $data['dateadded'] != "") {
                        $data['dateadded'] = substr($data['dateadded'], 0, 10);
                    }

                    if (isset($data['source']) && $data['source'] > 0) {
                        $exist = $this->db->get_where($this->dbPrefix . 'leads_sources', ['id' => $data['source']])->row();
                        if ($exist) {
                            $data['source'] = $exist->name;
                        }
                    } else {
                        $data['source'] = "";
                    }
                    if (isset($data['status']) && $data['status'] > 0) {
                        $exist = $this->db->get_where($this->dbPrefix . 'leads_status', ['id' => $data['status']])->row();
                        if ($exist) {
                            $data['status'] = $exist->name;
                        }
                    } else {
                        $data['status'] = "";
                    }

                    if (isset($data['assigned']) && $data['assigned'] > 0) {
                        $exist = $this->db->get_where($this->dbPrefix . 'staff', ['staffid' => $data['assigned']])->row();
                        if ($exist) {
                            $data['assigned'] = $exist->firstname . ' ' . $exist->lastname;
                        } else {
                            $data['assigned'] = "";
                        }
                    } else {
                        $data['assigned'] = "";
                    }
                    $data['custom_fields'] = get_custom_fields("leads");
                    $data['custom_fields_values'] = $this->get_custom_field_values($data['id'], "leads");
                    $customisedLead = $data;
                    $response = ['status' => 1, 'message' => 'Lead loaded', 'lead' => $customisedLead];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    private function get_custom_field_values($relId, $relType)
    {
        return $this->db->get_where($this->dbPrefix . 'customfieldsvalues', ['relid' => $relId, 'fieldto' => $relType])->result_array();
    }

    /**
     * @return void
     */
    public function delete_my_lead()
    {
        $requiredFieldsArray = ['authentication_token', 'lead_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->has_permission('leads', 'delete')) {
                if ($this->currentUser) {
                    $form_data = $this->formData;
                    $lead_id = $form_data['lead_id'];
                    $this->db->where('id', $lead_id);
                    if ($this->db->delete($this->dbPrefix . 'leads') && $this->db->affected_rows() > 0) {
                        $response = ['status' => 1, 'message' => 'Lead deleted'];
                    } else {
                        $response = ['status' => 0, 'message' => 'Lead not found'];
                    }
                    $this->log_api($response);
                } else {
                    $this->authentication_error("Session expired! Please login again");
                }
            } else {
                $this->permission_not_allowed('leads', 'delete');
            }
        }
    }


    /**
     * @return void
     */
    public function delete_my_customer()
    {
        $requiredFieldsArray = ['authentication_token', 'userId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'delete')) {
                    $form_data = $this->formData;
                    $this->db->where('userid', $form_data['userId']);
                    if ($this->db->delete($this->dbPrefix . 'clients') && $this->db->affected_rows() > 0) {
                        $response = ['status' => 1, 'message' => 'Customer deleted'];
                    } else {
                        $response = ['status' => 0, 'message' => 'Customer not found'];
                    }
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('customers', 'delete');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    public function delete_contact()
    {
        $requiredFieldsArray = ['authentication_token', 'contactId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'delete')) {
                    $this->db->where('id', $this->formData['contactId']);
                    if ($this->db->delete($this->dbPrefix . 'contacts') && $this->db->affected_rows() > 0) {
                        $response = ['status' => 1, 'message' => 'Contact deleted'];
                    } else {
                        $response = ['status' => 0, 'message' => 'Contact not found'];
                    }
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('customers', 'delete');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    /**
     * @return void
     */
    public function update_customer_active_status()
    {
        $requiredFieldsArray = ['authentication_token', 'userId', 'isActive'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->has_permission('customers', 'edit')) {
                if ($this->currentUser) {
                    $form_data = $this->formData;
                    $this->db->where('id', $form_data['userId']);
                    if ($this->db->update($this->dbPrefix . 'clients', ['active' => $form_data['isActive']]) && $this->db->affected_rows() > 0) {
                        $response = ['status' => 1, 'message' => 'Customer status updated'];
                    } else {
                        $response = ['status' => 0, 'message' => 'Customer not found'];
                    }
                    $this->log_api($response);
                } else {
                    $this->authentication_error("Session expired! Please login again");
                }
            } else {
                $this->permission_not_allowed("customers", "edit");
            }
        }
    }

    /**
     * @return void
     */
    public function update_contact_active_status()
    {
        $requiredFieldsArray = ['authentication_token', 'userId', 'isActive'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'edit')) {
                    $this->db->where('id', $this->formData['userId']);
                    if ($this->db->update($this->dbPrefix . 'contacts', ['active' => $this->formData['isActive']]) && $this->db->affected_rows() > 0) {
                        $response = ['status' => 1, 'message' => 'Contact status updated'];
                    } else {
                        $response = ['status' => 0, 'message' => 'Contact not found'];
                    }
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('customers', 'edit');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    /**
     * @return void
     */
    public function edit_my_lead()
    {
        $requiredFieldsArray = ['authentication_token', 'leadid', 'name', 'email', 'phonenumber'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $formData = $this->formData;
                $leadId = $formData['leadid'];
                $lead_exist = $this->db->get_where($this->dbPrefix . 'leads', ['id' => $leadId])->row();
                if ($lead_exist) {
                    $response = ['status' => 1, 'message' => 'Lead updated'];
                    $this->load->model('Leads_model');
                    unset($formData['staffid']);
                    unset($formData['leadid']);

                    if ($formData['is_public'] == "false") {
                        $formData['is_public'] = false;
                    } else {
                        $formData['is_public'] = true;
                    }

                    $country = $this->db->get_where($this->dbPrefix . 'countries', ['long_name' => $formData['country']])->row();
                    $formData['country'] = 0;
                    if ($country) {
                        $formData['country'] = $country->country_id;
                    }

                    if ($formData['source'] == "") {
                        $formData['source'] = 0;
                    } else {
                        $sourceRow = $this->db->get_where($this->dbPrefix . 'leads_sources', ['name' => $formData['source']])->row();
                        if ($sourceRow) {
                            $formData['source'] = $sourceRow->id;
                        }
                    }

                    if ($formData['status'] == "") {
                        $formData['status'] = 0;
                    } else {
                        $statusRow = $this->db->get_where($this->dbPrefix . 'leads_status', ['name' => $formData['status']])->row();
                        if ($statusRow) {
                            $formData['status'] = $statusRow->id;
                        }
                    }

                    if ($formData['assigned'] != "") {
                        $assignedArray = explode(" ", $formData['assigned']);
                        $searchArray['firstname'] = $assignedArray[0];
                        if (isset($assignedArray[1])) {
                            $searchArray['lastname'] = $assignedArray[1];
                        }
                        $user = $this->get_staff($searchArray);
                        if ($user) {
                            $formData['assigned'] = $user->staffid;
                        }
                    } else {
                        $formData['assigned'] = "";
                    }
                    if (!empty($formData['custom_fields']))
                        $formData['custom_fields'] = $this->prepareCustomFieldsToSave("leads", $formData['custom_fields']);
                    if (!empty($formData['custom_contact_date']) && $formData['custom_contact_date'] == "Select Date") {
                        unset($formData['custom_contact_date']);
                    }
                    if (!empty($formData['lastcontact']) && $formData['lastcontact'] == "Select Date") {
                        unset($formData['lastcontact']);
                    }
                    $updated = $this->Leads_model->update($formData, $leadId);
                    if ($updated) {
                        $response = ['status' => 1, 'message' => 'Lead updated successfully'];
                    }
                } else {
                    $response = ['status' => 0, 'message' => 'Lead does not exist!'];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function get_lead_status()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $status = $this->db->order_by('id', 'DESC')->get($this->dbPrefix . 'leads_status')->result_array();
                $response = ['status' => 1, 'message' => 'No status found!'];
                if ($status) {
                    $response['status'] = $status;
                    $response['message'] = "Status found";
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    /**
     * @return void
     */
    public function get_initial_lead_form()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $status = $this->db->order_by('id', 'DESC')->get($this->dbPrefix . 'leads_status')->result_array();
                $response = ['status' => 1, 'message' => 'No data found!'];
                if ($status) {
                    $response['leadStatus'] = $status;
                    $response['message'] = "Data found";
                } else {
                    $response['leadStatus'] = null;
                }

                $source = $this->db->order_by('id', 'DESC')->get($this->dbPrefix . 'leads_sources')->result_array();
                if ($source) {
                    $response['source'] = $source;
                    $response['message'] = "Data found";
                } else {
                    $response['source'] = null;
                }

                $countries = $this->db->get($this->dbPrefix . 'countries')->result_array();
                if ($countries) {
                    $response['countries'] = $countries;
                    $response['message'] = "Data found";
                } else {
                    $response['countries'] = null;
                }

                $this->db->select('staffid,firstname,lastname');
                $assignees = $this->db->get($this->dbPrefix . 'staff')->result_array();
                if ($assignees) {
                    $response['assignees'] = $assignees;
                    $response['message'] = "Data found";
                } else {
                    $response['assignees'] = null;
                }

                if ($this->currentUser->admin == 0) {
                    $this->db->where('only_admin', 0);
                }
                $this->db->where('fieldto', 'leads')->order_by('field_order');
                $customFields = $this->db->get($this->dbPrefix . 'customfields')->result_array();
                if ($customFields) {
                    $response['customFields'] = $customFields;
                    $response['message'] = "Data found";
                } else {
                    $response['customFields'] = null;
                }

                $response['defaultSelectedFields'] = [
                    'leads_default_source' => intval(get_option('leads_default_source')),
                    'leads_default_status' => intval(get_option('leads_default_status')),
                    'leads_default_country' => intval(get_option('customer_default_country'))
                ];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function get_lead_source()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $status = $this->db->order_by('id', 'DESC')->get($this->dbPrefix . 'leads_sources')->result_array();
                $response = ['status' => 1, 'message' => 'No source found!'];
                if ($status) {
                    $response['status'] = $status;
                    $response['message'] = "Sources found";
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function get_countries()
    {
        if ($this->currentUser) {
            $countries = $this->db->get($this->dbPrefix . 'countries')->result_array();
            $response = ['status' => 1, 'message' => 'No country found!', 'countries' => []];
            if ($countries) {
                $response['countries'] = $countries;
                $response['message'] = "Status found";
            }
            $this->log_api($response);
        } else {
            $this->authentication_error("Session expired! Please login again");
        }
    }


    /**
     * @return void
     */
    public function add_lead()
    {
        $requiredFieldsArray = ['authentication_token', 'status', 'source', 'name', 'email', 'phonenumber'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $response = ['status' => 0, 'message' => 'An error occurred'];
                $form_data = $this->formData;
                $this->load->model('Leads_model');
                unset($form_data['staffid']);

                if ($form_data['is_public'] == "false") {
                    $form_data['is_public'] = false;
                } else {
                    $form_data['is_public'] = true;
                }

                $country = $this->db->get_where($this->dbPrefix . 'countries', ['long_name' => $form_data['country']])->row();
                $form_data['country'] = 0;
                if ($country) {
                    $form_data['country'] = $country->country_id;
                }

                if ($form_data['source'] == "") {
                    $form_data['source'] = 0;
                } else {
                    $sourceRow = $this->db->get_where($this->dbPrefix . 'leads_sources', ['name' => $form_data['source']])->row();
                    if ($sourceRow) {
                        $form_data['source'] = $sourceRow->id;
                    }
                }

                if ($form_data['status'] == "") {
                    $form_data['status'] = 0;
                } else {
                    $statusRow = $this->db->get_where($this->dbPrefix . 'leads_status', ['name' => $form_data['status']])->row();
                    if ($statusRow) {
                        $form_data['status'] = $statusRow->id;
                    }
                }

                if ($form_data['assigned'] != "") {
                    $assignedArray = explode(" ", $form_data['assigned']);
                    $searchArray['firstname'] = $assignedArray[0];
                    if (isset($assignedArray[1])) {
                        $searchArray['lastname'] = $assignedArray[1];
                    }
                    $user = $this->get_staff($searchArray);
                    if ($user) {
                        $form_data['assigned'] = $user->staffid;
                    }
                } else {
                    $form_data['assigned'] = "";
                }

                if (isset($form_data['custom_contact_date'])) {
                    unset($form_data['contacted_today']);
                } else {
                    unset($form_data['custom_contact_date']);
                }
                if (!empty($form_data['custom_fields']))
                    $form_data['custom_fields'] = $this->prepareCustomFieldsToSave("leads", $form_data['custom_fields']);
                if (!empty($form_data['custom_contact_date']) && $form_data['custom_contact_date'] == "Select Date") {
                    unset($form_data['custom_contact_date']);
                }
                $added = $this->Leads_model->add($form_data);
                if ($added) {
                    $response = ['status' => 1, 'message' => 'Lead added successfully'];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    private function prepareCustomFieldsToSave($type, $customFields)
    {
        $customFields = json_decode($customFields, true);
        $customFieldsFinal = [];
        if (!empty($customFields)) {
            foreach ($customFields['custom_fields'] as $customField) {
                $customFieldValue = $customField['value'];
                if (strpos($customFieldValue, ",") !== false) {
                    $customFieldRow = $this->db->get_where($this->dbPrefix . 'customfields', ['id' => $customField['key']])->row();
                    if (empty($customFieldRow))
                        continue;
                    if ($customFieldRow->type == "multiselect") {
                        $customFieldValue = explode(",", $customFieldValue);
                    }
                }
                $customFieldsFinal[$type][$customField['key']] = $customFieldValue;
            }
        }
        return $customFieldsFinal;
    }


    /**
     * @return void
     */
    public function get_lead_activity_log()
    {
        $requiredFieldsArray = ['authentication_token', 'leadid'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('leads', 'view')) {
                    $response = [];
                    $form_data = $this->formData;
                    $leadid = $form_data['leadid'];

                    //$this->db->where('company_id', $this->current_user->company_id);
                    $this->load->model('Leads_model');
                    $activity_log = $this->Leads_model->get_lead_activity_log($leadid);
                    $finalData = [];
                    if ($activity_log) {
                        foreach ($activity_log as $key => $log) {
                            $additional_data = '';
                            $finalData[$key]['time_ago'] = time_ago($log['date']);
                            $finalData[$key]['date'] = $log['date'];

                            if (!empty($log['additional_data'])) {
                                $additional_data = unserialize($log['additional_data']);
                                $finalData[$key]['additional_data'] = strip_tags(($log['staffid'] == 0) ? _l($log['description'], $additional_data) : $log['full_name'] . ' - ' . _l($log['description'], $additional_data));
                            } else {
                                $name = $log['full_name'] . ' - ';
                                $description = "";
                                if ($log['custom_activity'] == 0) {
                                    $description = _l($log['description']);
                                } else {
                                    $description = _l($log['description'], '', false);
                                }
                                $finalData[$key]['additional_data'] = $name . strip_tags($description);
                            }
                        }
                    }
                    $response = ['status' => 1, 'message' => 'Lead logs loaded', 'logs' => $finalData];
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('leads', 'view');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function add_lead_activity_log()
    {
        $requiredFieldsArray = ['authentication_token', 'leadid', 'description'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $form_data = $this->formData;
                $leadid = $form_data['leadid'];
                $description = $form_data['description'];

                $this->load->model('Leads_model');
                $this->Leads_model->log_lead_activity($leadid, $description);
                $response = ['status' => 1, 'message' => "Log Added"];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function add_task()
    {
        $requiredFieldsArray = ['authentication_token', 'billable', 'name', 'startdate', 'duedate'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->has_permission('tasks', 'create')) {
                if ($this->currentUser) {
                    try {
                        $response = ['status' => 0, 'message' => 'An error occurred'];
                        $form_data = $this->formData;
                        $data = $form_data;
                        $id = isset($form_data['id']) ? $form_data['id'] : "";
                        unset($data['staffid']);
                        unset($data['id']);
                        $data['description'] = html_purify($this->input->post('description', false));
                        if ($id == '') {
                            if (!has_permission('tasks', '', 'create')) {
                                $response = ['status' => 0, 'message' => _l('access_denied')];
                            }
                            $this->load->model('tasks_model');
                            $id = $this->tasks_model->add($data);
                            $_id = false;
                            $success = false;
                            $message = '';
                            if ($id) {
                                $success = 1;
                                $_id = $id;
                                $message = _l('added_successfully', _l('task'));
                                $uploadedFiles = handle_task_attachments_array($id);
                                if ($uploadedFiles && is_array($uploadedFiles)) {
                                    foreach ($uploadedFiles as $file) {
                                        $this->misc_model->add_attachment_to_database($id, 'task', [$file]);
                                    }
                                }
                            }
                            $response = ['status' => $success, 'message' => $message];
                        } else {
                            if (!has_permission('tasks', '', 'edit')) {
                                $response = ['status' => 0, 'message' => _l('access_denied')];
                            }
                            $success = $this->tasks_model->update($data, $id);
                            $message = 'Updated successfully!';
                            if ($success) {
                                $message = _l('updated_successfully', _l('task'));
                                $response = ['status' => 1, 'message' => $message];
                            }
                        }
                    } catch (\Throwable $th) {
                        $response = ['status' => 0, 'message' => 'An error occurred: ' . $th->getMessage()];
                    }

                    $this->log_api($response);
                } else {
                    $this->authentication_error("Session expired! Please login again");
                }
            } else {
                $this->permission_not_allowed('tasks', 'create');
            }
        }
    }


    /**
     * @return void
     */
    public function delete_task()
    {
        $requiredFieldsArray = ['authentication_token', 'taskid'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->has_permission('tasks', 'delete')) {
                if ($this->currentUser) {
                    $form_data = $this->formData;
                    $this->load->model('Tasks_model');

                    if (!has_permission('tasks', '', 'delete')) {
                        $response = ['status' => 0, 'message' => 'Access denied'];
                    } else {
                        $success = $this->Tasks_model->delete_task($form_data['taskid']);
                        // $success = true;
                        $status = 0;
                        if ($success) {
                            $status = 1;
                            $message = _l('deleted', _l('task'));
                        } else {
                            $message = "Cannot delete task";
                        }
                    }
                    $response = ['status' => $status, 'message' => $message];
                    $this->log_api($response);
                } else {
                    $this->authentication_error("Session expired! Please login again");
                }
            } else {
                $this->permission_not_allowed('tasks', 'delete');
            }
        }
    }


    /**
     * @return void
     */
    public function view_task()
    {
        $requiredFieldsArray = ['authentication_token', 'taskid'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->has_permission('tasks', 'view')) {
                if ($this->currentUser) {
                    $response = ['status' => 0, 'message' => 'Task Not found'];
                    $form_data = $this->formData;
                    $this->load->model('Tasks_model');
                    $task = $this->Tasks_model->get($form_data['taskid']);
                    if ($task) {
                        $response['status'] = 1;
                        $response['message'] = "Task loaded";
                        $response['task'] = $task;
                        $response['staff'] = $this->get_all_staff();
                        $response['task_reminders'] = $this->tasks_model->get_reminders($form_data['taskid']);
                        $response['task_checklist_templates'] = $this->tasks_model->get_checklist_templates($form_data['taskid']);
                    }
                    $this->log_api($response);
                } else {
                    $this->authentication_error("Session expired! Please login again");
                }
            } else {
                $this->permission_not_allowed('tasks', 'view');
            }
        }
    }


    /**
     * @return void
     */
    public function change_task_status()
    {
        $requiredFieldsArray = ['authentication_token', 'taskid', 'status'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('tasks', 'edit')) {
                    $response = ['status' => 0, 'message' => 'Task Not found'];
                    $form_data = $this->formData;
                    $this->load->model('Tasks_model');
                    $task = $this->Tasks_model->mark_as($form_data['status'], $form_data['taskid']);
                    if ($task) {
                        $response = ['status' => 1, 'message' => "Status updated succesfully"];
                    }
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('tasks', 'edit');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function start_or_stop_task()
    {
        $requiredFieldsArray = ['authentication_token', 'taskid'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $response = ['status' => 0, 'message' => 'Task Not found'];
                $form_data = $this->formData;
                $this->load->model('Tasks_model');
                $adminStop = is_admin() ? true : false;
                $timer_id = isset($form_data['not_finished_timer_by_current_staff']) ? $form_data['not_finished_timer_by_current_staff'] : "";
                $note = $this->input->post('note');
                if ($this->input->post('note') != "")
                    nl2br($this->input->post('note'));
                $task = $this->Tasks_model->timer_tracking(
                    $form_data['taskid'],
                    $timer_id,
                    $note,
                    $adminStop
                );
                if ($task) {
                    $response = ['status' => 1, 'message' => "Timer updated succesfully"];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function get_my_tasks()
    {
        $requiredFieldsArray = ['authentication_token', 'start_from', 'end_to'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $canViewTask = $this->has_permission('tasks', 'view');
                $form_data = $this->formData;
                $start_from = $form_data['start_from'];
                $end_to = $form_data['end_to'];
                $customWhereCondition = " true ";
                if (isset($form_data['rel_id']) && $form_data['rel_id'] > 0 && $form_data['rel_type'] != "") {
                    $customWhereCondition = $this->dbPrefix . 'tasks.rel_id = "' . $form_data['rel_id'] . '" AND ' . $this->dbPrefix . 'tasks.rel_type = "' . $form_data['rel_type'] . '"';
                }
                if (!$canViewTask) {
                    $customWhereCondition .= get_tasks_where_string();
                }
                $query = 'SELECT SQL_CALC_FOUND_ROWS 1, ' . $this->dbPrefix . 'tasks.id AS id, ' . $this->dbPrefix . 'tasks.name AS task_name, STATUS, startdate, duedate, (SELECT sec_to_time(SUM(CASE WHEN end_time is NULL THEN ' . time() . '-start_time ELSE end_time-start_time END)) as total_logged_time FROM ' . $this->dbPrefix . 'taskstimers WHERE ' . $this->dbPrefix . 'taskstimers.task_id = ' . $this->dbPrefix . 'tasks.id) as total_logged_time,(SELECT sec_to_time(SUM(CASE WHEN end_time is NULL THEN ' . time() . '-start_time ELSE end_time-start_time END)) as total_logged_time FROM ' . $this->dbPrefix . 'taskstimers WHERE ' . $this->dbPrefix . 'taskstimers.task_id = ' . $this->dbPrefix . 'tasks.id AND ' . $this->dbPrefix . 'taskstimers.staff_id = ' . $this->currentUser->staffid . ') as my_logged_time, (SELECT GROUP_CONCAT( CONCAT(firstname," ",lastname) SEPARATOR ",") FROM ' . $this->dbPrefix . 'task_assigned JOIN ' . $this->dbPrefix . 'staff ON ' . $this->dbPrefix . 'staff.staffid = ' . $this->dbPrefix . 'task_assigned.staffid WHERE taskid = ' . $this->dbPrefix . 'tasks.id ORDER BY ' . $this->dbPrefix . 'task_assigned.staffid) AS assignees, (SELECT GROUP_CONCAT(NAME SEPARATOR ",") FROM ' . $this->dbPrefix . 'taggables JOIN ' . $this->dbPrefix . 'tags ON ' . $this->dbPrefix . 'taggables.tag_id = ' . $this->dbPrefix . 'tags.id WHERE rel_id = ' . $this->dbPrefix . 'tasks.id AND rel_type = "task" ORDER BY tag_order ASC) AS tags, priority, rel_type, rel_id, recurring, recurring_type, repeat_every, cycles, total_cycles, hourly_rate, custom_recurring, description, (CASE rel_type WHEN "contract" THEN (SELECT SUBJECT FROM ' . $this->dbPrefix . 'contracts WHERE ' . $this->dbPrefix . 'contracts.id = ' . $this->dbPrefix . 'tasks.rel_id) WHEN "estimate" THEN (SELECT id FROM ' . $this->dbPrefix . 'estimates WHERE ' . $this->dbPrefix . 'estimates.id = ' . $this->dbPrefix . 'tasks.rel_id) WHEN "proposal" THEN (SELECT id FROM ' . $this->dbPrefix . 'proposals WHERE ' . $this->dbPrefix . 'proposals.id = ' . $this->dbPrefix . 'tasks.rel_id) WHEN "invoice" THEN (SELECT id FROM ' . $this->dbPrefix . 'invoices WHERE ' . $this->dbPrefix . 'invoices.id = ' . $this->dbPrefix . 'tasks.rel_id) WHEN "ticket" THEN (SELECT CONCAT(CONCAT(" # ", ' . $this->dbPrefix . 'tickets.ticketid)," - ",' . $this->dbPrefix . 'tickets.subject) FROM ' . $this->dbPrefix . 'tickets WHERE ' . $this->dbPrefix . 'tickets.ticketid = ' . $this->dbPrefix . 'tasks.rel_id ) WHEN "lead" THEN ( SELECT CASE ' . $this->dbPrefix . 'leads.email WHEN "" THEN ' . $this->dbPrefix . 'leads.name ELSE CONCAT( ' . $this->dbPrefix . 'leads.name, " - ",' . $this->dbPrefix . 'leads.email ) END FROM ' . $this->dbPrefix . 'leads WHERE ' . $this->dbPrefix . 'leads.id = ' . $this->dbPrefix . 'tasks.rel_id ) WHEN "customer" THEN ( SELECT CASE company WHEN "" THEN ( SELECT CONCAT(firstname," ",lastname) FROM ' . $this->dbPrefix . 'contacts WHERE userid = ' . $this->dbPrefix . 'clients.userid AND is_primary = 1 ) ELSE company END FROM ' . $this->dbPrefix . 'clients WHERE ' . $this->dbPrefix . 'clients.userid = ' . $this->dbPrefix . 'tasks.rel_id ) WHEN "project" THEN ( SELECT CONCAT( CONCAT( CONCAT(" # ",' . $this->dbPrefix . 'projects.id)," - ",' . $this->dbPrefix . 'projects.name )," - ",(SELECT CASE company WHEN "" THEN ( SELECT CONCAT(firstname," ",lastname) FROM ' . $this->dbPrefix . 'contacts WHERE userid = ' . $this->dbPrefix . 'clients.userid AND is_primary = 1 ) ELSE company END FROM ' . $this->dbPrefix . 'clients WHERE userid = ' . $this->dbPrefix . 'projects.clientid ) ) FROM ' . $this->dbPrefix . 'projects WHERE ' . $this->dbPrefix . 'projects.id = ' . $this->dbPrefix . 'tasks.rel_id ) WHEN "expense" THEN ( SELECT CASE expense_name WHEN "" THEN ' . $this->dbPrefix . 'expenses_categories.name ELSE CONCAT(' . $this->dbPrefix . 'expenses_categories.name,"(",' . $this->dbPrefix . 'expenses.expense_name,")") END FROM ' . $this->dbPrefix . 'expenses JOIN ' . $this->dbPrefix . 'expenses_categories ON ' . $this->dbPrefix . 'expenses_categories.id = ' . $this->dbPrefix . 'expenses.category WHERE ' . $this->dbPrefix . 'expenses.id = ' . $this->dbPrefix . 'tasks.rel_id) ELSE NULL END) AS rel_name, billed, billable, (SELECT staffid FROM ' . $this->dbPrefix . 'task_assigned WHERE taskid = ' . $this->dbPrefix . 'tasks.id AND staffid = 1) AS is_assigned, (SELECT GROUP_CONCAT(staffid SEPARATOR ",") FROM ' . $this->dbPrefix . 'task_assigned WHERE taskid = ' . $this->dbPrefix . 'tasks.id ORDER BY ' . $this->dbPrefix . 'task_assigned.staffid ) AS assignees_ids, (SELECT MAX(id) FROM ' . $this->dbPrefix . 'taskstimers WHERE task_id = ' . $this->dbPrefix . 'tasks.id AND staff_id = 1 AND end_time IS NULL) AS not_finished_timer_by_current_staff, (SELECT staffid FROM ' . $this->dbPrefix . 'task_assigned WHERE taskid = ' . $this->dbPrefix . 'tasks.id AND staffid = 1) AS current_user_is_assigned, (SELECT CASE WHEN addedfrom = 1 AND is_added_from_contact = 0 THEN 1 ELSE 0 END) AS current_user_is_creator FROM ' . $this->dbPrefix . 'tasks WHERE (STATUS IN (1,4,3,2)) AND CASE WHEN rel_type = "project" AND rel_id IN (SELECT project_id FROM ' . $this->dbPrefix . 'project_settings WHERE project_id = rel_id AND NAME = "hide_tasks_on_main_tasks_table" AND VALUE = 1 ) THEN rel_type != "project" ELSE 1 = 1 END AND ' . $customWhereCondition . ' ORDER BY duedate ASC LIMIT ' . $start_from . ',' . $end_to . ';';
                $tasks = $this->db->query($query)->result_array();
                $response = ['status' => 1, 'tasks' => $tasks];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function get_reminders()
    {
        $requiredFieldsArray = ['authentication_token', 'start_from', 'end_to'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $form_data = $this->formData;
                $rel_id = $form_data['rel_id'];
                $rel_type = $form_data['rel_type'];

                $date_from = isset($form_data['date_from']) ? $form_data['date_from'] : "";
                $date_to = isset($form_data['date_to']) ? $form_data['date_to'] : "";

                if ($date_from != "" && $date_to != "") {
                    $this->db->where('dateadded >=', date("Y-m-d", strtotime($date_from)) . " 00:00:00");
                    $this->db->where('dateadded <=', date("Y-m-d", strtotime($date_to)) . " 23:59:59");
                }

                $this->db->select($this->dbPrefix . 'reminders.*,' . $this->dbPrefix . 'staff.firstname as firstname,' . $this->dbPrefix . 'staff.lastname as lastname');
                $this->db->join($this->dbPrefix . 'staff', $this->dbPrefix . 'reminders.staff=' . $this->dbPrefix . 'staff.staffid', 'left');
                $this->db->where('rel_id', $rel_id);
                $this->db->where('rel_type', $rel_type);
                $this->db->order_by($this->dbPrefix . 'reminders.id', 'DESC');

                $reminders = $this->db->get($this->dbPrefix . 'reminders')->result_array();
                $response = ['status' => 1, 'reminders' => $reminders];
                $this->log_api($response);

            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function get_lead_notes()
    {
        $requiredFieldsArray = ['authentication_token', 'start_from', 'end_to'];
        if ($this->check_form_validation($requiredFieldsArray)) {

            if ($this->currentUser) {
                $form_data = $this->formData;
                $rel_id = $form_data['rel_id'];
                $rel_type = $form_data['rel_type'];

                $date_from = isset($form_data['date_from']) ? $form_data['date_from'] : "";
                $date_to = isset($form_data['date_to']) ? $form_data['date_to'] : "";

                if ($date_from != "" && $date_to != "") {
                    $this->db->where('dateadded >=', date("Y-m-d", strtotime($date_from)) . " 00:00:00");
                    $this->db->where('dateadded <=', date("Y-m-d", strtotime($date_to)) . " 23:59:59");
                }

                $this->db->select($this->dbPrefix . 'notes.*,' . $this->dbPrefix . 'staff.firstname as firstname,' . $this->dbPrefix . 'staff.lastname as lastname');
                $this->db->join($this->dbPrefix . 'staff', $this->dbPrefix . 'notes.addedfrom=' . $this->dbPrefix . 'staff.staffid', 'left');
                $this->db->where('rel_id', $rel_id);
                $this->db->where('rel_type', $rel_type);
                $this->db->order_by($this->dbPrefix . 'notes.id', 'DESC');
                $notes = $this->db->get($this->dbPrefix . 'notes')->result_array();
                $response = ['status' => 1, 'notes' => $notes];
                $this->log_api($response);

            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function delete_reminder()
    {
        $requiredFieldsArray = ['authentication_token', 'id'];
        if ($this->check_form_validation($requiredFieldsArray)) {

            if ($this->currentUser) {
                $form_data = $this->formData;
                $id = $form_data['id'];

                $date_from = isset($form_data['date_from']) ? $form_data['date_from'] : "";
                $date_to = isset($form_data['date_to']) ? $form_data['date_to'] : "";

                if ($date_from != "" && $date_to != "") {
                    $this->db->where('dateadded >=', date("Y-m-d", strtotime($date_from)) . " 00:00:00");
                    $this->db->where('dateadded <=', date("Y-m-d", strtotime($date_to)) . " 23:59:59");
                }

                $this->load->model('Misc_model');
                $deleted = $this->Misc_model->delete_reminder($id);
                if ($deleted)
                    $response = ['status' => 1, 'message' => "Reminder deleted"];
                else
                    $response = ['status' => 0, 'message' => "Cannot delete reminder"];
                $this->log_api($response);

            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function get_assginees()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->db->select('staffid,firstname,lastname');
                $assignees = $this->db->get($this->dbPrefix . 'staff')->result_array();
                $response = ['status' => 0, 'message' => 'Staff not found'];
                if ($assignees) {
                    $response = ['status' => 1, 'message' => 'Staff found', 'assignees' => $assignees];
                }
                $this->log_api($response);
            }
        }
    }


    /**
     * @return void
     */
    public function edit_reminders()
    {
        $requiredFieldsArray = ['authentication_token', 'notify_by_email', 'id', 'date', 'description'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $response = ['status' => 0, 'message' => 'An error occurred'];
                $form_data = $this->formData;
                $id = isset($form_data['id']) ? $form_data['id'] : "";

                $saving_data = [];

                if (isset($form_data['notify_by_email'])) {
                    $saving_data['notify_by_email'] = 1;
                } else {
                    $saving_data['notify_by_email'] = 0;
                }

                $saving_data['date'] = to_sql_date($form_data['date'], true);
                $saving_data['description'] = nl2br($form_data['description']);

                $this->db->where('id', $id);
                $this->db->update($this->dbPrefix . 'reminders', $saving_data);

                if ($this->db->affected_rows() > 0) {
                    $response = ['status' => 1, 'message' => 'Reminder Updated Successfully'];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function add_reminders()
    {
        $requiredFieldsArray = ['authentication_token', 'notify_by_email', 'date', 'description', 'rel_type', 'rel_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $response = ['status' => 0, 'message' => 'An error occurred'];
                $form_data = $this->formData;
                $saving_data = [];
                if (isset($form_data['notify_by_email'])) {
                    $saving_data['notify_by_email'] = 1;
                } else {
                    $saving_data['notify_by_email'] = 0;
                }

                $saving_data['date'] = to_sql_date($form_data['date'], true);
                $saving_data['description'] = nl2br($form_data['description']);
                $saving_data['staff'] = $form_data['staffid'];
                $saving_data['rel_type'] = $form_data['rel_type'];
                $saving_data['rel_id'] = $form_data['rel_id'];

                $this->db->insert($this->dbPrefix . 'reminders', $saving_data);

                if ($this->db->affected_rows() > 0) {
                    $response = ['status' => 1, 'message' => 'Reminder Added Successfully'];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function add_notes()
    {
        $requiredFieldsArray = ['authentication_token', 'rel_type', 'rel_id', 'description', 'date_contacted'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $response = ['status' => 0, 'message' => 'An error occurred'];
                $form_data = $this->formData;
                $saving_data = [];

                $date_contacted = null;
                if (isset($form_data['date_contacted']) && $form_data['date_contacted'] != "") {
                    $date_contacted = $form_data['date_contacted'];
                }

                $saving_data['dateadded'] = date("Y-m-d H:i:s");
                $saving_data['description'] = nl2br($form_data['description']);
                $saving_data['addedfrom'] = $form_data['staffid'];
                $saving_data['rel_type'] = $form_data['rel_type'];
                $saving_data['rel_id'] = $form_data['rel_id'];
                $saving_data['date_contacted'] = $date_contacted;

                $this->db->insert($this->dbPrefix . 'notes', $saving_data);

                if ($this->db->affected_rows() > 0) {
                    $response = ['status' => 1, 'message' => 'Note Added Successfully'];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function edit_notes()
    {
        $requiredFieldsArray = ['authentication_token', 'id', 'description'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $response = ['status' => 0, 'message' => 'An error occurred'];
                $form_data = $this->formData;
                $saving_data['description'] = nl2br($form_data['description']);
                $this->db->where('id', $form_data['id']);
                $this->db->update($this->dbPrefix . 'notes', $saving_data);

                if ($this->db->affected_rows() > 0) {
                    $response = ['status' => 1, 'message' => 'Note Updated Successfully'];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    public function delete_notes()
    {
        $requiredFieldsArray = ['authentication_token', 'id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $response = ['status' => 0, 'message' => 'An error occurred'];
                $form_data = $this->formData;

                $this->db->where('id', $form_data['id']);
                $this->db->delete($this->dbPrefix . 'notes');

                if ($this->db->affected_rows() > 0) {
                    $response = ['status' => 1, 'message' => 'Note Deleted Successfully'];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    public function convert_to_customer()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'create')) {
                    $response = ['status' => 0, 'message' => 'An error occurred'];
                    $default_country = get_option('customer_default_country');
                    $data = $this->input->post();
                    $data['password'] = $this->input->post('password', false);

                    $original_lead_email = $data['original_lead_email'];
                    unset($data['original_lead_email']);
                    unset($data['authentication_token']);
                    unset($data['staffid']);
                    $this->load->model('leads_model');
                    $this->load->model('misc_model');
                    $this->load->model('clients_model');
                    if (isset($data['transfer_notes'])) {
                        $notes = $this->misc_model->get_notes($data['leadid'], 'lead');
                        unset($data['transfer_notes']);
                    }

                    if (isset($data['transfer_consent'])) {
                        $this->load->model('gdpr_model');
                        $consents = $this->gdpr_model->get_consents(['lead_id' => $data['leadid']]);
                        unset($data['transfer_consent']);
                    }

                    if (isset($data['merge_db_fields'])) {
                        $merge_db_fields = $data['merge_db_fields'];
                        unset($data['merge_db_fields']);
                    }

                    if (isset($data['merge_db_contact_fields'])) {
                        $merge_db_contact_fields = $data['merge_db_contact_fields'];
                        unset($data['merge_db_contact_fields']);
                    }

                    if (isset($data['include_leads_custom_fields'])) {
                        $include_leads_custom_fields = $data['include_leads_custom_fields'];
                        unset($data['include_leads_custom_fields']);
                    }

                    if ($data['country'] == '' && $default_country != '') {
                        $data['country'] = $default_country;
                    }

                    $data['billing_street'] = $data['address'];
                    $data['billing_city'] = $data['city'];
                    $data['billing_state'] = $data['state'];
                    $data['billing_zip'] = $data['zip'];
                    $data['billing_country'] = $data['country'];

                    $data['is_primary'] = 1;
                    $id = $this->clients_model->add($data, true);
                    if ($id) {
                        $primary_contact_id = get_primary_contact_user_id($id);

                        if (isset($notes)) {
                            foreach ($notes as $note) {
                                $this->db->insert($this->dbPrefix . 'notes', [
                                    'rel_id' => $id,
                                    'rel_type' => 'customer',
                                    'dateadded' => $note['dateadded'],
                                    'addedfrom' => $note['addedfrom'],
                                    'description' => $note['description'],
                                    'date_contacted' => $note['date_contacted'],
                                ]);
                            }
                        }
                        if (isset($consents)) {
                            foreach ($consents as $consent) {
                                unset($consent['id']);
                                unset($consent['purpose_name']);
                                $consent['lead_id'] = 0;
                                $consent['contact_id'] = $primary_contact_id;
                                $this->gdpr_model->add_consent($consent);
                            }
                        }
                        if (!has_permission('customers', '', 'view') && get_option('auto_assign_customer_admin_after_lead_convert') == 1) {
                            $this->db->insert($this->dbPrefix . 'customer_admins', [
                                'date_assigned' => date('Y-m-d H:i:s'),
                                'customer_id' => $id,
                                'staff_id' => get_staff_user_id(),
                            ]);
                        }
                        $this->leads_model->log_lead_activity($data['leadid'], 'not_lead_activity_converted', false, serialize([
                            get_staff_full_name(),
                        ]));
                        $default_status = $this->leads_model->get_status('', [
                            'isdefault' => 1,
                        ]);
                        $this->db->where('id', $data['leadid']);
                        $this->db->update($this->dbPrefix . 'leads', [
                            'date_converted' => date('Y-m-d H:i:s'),
                            'status' => $default_status[0]['id'],
                            'junk' => 0,
                            'lost' => 0,
                            'client_id' => $id
                        ]);
                        // Check if lead email is different then client email
                        $contact = $this->clients_model->get_contact(get_primary_contact_user_id($id));
                        if ($contact->email != $original_lead_email) {
                            if ($original_lead_email != '') {
                                $this->leads_model->log_lead_activity($data['leadid'], 'not_lead_activity_converted_email', false, serialize([
                                    $original_lead_email,
                                    $contact->email,
                                ]));
                            }
                        }
                        if (isset($include_leads_custom_fields)) {
                            foreach ($include_leads_custom_fields as $fieldid => $value) {
                                // checked don't merge
                                if ($value == 5) {
                                    continue;
                                }
                                // get the value of this leads custom fiel
                                $this->db->where('relid', $data['leadid']);
                                $this->db->where('fieldto', 'leads');
                                $this->db->where('fieldid', $fieldid);
                                $lead_custom_field_value = $this->db->get($this->dbPrefix . 'customfieldsvalues')->row()->value;
                                // Is custom field for contact ot customer
                                if ($value == 1 || $value == 4) {
                                    if ($value == 4) {
                                        $field_to = 'contacts';
                                    } else {
                                        $field_to = 'customers';
                                    }
                                    $this->db->where('id', $fieldid);
                                    $field = $this->db->get($this->dbPrefix . 'customfields')->row();
                                    // check if this field exists for custom fields
                                    $this->db->where('fieldto', $field_to);
                                    $this->db->where('name', $field->name);
                                    $exists = $this->db->get($this->dbPrefix . 'customfields')->row();
                                    $copy_custom_field_id = null;
                                    if ($exists) {
                                        $copy_custom_field_id = $exists->id;
                                    } else {
                                        // there is no name with the same custom field for leads at the custom side create the custom field now
                                        $this->db->insert($this->dbPrefix . 'customfields', [
                                            'fieldto' => $field_to,
                                            'name' => $field->name,
                                            'required' => $field->required,
                                            'type' => $field->type,
                                            'options' => $field->options,
                                            'display_inline' => $field->display_inline,
                                            'field_order' => $field->field_order,
                                            'slug' => slug_it($field_to . '_' . $field->name, [
                                                'separator' => '_',
                                            ]),
                                            'active' => $field->active,
                                            'only_admin' => $field->only_admin,
                                            'show_on_table' => $field->show_on_table,
                                            'bs_column' => $field->bs_column,
                                        ]);
                                        $new_customer_field_id = $this->db->insert_id();
                                        if ($new_customer_field_id) {
                                            $copy_custom_field_id = $new_customer_field_id;
                                        }
                                    }
                                    if ($copy_custom_field_id != null) {
                                        $insert_to_custom_field_id = $id;
                                        if ($value == 4) {
                                            $insert_to_custom_field_id = get_primary_contact_user_id($id);
                                        }
                                        $this->db->insert($this->dbPrefix . 'customfieldsvalues', [
                                            'relid' => $insert_to_custom_field_id,
                                            'fieldid' => $copy_custom_field_id,
                                            'fieldto' => $field_to,
                                            'value' => $lead_custom_field_value,
                                        ]);
                                    }
                                } elseif ($value == 2) {
                                    if (isset($merge_db_fields)) {
                                        $db_field = $merge_db_fields[$fieldid];
                                        // in case user don't select anything from the db fields
                                        if ($db_field == '') {
                                            continue;
                                        }
                                        if ($db_field == 'country' || $db_field == 'shipping_country' || $db_field == 'billing_country') {
                                            $this->db->where('iso2', $lead_custom_field_value);
                                            $this->db->or_where('short_name', $lead_custom_field_value);
                                            $this->db->or_like('long_name', $lead_custom_field_value);
                                            $country = $this->db->get($this->dbPrefix . 'countries')->row();
                                            if ($country) {
                                                $lead_custom_field_value = $country->country_id;
                                            } else {
                                                $lead_custom_field_value = 0;
                                            }
                                        }
                                        $this->db->where('userid', $id);
                                        $this->db->update($this->dbPrefix . 'clients', [
                                            $db_field => $lead_custom_field_value,
                                        ]);
                                    }
                                } elseif ($value == 3) {
                                    if (isset($merge_db_contact_fields)) {
                                        $db_field = $merge_db_contact_fields[$fieldid];
                                        if ($db_field == '') {
                                            continue;
                                        }
                                        $this->db->where('id', $primary_contact_id);
                                        $this->db->update($this->dbPrefix . 'contacts', [
                                            $db_field => $lead_custom_field_value,
                                        ]);
                                    }
                                }
                            }
                        }
                        // set the lead to status client in case is not status client
                        $this->db->where('isdefault', 1);
                        $status_client_id = $this->db->get($this->dbPrefix . 'leads_status')->row()->id;
                        $this->db->where('id', $data['leadid']);
                        $this->db->update($this->dbPrefix . 'leads', [
                            'status' => $status_client_id,
                        ]);


                        if (is_gdpr() && get_option('gdpr_after_lead_converted_delete') == '1') {
                            // When lead is deleted
                            // move all proposals to the actual customer record
                            $this->db->where('rel_id', $data['leadid']);
                            $this->db->where('rel_type', 'lead');
                            $this->db->update('proposals', [
                                'rel_id' => $id,
                                'rel_type' => 'customer',
                            ]);

                            $this->leads_model->delete($data['leadid']);

                            $this->db->where('userid', $id);
                            $this->db->update($this->dbPrefix . 'clients', ['leadid' => null]);
                        }

                        log_activity('Created Lead Client Profile [LeadID: ' . $data['leadid'] . ', ClientID: ' . $id . ']');
                        $response = ['status' => 1, 'message' => 'Lead converted to customer'];;
                        hooks()->do_action('lead_converted_to_customer', ['lead_id' => $data['leadid'], 'customer_id' => $id]);
                    }
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('customers', 'create');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    public function get_related_type()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $customers = $this->get_customers();
                $leads = $this->get_leads();
                $response = ['status' => 1, 'message' => 'Data found', 'leads' => $leads, 'customers' => $customers];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    private function get_customers()
    {
        return $this->db->get($this->dbPrefix . 'clients')->result();
    }

    private function get_all_staff()
    {
        return $this->db->get_where($this->dbPrefix . 'staff', ['active' => 1])->result();
    }

    private function get_leads()
    {
        return $this->db->get($this->dbPrefix . 'leads')->result();
    }

    /**
     * @return void
     */
    public function get_my_customers()
    {
        $requiredFieldsArray = ['authentication_token', 'start_from', 'end_to'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'view')) {
                    $query = 'SELECT ' . $this->dbPrefix . 'clients.userid as userid, company, firstname, email, ' . $this->dbPrefix . 'clients.phonenumber as phonenumber, `' . $this->dbPrefix . 'clients`.`active` AS `' . $this->dbPrefix . 'clients_active`, (SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . $this->dbPrefix . 'customer_groups JOIN ' . $this->dbPrefix . 'customers_groups ON ' . $this->dbPrefix . 'customer_groups.groupid = ' . $this->dbPrefix . 'customers_groups.id WHERE customer_id = ' . $this->dbPrefix . 'clients.userid ORDER by name ASC) as customerGroups, ' . $this->dbPrefix . 'clients.datecreated as datecreated ,' . $this->dbPrefix . 'contacts.id as contact_id,lastname,' . $this->dbPrefix . 'clients.zip as zip,registration_confirmed FROM ' . $this->dbPrefix . 'clients LEFT JOIN ' . $this->dbPrefix . 'contacts ON ' . $this->dbPrefix . 'contacts.userid=' . $this->dbPrefix . 'clients.userid AND ' . $this->dbPrefix . 'contacts.is_primary=1 WHERE (' . $this->dbPrefix . 'clients.active = 1 OR ' . $this->dbPrefix . 'clients.active=0 AND registration_confirmed = 0) ORDER BY userid DESC';
                    $customers = $this->db->query($query)->result_array();
                    $response = ['status' => 1, 'message' => 'Customers found', 'customers' => $customers];
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('customers', 'view');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    /**
     * @return void
     */
    public function get_notes()
    {
        $requiredFieldsArray = ['authentication_token', 'relId', 'relType'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->db->where('rel_type', $this->formData['relType']);
                $this->db->where('rel_id', $this->formData['relId']);
                $notes = $this->db->get($this->dbPrefix . 'notes')->result_array();
                $response = ['status' => 1, 'message' => 'Notes loaded', 'notes' => $notes];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function add_note()
    {
        $requiredFieldsArray = ['authentication_token', 'relId', 'relType', 'description'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('Misc_model');
                $id = $this->Misc_model->add_note(['description' => $this->formData['description']], $this->formData['relType'], $this->formData['relId']);
                if ($id)
                    $response = ['status' => 1, 'message' => 'Note added'];
                else
                    $response = ['status' => 0, 'message' => 'Cannot add note'];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function update_note()
    {
        $requiredFieldsArray = ['authentication_token', 'noteId', 'description'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('Misc_model');
                $id = $this->Misc_model->edit_note(['description' => $this->formData['description']], $this->formData['noteId']);
                if ($id)
                    $response = ['status' => 1, 'message' => 'Note updated'];
                else
                    $response = ['status' => 0, 'message' => 'Cannot update note'];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function delete_note()
    {
        $requiredFieldsArray = ['authentication_token', 'noteId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('Misc_model');
                $id = $this->Misc_model->delete_note($this->formData['noteId']);
                if ($id)
                    $response = ['status' => 1, 'message' => 'Note deleted'];
                else
                    $response = ['status' => 0, 'message' => 'Cannot delete note'];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    /**
     * @return void
     */
    public function get_initial_customer_form()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $response = ['status' => 1, 'message' => 'No data found!'];
                $groups = $this->db->order_by('id', 'DESC')->get($this->dbPrefix . 'customers_groups')->result_array();
                if ($groups) {
                    $response['groups'] = $groups;
                    $response['message'] = "Data found";
                } else {
                    $response['groups'] = null;
                }
                $this->db->order_by('short_name', 'ASC');
                $countries = $this->db->get($this->dbPrefix . 'countries')->result_array();
                if ($countries) {
                    $response['countries'] = $countries;
                    $response['message'] = "Data found";
                } else {
                    $response['countries'] = null;
                }

                if ($this->currentUser->admin == 0) {
                    $this->db->where('only_admin', 0);
                }
                $this->db->where('fieldto', 'customers');
                $customFields = $this->db->get($this->dbPrefix . 'customfields')->result_array();

                if ($customFields) {
                    $response['customFields'] = $customFields;
                    $response['message'] = "Data found";
                } else {
                    $response['customFields'] = null;
                }

                $response['defaultCountry'] = intval(get_option('customer_default_country'));
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    /**
     * @return void
     */
    public function addCustomer()
    {
        $requiredFieldsArray = ['authentication_token', 'company'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'create')) {
                    try {
                        $data = $this->formData;
                        $save_and_add_contact = false;
                        if (isset($data['save_and_add_contact'])) {
                            unset($data['save_and_add_contact']);
                            $save_and_add_contact = true;
                        }
                        $this->load->model('clients_model');
                        unset($data['staffid']);
                        $data['groups_in'] = [];
                        if (isset($data['groups_in']) && $data['groups_in']) {
                            $data['groups_in'] = explode(",", $data['groups_in']);
                        }
                        $id = $this->clients_model->add($data);
                        if (!has_permission('customers', '', 'view')) {
                            $assign['customer_admins'] = [];
                            $assign['customer_admins'][] = get_staff_user_id();
                            $this->clients_model->assign_admins($assign, $id);
                        }
                        if ($id) {
                            $customer = $this->db->get_where($this->dbPrefix . 'clients', [
                                'userid' => $id
                            ])->row();
                            $this->log_api(['status' => 1, 'message' => _l('added_successfully', _l('client')), 'id' => $id, 'customer' => $customer, 'saveAndAddContact' => $save_and_add_contact]);
                        } else {
                            $this->log_api(['status' => 0, 'message' => 'Cannot add customer']);
                        }
                    } catch (\Throwable $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot add customer: ' . $th]);
                    }
                } else {
                    $this->permission_not_allowed('customers', 'create');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }


    /**
     * @return void
     */
    public function get_customer()
    {
        $requiredFieldsArray = ['authentication_token', 'group', 'userId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'view')) {
                    try {
                        $response = ['status' => 0, 'message' => 'No data found!'];
                        $this->load->model('Clients_model');
                        $client = $this->Clients_model->get($this->formData['userId']);
                        if ($client) {
                            $client = (array)$client;
                            $response['status'] = 1;
                            $response['message'] = 'Customer found!';
                            $response['data']['client'] = $client;
                            $response['data']['group'] = $this->formData['group'];
                            $contacts = $this->db->order_by('id', 'DESC')->get_where($this->dbPrefix . 'contacts', ['userid' => $this->formData['userId']])->result_array();
                            if ($contacts) {
                                $response['data']['contacts'] = $contacts;
                            } else {
                                $response['data']['contacts'] = null;
                            }
                            $currencies = $this->db->order_by('id', 'DESC')->get($this->dbPrefix . 'currencies')->result_array();
                            if ($currencies) {
                                $response['data']['currencies'] = $currencies;
                            } else {
                                $response['data']['currencies'] = null;
                            }
                            $customer_admins = $this->db->get_where($this->dbPrefix . 'customer_admins', ['customer_id' => $this->formData['userId'], 'staff_id' => $this->formData['staffid']])->result_array();
                            if ($customer_admins) {
                                $response['data']['customer_admins'] = $customer_admins;
                            } else {
                                $response['data']['customer_admins'] = null;
                            }

                            $customer_currency = $this->db->get_where($this->dbPrefix . 'currencies', ['id' => $client['default_currency']])->row();
                            if ($customer_currency) {
                                $response['data']['customer_currency'] = $customer_currency;
                            } else {
                                $response['data']['customer_currency'] = null;
                            }

                            $customer_groups = $this->db->order_by('id', 'DESC')->get_where($this->dbPrefix . 'customer_groups', ['customer_id' => $client['userid']])->result_array();
                            if ($customer_groups) {
                                $response['data']['customer_groups'] = $customer_groups;
                            } else {
                                $response['data']['customer_groups'] = null;
                            }

                            $groups = $this->db->order_by('id', 'DESC')->get($this->dbPrefix . 'customers_groups')->result_array();
                            if ($groups) {
                                $response['data']['groups'] = $groups;
                            } else {
                                $response['data']['groups'] = null;
                            }

                            $staff = $this->db->order_by('staffid', 'DESC')->get($this->dbPrefix . 'staff')->result_array();
                            if ($staff) {
                                $response['data']['staff'] = $staff;
                            } else {
                                $response['data']['staff'] = null;
                            }

                            $countries = $this->db->get($this->dbPrefix . 'countries')->result_array();
                            if ($countries) {
                                $response['data']['countries'] = $countries;
                            } else {
                                $response['data']['countries'] = null;
                            }
                        }
                        $this->log_api($response);
                    } catch (Exception $exception) {
                        $response = ['status' => 0, 'message' => $exception->getMessage()];
                        $this->log_api($response);
                    }
                } else {
                    $this->permission_not_allowed('customers', 'view');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    /**
     * @return void
     */
    public function updateCustomer()
    {
        $requiredFieldsArray = ['authentication_token', 'company', 'clientId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'edit')) {
                    try {
                        $data = $this->formData;
                        if (isset($data['save_and_add_contact'])) {
                            unset($data['save_and_add_contact']);
                        }
                        $this->load->model('clients_model');
                        unset($data['staffid']);
                        if (isset($data['groups_in']) && $data['groups_in']) {
                            $data['groups_in'] = explode(",", $data['groups_in']);
                        }
                        $clientId = $data['clientId'];
                        unset($data['clientId']);
                        $this->clients_model->update($data, $clientId);
                        $customer = $this->db->get_where($this->dbPrefix . 'clients', [
                            'userid' => $clientId
                        ])->row();
                        $this->log_api(['status' => 1, 'message' => 'Customer updated', 'id' => $clientId, 'customer' => $customer]);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot update customer: ' . $th->getLine()]);
                    }
                } else {
                    $this->permission_not_allowed('customers', 'edit');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function addContact()
    {
        $requiredFieldsArray = ['authentication_token', 'userId', 'firstname', 'lastname', 'email'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'create')) {
                    try {
                        $contact = $this->db->order_by('id', 'DESC')->get_where($this->dbPrefix . 'contacts', ['email' => $this->formData['email']])->row();
                        $response = ['status' => 0, 'message' => 'Cannot add contact'];
                        if ($contact) {
                            $response['message'] = "Email already exist!";
                        } else {
                            $this->load->model('clients_model');
                            $data = $this->formData;
                            $contactId = $this->formData['userId'];
                            unset($data['userId']);
                            unset($data['staffid']);
                            $id = $this->clients_model->add_contact($data, $contactId);
                            if ($id) {
                                handle_contact_profile_image_upload($id);
                                $response = ['status' => 1, 'message' => 'Contact added!'];
                            }
                        }
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot add contacts: ' . $th->getLine()]);
                    }
                } else {
                    $this->permission_not_allowed('customers', 'create');
                }
            }
        }
    }

    public function editContact()
    {
        $requiredFieldsArray = ['authentication_token', 'userId', 'firstname', 'lastname', 'email'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'edit')) {
                    try {
                        $response = ['status' => 0, 'message' => 'Cannot edit contact'];
                        $this->load->model('clients_model');
                        $data = $this->formData;
                        $contactId = $this->formData['userId'];
                        unset($data['userId']);
                        unset($data['staffid']);
                        $id = $this->clients_model->update_contact($data, $contactId);
                        if ($id) {
                            handle_contact_profile_image_upload($id);
                            $response = ['status' => 1, 'message' => 'Contact updated!'];
                        }
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot edit contacts: ' . $th->getLine()]);
                    }
                } else {
                    $this->permission_not_allowed('customers', 'edit');
                }
            }
        }
    }

    public function get_contacts()
    {
        $requiredFieldsArray = ['userId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('customers', 'view')) {
                    try {
                        $contacts = $this->db->order_by('id', 'DESC')->get_where($this->dbPrefix . 'contacts', ['userid' => $this->formData['userId']])->result_array();
                        $response = ['status' => 0, 'message' => 'No contacts available', 'contacts' => null];
                        if ($contacts) {
                            $response['message'] = "Contacts found";
                            $response['contacts'] = $contacts;
                            $response['status'] = 1;
                        }
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot get contacts: ' . $th->getLine()]);
                    }
                } else {
                    $this->permission_not_allowed('customers', 'view');
                }
            }
        }
    }

    public function get_tickets()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $this->db->select($this->dbPrefix . 'tickets.*,' . $this->dbPrefix . 'departments.name as departmentName');
                    $this->db->join($this->dbPrefix . 'departments', $this->dbPrefix . 'departments.departmentid = ' . $this->dbPrefix . 'tickets.department', 'left');
                    if ($this->currentUser->admin == 0) {
                        $this->db->group_start();
                        $this->db->where('assigned', $this->formData['staffid']);
                        $this->db->or_where('admin', $this->formData['staffid']);
                        $this->db->group_end();
                    }
                    $tickets = $this->db->order_by('ticketid', 'DESC')->get($this->dbPrefix . 'tickets')->result_array();
                    $response = ['status' => 0, 'message' => 'No tickets available', 'tickets' => null];
                    if ($tickets) {
                        $response['message'] = "Tickets found";
                        $response['tickets'] = $tickets;
                        $response['status'] = 1;
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot get tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function load_initial_ticket_data()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $staff = $this->db->order_by('firstname', 'ASC')->get($this->dbPrefix . 'staff')->result_array();
                    $response = ['status' => 0, 'message' => 'No data available'];
                    if ($staff) {
                        $response['message'] = "Data loaded";
                        $response['staff'] = $staff;
                        $response['status'] = 1;
                    }

                    $contacts = $this->db->order_by('firstname', 'ASC')->get($this->dbPrefix . 'contacts')->result_array();
                    if ($contacts) {
                        $response['contacts'] = $contacts;
                        $response['status'] = 1;
                    }

                    $services = $this->db->order_by('name', 'ASC')->get($this->dbPrefix . 'services')->result_array();
                    if ($services) {
                        $response['services'] = $services;
                        $response['status'] = 1;
                    }

                    $departments = $this->db->order_by('name', 'ASC')->get($this->dbPrefix . 'departments')->result_array();
                    if ($departments) {
                        $response['departments'] = $departments;
                        $response['status'] = 1;
                    }

                    $preDefinedReplies = $this->db->order_by('id', 'DESC')->get($this->dbPrefix . 'tickets_predefined_replies')->result_array();
                    if ($departments) {
                        $response['preDefinedReplies'] = $preDefinedReplies;
                        $response['status'] = 1;
                    }

                    $this->db->select($this->dbPrefix . 'knowledge_base.*,' . $this->dbPrefix . 'knowledge_base_groups.*');
                    $this->db->join($this->dbPrefix . 'knowledge_base_groups', $this->dbPrefix . 'knowledge_base_groups.groupid=' . $this->dbPrefix . 'knowledge_base.articleid', 'left');
                    $knowledgeBase = $this->db->get($this->dbPrefix . 'knowledge_base')->result_array();
                    if ($knowledgeBase) {
                        $response['knowledgeBase'] = $knowledgeBase;
                        $response['status'] = 1;
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot get tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function add_ticket()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 0, 'message' => 'Cannot add ticket'];
                    $this->load->model('tickets_model');
                    $formData = $this->formData;
                    unset($formData['staffid']);
                    $formData['message'] = html_purify($formData['message']);
                    $id = $this->tickets_model->add($formData, $this->currentUser->staffid);
                    if ($id) {
                        $response = ['status' => 1, 'message' => _l('new_ticket_added_successfully', $id)];
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot add tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function update_ticket()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 0, 'message' => 'Cannot add ticket'];
                    $this->load->model('tickets_model');
                    $formData = $this->formData;
                    unset($formData['staffid']);
                    $formData['message'] = html_purify($formData['message']);
                    $id = $this->tickets_model->update_single_ticket_settings($formData);
                    if ($id) {
                        $response = ['status' => 1, 'message' => _l('new_ticket_added_successfully', $id)];
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot add tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function delete_ticket()
    {
        $requiredFieldsArray = ['authentication_token', 'ticketId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 0, 'message' => 'Cannot add ticket'];
                    $this->load->model('tickets_model');
                    $id = $this->tickets_model->delete($this->formData['ticketId']);
                    if ($id) {
                        $response = ['status' => 1, 'message' => 'Ticket deleted'];
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot add tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function get_ticket_replies()
    {
        $requiredFieldsArray = ['authentication_token', 'ticketId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 0, 'message' => 'No Replies found!'];
                    $this->db->select($this->dbPrefix . 'ticket_attachments.file_name as fileName,' . $this->dbPrefix . 'ticket_attachments.filetype as fileType,' . $this->dbPrefix . 'ticket_replies.*');
                    $this->db->order_by($this->dbPrefix . 'ticket_replies.id', 'DESC');
                    $this->db->join($this->dbPrefix . 'ticket_attachments', $this->dbPrefix . 'ticket_attachments.ticketid=' . $this->dbPrefix . 'ticket_replies.id', 'left');
                    $replies = $this->db->get_where($this->dbPrefix . 'ticket_replies', [$this->dbPrefix . 'ticket_replies.ticketid' => $this->formData['ticketId']])->result_array();
                    if ($replies) {
                        $response = ['status' => 1, 'message' => 'Replies found!', 'replies' => $replies];
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot add tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function add_ticket_reply()
    {
        $requiredFieldsArray = ['authentication_token', 'ticketId', 'message'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 0, 'message' => 'Cannot add ticket reply'];
                    $ticket = $this->db->get_where($this->dbPrefix . 'tickets', ['ticketid' => $this->formData['ticketId']])->row();
                    $this->db->insert($this->dbPrefix . 'ticket_replies', [
                        'ticketid' => $this->formData['ticketId'],
                        'userid' => $ticket->userid,
                        'contactid' => $ticket->contactid,
                        'name' => $ticket->name,
                        'email' => $ticket->email,
                        'date' => date("Y-m-d H:i:s"),
                        'message' => $this->formData['message']
                    ]);

                    $insertId = $this->db->insert_id();
                    if ($insertId) {
                        $response = ['status' => 1, 'message' => 'Reply added successfully!'];
                    }
                    log_activity('New Ticket Reply [ReplyID: ' . $insertId . ']');

                    $this->db->where('ticketid', $this->formData['ticketId']);
                    $this->db->update($this->dbPrefix . 'tickets', [
                        'lastreply' => date('Y-m-d H:i:s'),
                        'status' => $ticket->status,
                        'adminread' => 0,
                        'clientread' => 0,
                    ]);

                    if (isset($_FILES['attachments']) && count($_FILES['attachments']) > 0) {
                        $uploadedFilesArray = handle_ticket_attachments($this->formData['ticketId']);
                        foreach ($uploadedFilesArray as $uploadedFile) {
                            $fileArray = explode(".", $uploadedFile['file_name']);
                            $this->db->insert($this->dbPrefix . 'ticket_attachments', [
                                'ticketid' => $this->formData['ticketId'],
                                'replyid' => $insertId,
                                'file_name' => $uploadedFile['file_name'],
                                'filetype' => "image/" . $fileArray[(count($fileArray) - 1)],
                                'dateadded' => date("Y-m-d H:i:s")
                            ]);
                        }
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot add tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function crm_meta_data()
    {
        $company_logo = get_option('company_logo' . ($this->formData['type'] == 'dark' ? '_dark' : ''));
        $company_name = get_option('companyname');
        $response = ['status' => 1, 'message' => 'Data loaded', 'companyLogo' => base_url('uploads/company/' . $company_logo), 'company_name' => $company_name];
        $this->log_api($response);
    }

    public function dashboard_data()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Data loaded'];
                    if (!$this->has_permission('customers', 'view'))
                        $this->db->where('addedfrom', $this->formData['staffid']);
                    $customerData = $this->db->select("SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as activeCustomers, COUNT(userid) as totalCustomers")->get($this->dbPrefix . 'clients')->row();

                    if (!$this->has_permission('tasks', 'view'))
                        $this->db->where('addedfrom', $this->formData['staffid']);
                    $this->db->select("SUM(CASE WHEN datefinished IS NULL THEN 1 ELSE 0 END) as tasksNotFinished, COUNT(id) as totalTasks");
                    $tasksData = $this->db->get($this->dbPrefix . 'tasks')->row();

                    $this->db->select("SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as openTickets, SUM(CASE WHEN priority = 1 THEN 1 ELSE 0 END) as lowPriorityTickets, SUM(CASE WHEN priority = 2 THEN 1 ELSE 0 END) as mediumPriorityTickets, SUM(CASE WHEN priority = 3 THEN 1 ELSE 0 END) as highPriorityTickets,SUM(CASE WHEN contactid = 0 THEN 1 ELSE 0 END) as ticketsWithoutContact, COUNT(ticketid) as totalTickets");
                    $ticketsData = $this->db->get($this->dbPrefix . 'tickets')->row();

                    if (!$this->has_permission('leads', 'view'))
                        $this->db->where('addedfrom', $this->formData['staffid'])->or_where('assigned', $this->formData['staffid']);
                    $this->db->select("SUM(CASE WHEN date_converted IS NOT NULL THEN 1 ELSE 0 END) as convertedLeads, COUNT(id) as totalLeads");
                    $leadsData = $this->db->get($this->dbPrefix . 'leads')->row();

                    $this->db->select("SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as activeContacts,SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) as inActiveContacts, COUNT(id) as totalContacts");
                    $contactsData = $this->db->get($this->dbPrefix . 'contacts')->row();

                    $this->db->like('name', 'pusher');
                    $optionsData = $this->db->get($this->dbPrefix . 'options')->result_array();

                    $response['customerData'] = $customerData;
                    $response['tasksData'] = $tasksData;
                    $response['ticketsData'] = $ticketsData;
                    $response['leadsData'] = $leadsData;
                    $response['contactsData'] = $contactsData;
                    $response['optionsData'] = $optionsData;
                    $response['currentUser'] = $this->currentUser;
                    $response['permissionsData'] = $this->get_permissions($this->currentUser->staffid);
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot load data: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function notifications()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Notifications loaded'];
                    $this->load->model('misc_model');
                    $this->db->where('touserid', $this->currentUser->staffid);
                    $this->db->order_by('date', 'desc');
                    $notifications = $this->db->get($this->dbPrefix . 'notifications')->result_array();
                    $i = 0;
                    foreach ($notifications as $notification) {
                        if (($notification['fromcompany'] == null && $notification['fromuserid'] != 0) || ($notification['fromcompany'] == null && $notification['fromclientid'] != 0)) {
                            if ($notification['fromuserid'] != 0) {
                                $notifications[$i]['profile_image'] = staff_profile_image_url($notification['fromuserid']);
                            } else {
                                $notifications[$i]['profile_image'] = contact_profile_image_url($notification['fromclientid']);
                            }
                        } else {
                            $notifications[$i]['profile_image'] = '';
                            $notifications[$i]['full_name'] = '';
                        }
                        $additional_data = '';
                        if (!empty($notification['additional_data'])) {
                            $additional_data = unserialize($notification['additional_data']);
                            $x = 0;
                            foreach ($additional_data as $data) {
                                if (strpos($data, '<lang>') !== false) {
                                    $lang = get_string_between($data, '<lang>', '</lang>');
                                    $temp = _l($lang);
                                    if (strpos($temp, 'project_status_') !== false) {
                                        $status = get_project_status_by_id(strafter($temp, 'project_status_'));
                                        $temp = $status['name'];
                                    }
                                    $additional_data[$x] = $temp;
                                }
                                $x++;
                            }
                        }
                        $notifications[$i]['description'] = _l($notification['description'], $additional_data);
                        $notifications[$i]['date'] = time_ago($notification['date']);
                        $notifications[$i]['full_date'] = $notification['date'];
                        $i++;
                        $response['notifications'] = $notifications;
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot add tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function mark_notification_as_read()
    {
        $requiredFieldsArray = ['authentication_token', 'notificationId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $this->db->where('id', $this->formData['notificationId']);
                    $this->db->where('touserid', $this->currentUser->staffid);
                    $this->db->update($this->dbPrefix . 'notifications', ['isread' => 1]);
                    $response = ['status' => 1, 'message' => 'Notifications marked as read'];
                    $this->log_api($response);
                } catch (Exception $e) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot mark as read: ' . $e->getMessage()]);
                }
            }
        }
    }

    public function get_latest_notification()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Notifications loaded'];
                    $this->load->model('misc_model');
                    $this->db->where('touserid', $this->currentUser->staffid);
                    $this->db->order_by('date', 'desc');
                    $this->db->limit(1);
                    $notifications = $this->db->get($this->dbPrefix . 'notifications')->result_array();
                    $i = 0;
                    foreach ($notifications as $notification) {
                        if (($notification['fromcompany'] == null && $notification['fromuserid'] != 0) || ($notification['fromcompany'] == null && $notification['fromclientid'] != 0)) {
                            if ($notification['fromuserid'] != 0) {
                                $notifications[$i]['profile_image'] = staff_profile_image_url($notification['fromuserid']);
                            } else {
                                $notifications[$i]['profile_image'] = contact_profile_image_url($notification['fromclientid']);
                            }
                        } else {
                            $notifications[$i]['profile_image'] = '';
                            $notifications[$i]['full_name'] = '';
                        }
                        $additional_data = '';
                        if (!empty($notification['additional_data'])) {
                            $additional_data = unserialize($notification['additional_data']);
                            $x = 0;
                            foreach ($additional_data as $data) {
                                if (strpos($data, '<lang>') !== false) {
                                    $lang = get_string_between($data, '<lang>', '</lang>');
                                    $temp = _l($lang);
                                    if (strpos($temp, 'project_status_') !== false) {
                                        $status = get_project_status_by_id(strafter($temp, 'project_status_'));
                                        $temp = $status['name'];
                                    }
                                    $additional_data[$x] = $temp;
                                }
                                $x++;
                            }
                        }
                        $notifications[$i]['description'] = _l($notification['description'], $additional_data);
                        $notifications[$i]['date'] = time_ago($notification['date']);
                        $notifications[$i]['full_date'] = $notification['date'];
                        $i++;
                        $response['notifications'] = $notifications;
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot add tickets: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function add_proposal()
    {
        $requiredFieldsArray = ['authentication_token', 'subject', 'rel_type', 'rel_id', 'date', 'currency', 'proposal_to', 'email'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('proposals', 'create')) {
                    try {
                        $response = ['status' => 0, 'message' => 'Cannot add proposal'];
                        $formData = $this->formData;
                        unset($formData['staffid']);
                        $newItems = [];
                        $formData['newitems'] = json_decode($formData['newitems'], true);
                        foreach ($formData['newitems'] as $item) {
                            $newItems[] = [
                                'order' => $item['id'],
                                'description' => $item['description'],
                                'long_description' => $item['long_description'],
                                'qty' => $item['qty'],
                                'unit' => !empty($item['unit']) ? $item['unit'] : null,
                                'rate' => $item['rate'],
                                'taxname' => $this->prepareTaxesForItem($item['taxes'])
                            ];
                        }
                        $formData['newitems'] = $newItems;
                        $this->load->model('Proposals_model');
                        $insertedId = $this->Proposals_model->add($formData);
                        if ($insertedId) {
                            $response = ['status' => 1, 'message' => 'Proposal added successfully'];
                        }
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot add Proposal: ' . $th->getMessage()]);
                    }
                } else {
                    $this->permission_not_allowed('proposals', 'create');
                }
            }
        }
    }

    public function add_invoice()
    {
        $requiredFieldsArray = ['authentication_token', 'cancel_merged_invoices', 'clientid', 'currency', 'recurring', 'show_quantity_as', 'quantity', 'newitems', 'subtotal', 'discount_percent', 'discount_total', 'adjustment', 'total', 'allowed_payment_modes'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('invoices', 'create')) {
                    try {
                        $response = ['status' => 0, 'message' => 'Cannot add proposal'];
                        $formData = $this->formData;
                        unset($formData['staffid']);
                        $newItems = [];
                        $formData['newitems'] = json_decode($formData['newitems'], true);
                        if (empty($formData['allowed_payment_modes']) || $formData['allowed_payment_modes'] == "[]") {
                            $formData['allowed_payment_modes'] = [];
                        }
                        foreach ($formData['newitems'] as $item) {
                            $newItems[] = [
                                'order' => $item['id'],
                                'description' => $item['description'],
                                'long_description' => $item['long_description'],
                                'qty' => $item['qty'],
                                'unit' => !empty($item['unit']) ? $item['unit'] : null,
                                'rate' => $item['rate'],
                                'taxname' => $this->prepareTaxesForItem($item['taxes'])
                            ];
                        }
                        $formData['newitems'] = $newItems;
                        $this->load->model('Invoices_model');
                        $insertedId = $this->Invoices_model->add($formData);
                        if ($insertedId) {
                            $response = ['status' => 1, 'message' => 'Invoice added successfully'];
                        }
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot add invoice: ' . $th->getMessage()]);
                    }
                } else {
                    $this->permission_not_allowed('invoice', 'create');
                }
            }
        }
    }

    private function prepareTaxesForItem($receivedTaxes)
    {
        $taxes = [];
        if (!empty($receivedTaxes)) {
            foreach ($receivedTaxes as $receivedTax) {
                $taxes[] = $receivedTax['name'] . '|' . $receivedTax['taxrate'];
            }
        }
        return $taxes;
    }

    public function update_proposal()
    {
        $requiredFieldsArray = ['authentication_token', 'subject', 'rel_type', 'rel_id', 'date', 'currency', 'proposal_to', 'email', 'proposalId', 'save_and_send'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('proposals', 'edit')) {
                    try {
                        $response = ['status' => 0, 'message' => 'Cannot add proposal'];
                        $proposalId = $this->formData['proposalId'];
                        unset($this->formData['staffid']);
                        unset($this->formData['proposalId']);
                        $this->load->model('Proposals_model');
                        $updated = $this->Proposals_model->update($this->formData, $proposalId);
                        if ($updated) {
                            $response = ['status' => 1, 'message' => 'Proposal updated successfully'];
                        }
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot add Proposal: ' . $th->getMessage()]);
                    }
                } else {
                    $this->permission_not_allowed('proposals', 'edit');
                }
            }
        }
    }

    public function get_proposals()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Proposals loaded'];
                    $this->db->select('*,' . $this->dbPrefix . 'currencies.id as currencyid, ' . $this->dbPrefix . 'proposals.id as id, ' . $this->dbPrefix . 'currencies.name as currency_name');
                    $this->db->from($this->dbPrefix . 'proposals');
                    $this->db->join($this->dbPrefix . 'currencies', $this->dbPrefix . 'currencies.id = ' . $this->dbPrefix . 'proposals.currency', 'left');
                    if (!empty($this->formData['rel_type'])) {
                        $this->db->where('rel_type', $this->formData['rel_type']);
                    }
                    if (!empty($this->formData['rel_id'])) {
                        $this->db->where('rel_id', $this->formData['rel_id']);
                    }
                    if (!$this->has_permission('proposals', 'view')) {
                        $this->db->where('addedfrom', $this->formData['staffid']);
                    }
                    $response['proposals'] = $this->db->get($this->dbPrefix . 'proposals')->result_array();
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot get proposals: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function get_invoices()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $invoices = $this->getMyInvoice();
                foreach ($invoices as &$invoice) {
                    $invoice['invoiceNumber'] = format_invoice_number($invoice['id']);
                    $invoice['fancyTax'] = app_format_money($invoice['total'], $invoice['currency_name']);
                    $invoice['fancyTotal'] = app_format_money($invoice['total_tax'], $invoice['currency_name']);
                    $invoice['fancyDueDate'] = _d($invoice['duedate']);
                }
                $response = ['status' => 1, 'message' => 'Invoices loaded', 'invoices' => $invoices];
                $this->log_api($response);
            }
        }
    }

    public function view_invoice()
    {
        $requiredFieldsArray = ['authentication_token', 'invoiceId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if (!$this->has_permission('invoices', 'view') && $this->has_permission('invoices', 'view_own') && get_option('allow_staff_view_invoices_assigned') == '0') {
                    $this->permission_not_allowed('proposals', 'view');
                } else {
                    $id = $this->formData['invoiceId'];
                    if (!$id) {
                        $this->log_api(['status' => 0, 'message' => 'Invoice not found']);
                    }
                    $this->load->model('invoices_model');
                    $invoice = $this->invoices_model->get($id);

                    if (!$invoice || !user_can_view_invoice($id)) {
                        $this->log_api(['status' => 0, 'message' => 'Invoice not found']);
                    }

                    $template_name = 'invoice_send_to_customer';

                    if ($invoice->sent == 1) {
                        $template_name = 'invoice_send_to_customer_already_sent';
                    }

                    $data = prepare_mail_preview_data($template_name, $invoice->clientid);
                    // Check for recorded payments
                    $this->load->model('payments_model');
                    $data['invoices_to_merge'] = $this->invoices_model->check_for_merge_invoice($invoice->clientid, $id);
                    $data['members'] = $this->staff_model->get('', ['active' => 1]);
                    $data['payments'] = $this->payments_model->get_invoice_payments($id);
                    $data['activity'] = $this->invoices_model->get_invoice_activity($id);
                    $data['totalNotes'] = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'invoice']);
                    $data['invoice_recurring_invoices'] = $this->invoices_model->get_invoice_recurring_invoices($id);

                    $data['applied_credits'] = $this->credit_notes_model->get_applied_invoice_credits($id);
                    // This data is used only when credit can be applied to invoice
                    if (credits_can_be_applied_to_invoice($invoice->status)) {
                        $data['credits_available'] = $this->credit_notes_model->total_remaining_credits_by_customer($invoice->clientid);

                        if ($data['credits_available'] > 0) {
                            $data['open_credits'] = $this->credit_notes_model->get_open_credits($invoice->clientid);
                        }

                        $customer_currency = $this->clients_model->get_customer_default_currency($invoice->clientid);
                        $this->load->model('currencies_model');

                        if ($customer_currency != 0) {
                            $data['customer_currency'] = $this->currencies_model->get($customer_currency);
                        } else {
                            $data['customer_currency'] = $this->currencies_model->get_base_currency();
                        }
                    }

                    $data['invoice'] = $invoice;

                    $data['record_payment'] = false;
                    $data['send_later'] = false;

                    if ($this->session->has_userdata('record_payment')) {
                        $data['record_payment'] = true;
                        $this->session->unset_userdata('record_payment');
                    } elseif ($this->session->has_userdata('send_later')) {
                        $data['send_later'] = true;
                        $this->session->unset_userdata('send_later');
                    }
                    $data['companyInformation'] = $this->getInvoiceCompanyInformation();
                    if (count($invoice->payments) > 0 && get_option('show_total_paid_on_invoice') == 1) {
                        $data['total_paid'] = app_format_money(sum_from_table(db_prefix() . 'invoicepaymentrecords', ['field' => 'amount', 'where' => ['invoiceid' => $invoice->id]]), $invoice->currency_name);
                    }
                    if (get_option('show_amount_due_on_invoice') == 1 && $invoice->status != Invoices_model::STATUS_CANCELLED) {
                        $data['amount_due'] = app_format_money($invoice->total_left_to_pay, $invoice->currency_name);
                    }
                    $this->load->model('payment_modes_model');
                    $paymentModes = $this->payment_modes_model->get('', [
                        'expenses_only !=' => 1,
                    ]);
                    $data['payment_modes'] = [];
                    if (!empty($paymentModes)) {
                        foreach ($paymentModes as $mode) {
                            if (is_payment_mode_allowed_for_invoice($mode['id'], $invoice->id)) {
                                $paymentMode = [
                                    "id" => $mode['id'],
                                    "name" => $mode['name'],
                                    "description" => $mode['description'],
                                ];
                                $data['payment_modes'][] = $paymentMode;
                            }
                        }
                    }
                    $this->log_api(['status' => 1, 'message' => 'Invoice loaded', 'data' => $data]);
                }
            }
        }
    }

    private function getInvoiceCompanyInformation()
    {
        return [
            'invoice_company_name' => get_option('invoice_company_name'),
            'invoice_company_address' => get_option('invoice_company_address'),
            'invoice_company_city' => get_option('invoice_company_city'),
            'invoice_company_country_code' => get_option('invoice_company_country_code'),
            'invoice_company_postal_code' => get_option('invoice_company_postal_code'),
            'invoice_company_phonenumber' => get_option('invoice_company_phonenumber'),
        ];
    }

    private function getMyInvoice($id = '', $where = [])
    {
        $hasViewGlobalPermission = $this->has_permission('invoices', 'view');
        $this->db->select('*, ' . $this->dbPrefix . 'currencies.id as currencyid, ' . $this->dbPrefix . 'invoices.id as id, ' . $this->dbPrefix . 'currencies.name as currency_name,' . $this->dbPrefix . 'clients.company as companyName');
        $this->db->from($this->dbPrefix . 'invoices');
        $this->db->join($this->dbPrefix . 'currencies', $this->dbPrefix . 'currencies.id = ' . $this->dbPrefix . 'invoices.currency', 'left');
        $this->db->join($this->dbPrefix . 'clients', $this->dbPrefix . 'clients.userid = ' . $this->dbPrefix . 'invoices.clientid', 'left');
        $this->db->where($where);
        if (!$hasViewGlobalPermission)
            $this->db->where('addedfrom', $this->currentUser->staffid);
        $this->db->order_by($this->dbPrefix . 'invoices.id', 'desc');
        return $this->db->get()->result_array();
    }

    public function get_rel_items()
    {
        $requiredFieldsArray = ['authentication_token', 'relId', 'relType'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Proposal items loaded'];
                    $response['items'] = get_items_by_type($this->formData['relType'], $this->formData['relId']);
                    $response['taxes'] = $this->db->get_where($this->dbPrefix . 'item_tax', ['rel_id' => $this->formData['relId'], 'rel_type' => $this->formData['relType']])->result_array();
                    $response['currencies'] = $this->get_currencies();
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot get proposals: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function delete_proposals()
    {
        $requiredFieldsArray = ['authentication_token', 'id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('proposals', 'delete')) {
                    try {
                        $response = ['status' => 1, 'message' => 'Proposals deleted'];
                        $this->load->model('Proposals_model');
                        $this->Proposals_model->delete($this->formData['id']);
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot get proposals: ' . $th->getMessage()]);
                    }
                }
            } else {
                $this->permission_not_allowed('proposals', 'delete');
            }
        }
    }

    public function get_proposal_comments()
    {
        $requiredFieldsArray = ['authentication_token', 'id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('proposals', 'view')) {
                    try {
                        $response = ['status' => 1, 'message' => 'Proposals Comments loaded'];
                        $this->db->select("proposal_comments.*,CONCAT(" . $this->dbPrefix . "staff.firstname, ' ', " . $this->dbPrefix . "staff.lastname) as staffName");
                        $this->db->order_by('dateadded', 'ASC');
                        $this->db->join($this->dbPrefix . 'staff', $this->dbPrefix . 'staff.staffid=' . $this->dbPrefix . 'proposal_comments.staffid', 'left');
                        $comments = $this->db->get_where($this->dbPrefix . 'proposal_comments', ['proposalid' => $this->formData['id']])->result_array();
                        if (!empty($comments)) {
                            foreach ($comments as &$comment) {
                                $comment['staffImage'] = staff_profile_image_url($comment['staffid']);
                                $comment['addedDate'] = $comment['dateadded'];
                                $comment['dateadded'] = Date::timeAgoString($comment['dateadded']);
                            }
                        }
                        $response['comments'] = $comments;
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Comments not available: ' . $th->getMessage()]);
                    }
                } else {
                    $this->permission_not_allowed('proposals', 'view');
                }
            }
        }
    }

    private function get_currencies()
    {
        return $this->db->get($this->dbPrefix . 'currencies')->result_array();
    }

    private function get_all_countries()
    {
        return $this->db->get($this->dbPrefix . 'countries')->result_array();
    }

    public function get_proposal_initial_data()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Proposals data loaded'];
                    $response['leads'] = $this->get_leads();
                    $response['customers'] = $this->get_customers();
                    $response['currencies'] = $this->get_currencies();
                    $response['staff_list'] = $this->get_all_staff();
                    $response['countries'] = $this->get_all_countries();
                    $response['itemData'] = $this->get_all_items_with_taxes();
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot get proposals: ' . $th->getMessage()]);
                }
            }
        }
    }

    private function get_all_items_with_taxes()
    {
        $response['items'] = $this->db->get($this->dbPrefix . 'items')->result();
        $response['taxes'] = $this->db->get($this->dbPrefix . 'taxes')->result();
        return $response;
    }

    public function remove_attachment()
    {
        $requiredFieldsArray = ['authentication_token', 'attachment_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('tasks', 'edit')) {
                    $this->load->model('Tasks_model');
                    $this->Tasks_model->remove_task_attachment($this->formData['attachment_id']);
                    $response = ['status' => 1, 'message' => "Attachment Deleted"];
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('tasks', 'edit');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function remove_task_comment()
    {
        $requiredFieldsArray = ['authentication_token', 'commentId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('tasks', 'edit')) {
                    $this->load->model('Tasks_model');
                    $this->Tasks_model->remove_comment($this->formData['commentId']);
                    $response = ['status' => 1, 'message' => "Comment Deleted"];
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('tasks', 'edit');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function remove_checklist_item()
    {
        $requiredFieldsArray = ['authentication_token', 'checklist_item_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('tasks', 'edit')) {
                    $this->load->model('Tasks_model');
                    $this->Tasks_model->delete_checklist_item($this->formData['checklist_item_id']);
                    $response = ['status' => 1, 'message' => "Item Deleted"];
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('tasks', 'edit');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function update_task_comment()
    {
        $requiredFieldsArray = ['authentication_token', 'comment_id', 'comment'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('tasks', 'edit')) {
                    $this->load->model('Tasks_model');
                    $data = [
                        'id' => $this->formData['comment_id'],
                        'content' => $this->formData['comment']
                    ];
                    $this->Tasks_model->edit_comment($data);
                    $response = ['status' => 1, 'message' => "Comment Updated"];
                    $this->log_api($response);
                } else {
                    $this->permission_not_allowed('tasks', 'edit');
                }
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function get_estimates()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('estimates', 'view_own')) {
                    try {
                        $response = ['status' => 1, 'message' => 'Estimates loaded'];
                        $this->db->select('*,' . $this->dbPrefix . 'currencies.id as currencyid, ' . $this->dbPrefix . 'estimates.id as id, ' . $this->dbPrefix . 'currencies.name as currency_name');
                        $this->db->from($this->dbPrefix . 'estimates');
                        $this->db->join($this->dbPrefix . 'currencies', $this->dbPrefix . 'currencies.id = ' . $this->dbPrefix . 'estimates.currency', 'left');
                        if (!empty($this->formData['rel_type'])) {
                            $this->db->where('rel_type', $this->formData['rel_type']);
                        }
                        if (!empty($this->formData['rel_id'])) {
                            $this->db->where('rel_id', $this->formData['rel_id']);
                        }
                        $response['estimates'] = $this->db->get()->result_array();
                        $this->log_api($response);
                    } catch (Exception $th) {
                        $this->log_api(['status' => 0, 'message' => 'Cannot get estimates: ' . $th->getMessage()]);
                    }
                } else {
                    $this->permission_not_allowed('estimates', 'view_own');
                }
            }
        }
    }

    public function update_task_followers()
    {
        $requiredFieldsArray = ['authentication_token', 'follower_ids', 'task_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Followers updated'];
                    $staffIds = array_unique(explode(",", $this->formData['follower_ids']));
                    $this->db->where('taskid', $this->formData['task_id'])->delete($this->dbPrefix . 'task_followers');
                    if (!empty($staffIds)) {
                        $this->load->model('Tasks_model');
                        foreach ($staffIds as $staffId) {
                            if (intval($staffId) > 0) {
                                $this->Tasks_model->add_task_followers([
                                    'follower' => $staffId,
                                    'taskid' => $this->formData['task_id']
                                ]);
                            }
                        }
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot get estimates: ' . $th->getMessage()]);
                }

            }
        }
    }

    public function update_task_assignees()
    {
        $requiredFieldsArray = ['authentication_token', 'assignee_ids', 'task_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Assignees updated'];
                    $staffIds = array_unique(explode(",", $this->formData['assignee_ids']));
                    $this->db->where('taskid', $this->formData['task_id'])->delete($this->dbPrefix . 'task_assigned');
                    if (!empty($staffIds)) {
                        $this->load->model('Tasks_model');
                        foreach ($staffIds as $staffId) {
                            if (intval($staffId) > 0) {
                                $this->Tasks_model->add_task_assignees([
                                    'assignee' => $staffId,
                                    'taskid' => $this->formData['task_id']
                                ]);
                            }
                        }
                    }
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot update assignees: ' . $th->getMessage()]);
                }
            }
        }
    }

    public function add_attachment()
    {
        $requiredFieldsArray = ['authentication_token', 'task_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $uploadedFiles = handle_task_attachments_array($this->formData['task_id']);
                if ($uploadedFiles && is_array($uploadedFiles)) {
                    foreach ($uploadedFiles as $file) {
                        $this->misc_model->add_attachment_to_database($this->formData['task_id'], 'task', [$file]);
                    }
                    $this->log_api(['status' => 1, 'message' => 'Attachment uploaded']);
                }
                $this->log_api(['status' => 0, 'message' => 'Cannot upload attachment']);
            }
        }
    }

    public function add_task_comment()
    {
        $requiredFieldsArray = ['authentication_token', 'task_id', 'comment'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if ($this->has_permission('tasks', 'create')) {
                    $data = [
                        'content' => html_purify($this->formData['comment']),
                        'taskid' => $this->formData['task_id']
                    ];
                    if (
                        $data['content'] != ''
                        || (isset($_FILES['file']['name']) && is_array($_FILES['file']['name']) && count($_FILES['file']['name']) > 0)
                    ) {
                        $comment_id = $this->tasks_model->add_task_comment($data);
                        if ($comment_id) {
                            $commentAttachments = handle_task_attachments_array($data['taskid'], 'file');
                            if ($commentAttachments && is_array($commentAttachments)) {
                                foreach ($commentAttachments as $file) {
                                    $file['task_comment_id'] = $comment_id;
                                    $this->misc_model->add_attachment_to_database($data['taskid'], 'task', [$file]);
                                }

                                if (count($commentAttachments) > 0) {
                                    $this->db->query('UPDATE ' . $this->dbPrefix . "task_comments SET content = CONCAT(content, '[task_attachment]')
                            WHERE id = " . $this->db->escape_str($comment_id));
                                }
                            }
                        }
                    }
                    $this->log_api(['status' => 1, 'message' => 'Comment added']);
                } else {
                    $this->permission_not_allowed('tasks', 'create');
                }
            }
        }
    }


    public function toggle_task_checklist_item()
    {
        $requiredFieldsArray = ['authentication_token', 'checklist_item_id', 'is_completed'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->db->where('id', $this->formData['checklist_item_id']);
                $this->db->update($this->dbPrefix . 'task_checklist_items', [
                    'finished' => $this->formData['is_completed'],
                ]);

                if ($this->db->affected_rows() > 0) {
                    if ($this->formData['is_completed'] == 1) {
                        $this->db->where('id', $this->formData['checklist_item_id']);
                        $this->db->update($this->dbPrefix . 'task_checklist_items', [
                            'finished_from' => get_staff_user_id(),
                        ]);
                        hooks()->do_action('task_checklist_item_finished', $this->formData['checklist_item_id']);
                    }
                }
                $response = ['status' => 1, 'message' => "Item Updated"];
                $this->log_api($response);

            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function save_checklist_as_template()
    {
        $requiredFieldsArray = ['authentication_token', 'description'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('Tasks_model');
                $this->Tasks_model->add_checklist_template($this->formData['description']);
                $response = ['status' => 1, 'message' => "Added as template"];
                $this->log_api($response);

            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function add_checklist_item()
    {
        $requiredFieldsArray = ['authentication_token', 'description', 'task_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $data = [
                    'taskid' => $this->formData['task_id'],
                    'description' => $this->formData['description'],
                ];
                $this->load->model('Tasks_model');
                $this->Tasks_model->add_checklist_item($data);
                $response = ['status' => 1, 'message' => "Checklist item added"];
                $this->log_api($response);

            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function update_checklist_item()
    {
        $requiredFieldsArray = ['authentication_token', 'description', 'checklist_item_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('Tasks_model');
                $this->Tasks_model->update_checklist_item($this->formData['checklist_item_id'], $this->formData['description']);
                $response = ['status' => 1, 'message' => "Checklist item updated"];
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function assign_staff_to_checklist_item()
    {
        $requiredFieldsArray = ['authentication_token', 'assignedTo', 'checklistItemId', 'task_id'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('Tasks_model');
                $item = $this->tasks_model->get_checklist_item($this->formData['checklistItemId']);
                $response = ['status' => 0, 'message' => "Not allowed"];
                if ($item->addedfrom == get_staff_user_id()
                    || is_admin() ||
                    $this->tasks_model->is_task_creator(get_staff_user_id(), $this->formData['task_id'])) {
                    $this->tasks_model->update_checklist_assigned_staff([
                        'assigned' => $this->formData['assignedTo'],
                        'checklistId' => $this->formData['checklistItemId']
                    ]);
                    $response = ['status' => 1, 'message' => "Assigned successfully!"];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function view_invoice_pdf()
    {
        $requiredFieldsArray = ['authentication_token', 'invoiceId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('invoices_model');
                $invoice = $this->invoices_model->get($this->formData['invoiceId']);
                $invoice = hooks()->apply_filters('before_admin_view_invoice_pdf', $invoice);
                $invoice_number = format_invoice_number($invoice->id);

                try {
                    $pdf = invoice_pdf($invoice);
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    echo $message;
                    if (strpos($message, 'Unable to get the size of the image') !== false) {
                        show_pdf_unable_to_get_image_size_error();
                    }
                    die;
                }

                $type = 'D';

                if ($this->input->get('output_type')) {
                    $type = $this->input->get('output_type');
                }

                if ($this->input->get('print')) {
                    $type = 'I';
                }
                $pdf->Output(mb_strtoupper(slug_it($invoice_number)) . '.pdf', $type);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function mark_invoice_as_sent()
    {
        $requiredFieldsArray = ['authentication_token', 'invoiceId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('invoices_model');
                if (!user_can_view_invoice($this->formData['invoiceId'])) {
                    $this->permission_not_allowed('invoices', 'view');
                }
                $success = $this->invoices_model->set_invoice_sent($this->formData['invoiceId'], true);
                if ($success) {
                    $response = ['status' => 1, 'message' => "Invoice marked as sent!"];
                } else {
                    $response = ['status' => 0, 'message' => "Cannot mark invoice as sent!"];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function delete_invoice()
    {
        $requiredFieldsArray = ['authentication_token', 'invoiceId'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                $this->load->model('invoices_model');
                if (!user_can_view_invoice($this->formData['invoiceId'])) {
                    $this->permission_not_allowed('invoices', 'view');
                }
                $success = $this->invoices_model->delete($this->formData['invoiceId']);
                if ($success) {
                    $response = ['status' => 1, 'message' => "Invoice marked as sent!"];
                } else {
                    $response = ['status' => 0, 'message' => "Cannot mark invoice as sent!"];
                }
                $this->log_api($response);
            } else {
                $this->authentication_error("Session expired! Please login again");
            }
        }
    }

    public function add_payment_to_invoice()
    {
        $requiredFieldsArray = ['authentication_token', 'invoiceid', 'date', 'transactionid', 'amount', 'paymentmode'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                if (!$this->has_permission('payments', 'create')) {
                    $this->permission_not_allowed('payments', 'delete');
                }
                $this->load->model('payments_model');
                $tempFormData = $this->formData;
                unset($tempFormData['staffid']);
                $id = $this->payments_model->process_payment($tempFormData, '');
                if ($id) {
                    $response = ['status' => 1, 'message' => _l('invoice_payment_recorded')];
                } else {
                    $response = ['status' => 0, 'message' => _l('invoice_payment_recorded')];
                }
                $this->log_api($response);
            }
        }
    }

    public function get_invoices_initial_data()
    {
        $requiredFieldsArray = ['authentication_token'];
        if ($this->check_form_validation($requiredFieldsArray)) {
            if ($this->currentUser) {
                try {
                    $response = ['status' => 1, 'message' => 'Invoice data loaded'];
                    $this->load->model('payment_modes_model');
                    $response['payment_modes'] = $this->payment_modes_model->get('', [], true);
                    $response['customers'] = $this->get_customers();
                    $response['currencies'] = $this->get_currencies();
                    $response['staff_list'] = $this->get_all_staff();
                    $response['item_data'] = $this->get_all_items_with_taxes();
                    $this->log_api($response);
                } catch (Exception $th) {
                    $this->log_api(['status' => 0, 'message' => 'Cannot get invoices data ' . $th->getMessage()]);
                }
            }
        }
    }
}