<?php

defined('BASEPATH') || exit('No direct script access allowed');
define("LB_API_DEBUG", false);
define("LB_TEXT_CONNECTION_FAILED", 'Server is unavailable at the moment, please try again.');
define("LB_TEXT_INVALID_RESPONSE", 'Server returned an invalid response, please contact support.');
define("LB_TEXT_VERIFIED_RESPONSE", 'Verified! Thanks for purchasing.');

class VerifyPurchase extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function activate()
    {
        $response = is_file(realpath(__DIR__).'/.lic') ? $this->verify_license() : $this->verify_purchase($this->input->post('module_name'),$this->input->post('purchase_code'), $this->input->post('username'));
        if ($response['status']) {
            $response['return_url'] = $this->input->post('return_url');
        }
        echo json_encode($response);
    }
    
    private function call_api($method, $url, $data = null)
    {
		$curl = curl_init();
		switch ($method){
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);
				if($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
			case "PUT":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
				if($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);                         
				break;
		  	default:
		  		if($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}
		$this_server_name = getenv('SERVER_NAME')?:
			$_SERVER['SERVER_NAME']?:
			getenv('HTTP_HOST')?:
			$_SERVER['HTTP_HOST'];
		$this_http_or_https = ((
			(isset($_SERVER['HTTPS'])&&($_SERVER['HTTPS']=="on"))or
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])and
				$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
		)?'https://':'http://');
		$this_url = $this_http_or_https.$this_server_name;
		$this_ip = getenv('SERVER_ADDR')?:
			$_SERVER['SERVER_ADDR']?:
			$this->get_ip_from_third_party()?:
			gethostbyname(gethostname());
		curl_setopt($curl, CURLOPT_HTTPHEADER, 
			array('Content-Type: application/json', 
				'LB-API-KEY: DCEABC4EBE20D2E0E13C', 
				'LB-URL: '.$this_url, 
				'LB-IP: '.$this_ip, 
				'LB-LANG: english')
		);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		$result = curl_exec($curl);
		if(!$result&&!LB_API_DEBUG){
			$rs = array(
				'status' => FALSE, 
				'message' => LB_TEXT_CONNECTION_FAILED
			);
			return json_encode($rs);
		}
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if($http_status != 200){
			if(LB_API_DEBUG){
				$temp_decode = json_decode($result, true);
				$rs = array(
					'status' => FALSE, 
					'message' => ((!empty($temp_decode['error']))?
						$temp_decode['error']:
						$temp_decode['message'])
				);
				return json_encode($rs);
			}else{
				$rs = array(
					'status' => FALSE, 
					'message' => LB_TEXT_INVALID_RESPONSE
				);
				return json_encode($rs);
			}
		}
		curl_close($curl);
		return $result;
	}
	
	private function get_ip_from_third_party()
	{
		$curl = curl_init ();
		curl_setopt($curl, CURLOPT_URL, "http://ipecho.net/plain");
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

    public function verify_purchase($module_name,$purchase_code='',$username='')
    {
        $data_array =  array(
			"product_id"  => 'FD6C9535',
			"license_code" => $purchase_code,
			"client_name" => $username,
			"verify_type" => 'envato'
		);
		
		$get_data = $this->call_api(
			'POST',
			'https://license.branditta.net/api/activate_license', 
			json_encode($data_array)
		);
		
		$response = json_decode($get_data, true);
		
		if($response['status']){
			$licfile = trim($response['lic_response']);
			file_put_contents(realpath(__DIR__).'/.lic', $licfile, LOCK_EX);
			update_option('flutex_admin_api_enabled', 1);
		}else{
			@chmod(realpath(__DIR__).'/.lic', 0777);
			if(is_writeable(realpath(__DIR__).'/.lic')){
				unlink(realpath(__DIR__).'/.lic');
			}
		}
		
		return $response;
    }
    
    public function verify_license($license = false, $client = false)
    {
		if(!empty($license)&&!empty($client)){
			$data_array =  array(
				"product_id"  => 'FD6C9535',
				"license_file" => null,
				"license_code" => $license,
				"client_name" => $client
			);
		}else{
			if(is_file(realpath(__DIR__).'/.lic')){
				$data_array =  array(
					"product_id"  => 'FD6C9535',
					"license_file" => file_get_contents(realpath(__DIR__).'/.lic'),
					"license_code" => null,
					"client_name" => null
				);
			}else{
				$data_array =  array();
			}
		}
		$res = array('status' => TRUE, 'message' => LB_TEXT_VERIFIED_RESPONSE);
		
		$get_data = $this->call_api(
			'POST',
			'https://license.branditta.net/api/verify_license', 
			json_encode($data_array)
		);
		$res = json_decode($get_data, true);
		
		if($res['status']){
			update_option('flutex_admin_api_enabled', 1);
		}else{
		    update_option('flutex_admin_api_enabled', 0);
			@chmod(realpath(__DIR__).'/.lic', 0777);
			if(is_writeable(realpath(__DIR__).'/.lic')){
				unlink(realpath(__DIR__).'/.lic');
			}
		}
		return $res;
	}
}
