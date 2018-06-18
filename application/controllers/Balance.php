<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Balance extends BaseLoginController {

    function __construct() {
        parent::__construct();
        $this->load->model('balance_model');    
    }

	public function getBalance()
	{
        $returnCode = 1;
        $returnVal = 1;
        $returnMessage = "ok";
        $resultMap = array();
        
        $member = json_decode($this->session->userdata("USER_DATA"));
        if(empty($this->params->coin)) {
            $returnVal = -1;
            $returnMessage = "인자값이 없습니다.";
        } else {
            $balance = $this->balance_model->getBalance($member["IDX"], $this->params->coin);
            $resultMap = array(
                'result'=> $returnVal,
                'data'=>$balance
            );
        }

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
