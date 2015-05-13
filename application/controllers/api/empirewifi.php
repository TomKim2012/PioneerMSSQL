<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';
class EmpireWifi extends REST_Controller {
	function __construct() {
		parent::__construct ();
		date_default_timezone_set ( 'Africa/Nairobi' );
		$this->load->library ( 'curl' );
		$this->load->library ( 'CoreScripts' );
	}
	function customerSubmit_post() {
		$parameters = array (
				'cf' => 'validate',
				'phone' => $this->post ( 'phone' ),
				'email' => $this->post ( 'email' ),
				'ppid' => $this->random_string ( 4 ) 
		);
		$this->submitRequest ( $parameters );
	}
	function submitRequest($parameters) {
		$serverUrl = "http://196.201.231.226/em-api/w-api.php";
		$response = $this->curl->simple_get ( $serverUrl, $parameters );
		
		$data = json_decode ( $response, true );
		
		if ($data ['Status'] == 'Successful') {
			$smsMessage = "Thank-you for your registration. Use this credentials to Login. UserName: " . $data ['Username'] . " Password:" . $data ['Password'];
			$emailMessage = "Dear Client,<br>We have received your request for EmpireWifi Credentials." . "<br>Use this credentials below to Login. <br><br><br><strong>UserName:</strong> " . $data ['Username'] . " <br><strong>Password:</strong>" . $data ['Password'];
			$this->sendSMS ( $parameters ['phone'], $smsMessage );
			$this->sendEmail ( $parameters ['email'], $emailMessage );
			header("172.20.0.1/login");
		} else {
			return array (
					'status' => 'Failed' 
			);
		}
	}
	function random_string($length = 4) {
		$firstPart = substr ( str_shuffle ( "ABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, 2 );
		// Generate random 4 character string
		$string = md5 ( microtime () );
		$secondPart = substr ( $string, 1, 3 );
		$randomString = $firstPart . strtoupper ( $secondPart );
		
		return $randomString;
	}
	function sendSMS($phone, $message) {
		$sms_feedback = $this->corescripts->_send_sms2 ( $phone, $message );
		if ($sms_feedback) {
			echo 'SMS successfully sent';
		}
	}
	function sendEmail($email, $message) {
		$config = Array (
				'protocol' => 'smtp',
				'smtp_host' => 'ssl://smtp.googlemail.com',
				'smtp_port' => 465,
				'smtp_user' => 'tosh0948@gmail.com',
				'smtp_pass' => 'g11taru09',
				'mailtype' => 'html',
				'charset' => 'iso-8859-1' 
		);
		$this->load->library ( 'email', $config );
		
		$this->email->set_newline ( "\r\n" );
		
		$this->load->library ( 'email' );
		
		$this->email->from ( 'wifi@empire.co.ke', 'Empire Wifi' );
		$this->email->to ( $email );
		$copied = array (
				'tomkim@wira.io',
				'mworia@empire.co.ke',
				'matthew.mwangi10@gmail.com',
				'a.mellu@virtualmetrik.com' 
		);
		$this->email->cc ( $copied );
		$this->email->subject ( 'Login Credentials' );
		$this->email->message ( $message );
		
		$this->email->send ();
		echo $this->email->print_debugger ();
	}
}