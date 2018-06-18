<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class OrderRegister extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->model('orderRegister_model');
	}	

	public function process()
	{
		print("process\n");
		$this->orderRegister_model->process();
	}
}