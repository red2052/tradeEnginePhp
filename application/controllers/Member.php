<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Member extends CI_Controller {

    protected $_request_type;
    protected $params;

    function __construct() {
        parent::__construct();
        $this->load->model('member_model'); 
        $this->_request_type = $this->_check_request_method();
        $this->_call_requested_function();
        $this->load->database();
            
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

	public function list()
	{
		echo "test";
    }
    
	public function login()
	{
        //echo "login " .$this->params->MB_ID . "\n";
        $returnCode = 1;
        $returnVal = 1;
        $returnMessage = "ok";
        $resultMap = array();
        $memberRow = $this->member_model->getMember($this->params->MB_ID, $this->params->PASSWD);
        if(!empty($memberRow)){
            if($memberRow['LOGINFLAG'] != 'Y') {
                $returnVal = 0;
                $returnMessage = "로그인이 가능하지 않습니다.";
            } else if($memberRow['ISMEMBER'] != 'Y') {
                $returnVal = 0;
                $returnMessage = "로그인이 가능하지 않습니다.";
            } else if($memberRow['ISMEMBER'] != 'Y') {
                $returnVal = 0;
                $returnMessage = "로그인이 가능하지 않습니다.";
            } else {
                $SESSION_USER_DATA = array( 
                                            "MB_ID"=>$memberRow["MB_ID"],
                                            "IDX"=>$memberRow["IDX"],
                                            "MB_NAME"=>$memberRow["MB_NAME"]
                                        );
                $this->session->set_userdata("USER_DATA", json_encode($SESSION_USER_DATA));
                
            }

        } else {
            $returnVal = 0;
            $returnMessage = "존재하지 않는 회원아이디 이거나 패스워드가 맞지 않습니다.";            
        }

        $resultMap["returnVal"] = $returnVal;
        $resultMap["member"] = $memberRow;

        $returnObj = array(
            'returnCode'=> $returnCode,
            'message'=> $returnMessage,
            'resultMap'=> $resultMap
        );
        //echo json_encode($returnObj);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($returnObj));
    }    
    
	public function checkMember()
	{
        $returnCode = 1;
        $returnVal = 1;
        $returnMessage = "ok";
        $resultMap = array();

        $resultMap["returnVal"] = $returnVal;
        $resultMap["member"] = json_decode($this->session->userdata("USER_DATA"));

        $returnObj = array(
            'returnCode'=> $returnCode,
            'message'=> $returnMessage,
            'resultMap'=> $resultMap
        );
        //echo json_encode($returnObj);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($returnObj));
    }  
    
	public function logout()
	{
        $returnCode = 1;
        $returnVal = 1;
        $returnMessage = "ok";
        $resultMap = array();

        $resultMap["returnVal"] = $returnVal;
        $this->session->unset_userdata("USER_DATA");

        $returnObj = array(
            'returnCode'=> $returnCode,
            'message'=> $returnMessage,
            'resultMap'=> $resultMap
        );
        //echo json_encode($returnObj);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($returnObj));
	}      
}
