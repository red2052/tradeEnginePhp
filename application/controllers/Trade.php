<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Trade extends My_Controller {

    function __construct() {
        parent::__construct();
        //$this->load->database();
        $this->load->model('orderRegister_model');        
    }

	public function register()
	{
        //print( "Trade : register()" );
        //var_dump($this->params);
        $member = json_decode($this->session->userdata("USER_DATA"));
        $paramMap = array(
            'mb_idx'=>$member->IDX,
            'type'=>$this->params->type,
            'coin'=>$this->params->coin,
            'market'=>$this->params->market,
            'price'=>$this->params->price,
            'amount'=>$this->params->amount,
            'unit_price'=>$this->params->unit_price,
            'feerate'=>$this->params->feerate,
        );
        $returnVal = $this->orderRegister_model->register($paramMap);
        $returnObj = array(
            'result'=> $returnVal
        );
        //echo json_encode($returnObj);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($returnObj));
	}
}
