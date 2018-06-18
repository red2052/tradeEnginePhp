<?php
//defined('BASEPATH') OR exit('No direct script access allowed');

class My_Controller extends CI_Controller {

    protected $_request_type;
    protected $params;

    function __construct() {
        parent::__construct();
        $this->_request_type = $this->_check_request_method();
        $this->_call_requested_function();
        $this->_loginCheck();
        $this->load->database();            
    }    

    function _loginCheck() {
        $member = json_decode($this->session->userdata("USER_DATA"));
        if(empty($member)){
            log_message('debug', "member empty...");
            $returnCode = -101; //101:로그인 세션없음 error
            $returnMessage = "로그인 하셔야 합니다.";                            
            $returnObj = array(
                'returnCode'=> $returnCode,
                'message'=> $returnMessage
            );
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode($returnObj))
                         ->_display();
                         
            log_message('debug', json_encode($returnObj));                         
            exit;
        }   
    }

    function _check_request_method() {
        $method = strtolower($this->input->server('REQUEST_METHOD'));
        if (in_array($method, array('get', 'delete', 'post', 'put'))) {
            return $method;
        }
        return 'get';
    }    

    function _call_requested_function(){
        #processing the arguments based on the type of request
        switch ($this->_request_type) {
            case 'get':
                #converting the passed arguments into an associative array
                $data = $this->uri->uri_to_assoc(4);
                break;
            case 'post':
                $data = file_get_contents("php://input");
                $data = json_decode($data);
                break;
            case 'put':
                $data = file_get_contents("php://input");
                $data = json_decode($data);
                break;
            case 'delete':
                $data = file_get_contents("php://input");
                $data = json_decode($data);
            default:
                break;
        }
        
        #inserting the data entered by the user in the POST array
        if (!empty($data)) {
            // foreach ($data as $key => $value) {
            //     $params[$key] = $value;
            // }
            $this->params = $data;
        }

    }   
}
