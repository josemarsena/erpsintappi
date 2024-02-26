<?php
defined('BASEPATH') or exit('No direct script access allowed');

class BaseApiController extends App_Controller
{
    protected array $formData;
    protected array $headers = [];
    protected string $authKey = "J8sxPqLQdWijDJvSro3FKmMNJVyG0vUDSAqSwyeYaW4xx2JGGpxs44CQlXodYPsf";
    protected $currentUser = [];
    protected string $dbPrefix;
    protected int $perPageLimit = 6;
    private array $previousSession = [];

    public function __construct()
    {
        parent::__construct();
        $this->dbPrefix = db_prefix();
        // set form method and form data for get and post
        if ($this->input->method() == "get") {
            $this->formData = $this->input->get();
        } else {
            $this->formData = $this->input->post();
        }

//        $this->checkAuthentication();
        $this->fixFileTypeInGlobalFileVar();
        if (isset($this->formData['authentication_token']) && !strpos(current_url(), 'validate_session')) {
            $this->load->model('Authentication_model');
            $this->formData['staffid'] = $this->Authentication_model->decrypt($this->formData['authentication_token']);
            $this->formData['staffid'] = 1;
            $this->currentUser = $this->get_staff(['staffid' => $this->formData['staffid']]);
            if ($this->currentUser)
                $this->set_session($this->currentUser);
            unset($this->formData['authentication_token']);
        }
    }

    protected function checkAuthentication()
    {
        $this->headers = $this->input->request_headers();
        if ($this->headers) {
            if (isset($this->headers['Auth-Key'])) {
                if ($this->headers['Auth-Key'] != $this->authKey) {
                    $this->authentication_error('Invalid Auth key!');
                }
            } else {
                $this->authentication_error('Auth key not found!');
            }
        } else {
            $this->authentication_error('Auth key not found!');
        }
    }

    public function set_session($user)
    {
        $this->previousSession = $this->session->all_userdata();
        $user_data = [
            'staff_user_id' => $user->staffid,
            'staff_logged_in' => true,
        ];
        $this->session->set_userdata($user_data);
    }

    private function clearCurrentSessionAndSetPreviousSession()
    {
        $this->session->set_userdata($this->previousSession);
    }

    public function authentication_error($message)
    {
        $response = ['success' => 0, 'status' => 0, 'message' => $message];
        $this->log_api($response, false);
    }

    protected function check_form_validation($requiredFieldsArray): bool
    {
        foreach ($requiredFieldsArray as $required_field) {
            if($required_field == "authentication_token" && !strpos(current_url(), 'validate_session')){
                continue;
            }
            if (!array_key_exists($required_field, $this->formData)) {
                $this->authentication_error("Required field " . $required_field . " is missing");
                return false;
            }
        }
        return true;
    }

    protected function permission_not_allowed($feature, $capability)
    {
        $response = ['status' => 0, 'message' => "You are not allowed to $capability $feature"];
        $this->log_api($response);
    }

    protected function log_api($response, bool $saveResponse = false)
    {
        array_walk_recursive($response, function (&$value) {
            $value = $value === "" ? NULL : $value;
        });
        $response = json_encode($response, JSON_NUMERIC_CHECK);
        if ($saveResponse) {
            //save request with response 
            $data = ['url' => current_url(), 'data' => json_encode($this->formData), 'response' => $response, 'origin_from' => 'app', 'headers' => json_encode($this->headers), 'ip_address' => $_SERVER['REMOTE_ADDR']];
            $this->db->insert($this->dbPrefix . 'api_logs', $data);
        }

        $this->clearCurrentSessionAndSetPreviousSession();

        //send the response
        header('Content-Type: application/json');
        echo $response;
        die;
    }

    protected function fixFileTypeInGlobalFileVar()
    {
        if (!empty($_FILES)) {
            foreach ($_FILES as &$file) {
                if (empty($file['type'])) {
                    $file['type'] = $this->getFileTypeFromExtension($file);
                }
            }
        }
    }

    protected function getFileTypeFromExtension($file)
    {
        if (!empty($file['name'])) {
            return mime_content_type($file['tmp_name']);
        }
    }

    protected function get_staff($where)
    {
        if (!empty($where)) {
            if (isset($where['email'])) {
                $this->db->where('email', $where['email'])->or_where('phonenumber', $where['email']);
                unset($where['email']);
                if (!empty($where)) {
                    $this->db->where($where);
                }
            } else {
                $this->db->where($where);
            }
        }
        return $this->db->get($this->dbPrefix . 'staff')->row();
    }

    protected function has_permission(string $feature, string $capability, $staff = null)
    {
        if (empty($staff)) {
            if (empty($this->currentUser))
                return false;
            $staff = $this->currentUser;
        }
        return has_permission($feature, $staff->staffid, $capability);
    }
}