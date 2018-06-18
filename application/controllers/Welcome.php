<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		log_message('debug', "API PATH: ".$_SERVER["REQUEST_URI"]);
		$this->load->view('welcome_message');
	}

	public function test()
	{
		log_message('debug', "API PATH: ".$_SERVER["REQUEST_URI"]);
		log_message('debug', "test page");
		$this->load->library('googleauthenticator');
		$ga = $this->googleauthenticator;
		$secret = $ga->createSecret();
		log_message('debug',  "Secret is: ".$secret."\n\n");
		
		$qrCodeUrl = $ga->getQRCodeGoogleUrl('Blog', $secret, 'Title');
		log_message('debug',  "Google Charts URL for the QR-Code: ".$qrCodeUrl."\n\n");
		
		$oneCode = $ga->getCode($secret);
		log_message('debug',  "Checking Code '$oneCode' and Secret '$secret':\n");		

		$checkResult = $ga->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
		log_message('debug',  $checkResult);
		if ($checkResult) {
			log_message('debug',  'OK');
		} else {
			log_message('debug',  'FAILED');
		}		


		$this->mysqlPassword("1234");
		$this->load->view('test');
	}	

	private function mysqlPassword($raw) {
		log_message('debug', '*'.strtoupper(hash('sha1',pack('H*',hash('sha1', $raw)))));
	}	
}
