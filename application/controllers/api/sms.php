<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
// Receiving messages boils down to reading values in the POST array
// This example will read in the values received and compose a response.

// 1.Import the helper Gateway class
require APPPATH . '/libraries/REST_Controller.php';
// require APPPATH . '/libraries/AfricasTalkingGateway.php';
class Sms extends REST_Controller {
	function __construct() {
		parent::__construct ();
		$this->load->library ( 'curl' );
		$this->load->library ( 'CoreScripts' );
		$this->load->model ( 'Customer_Model', 'customers' );
		$this->load->model ( 'Users_Model', 'users' );
	}
	function custSms_post() {
		// echo "Heere";
		// 2.Read in the received values
		$phoneNumber = $this->post ( "from" ); // sender's Phone Number
		$shortCode = $this->post ( "to" ); // The short code that received the message
		$text = $this->post ( "text" ); // Message text
		$linkId = $this->post ( "linkId" ); // Used To bill the user for the response
		$date = $this->post ( "date" ); // The time we received the message
		$id = $this->post ( "id" ); // A unique id for this message
		
		if (strpos ( $text, "data" ) !== false) {
			$this->send_email ( $phoneNumber, $text );
			return;
		}
		                            
		// Add Balance from the text
		if ($text) {
			// 1. Use phoneNumber to get Client Code
			if ($phoneNumber) {
				$phoneNumber = "0" . substr ( $phoneNumber, 4 );
				$custData = $this->customers->getSingleCustomer ( 'phone', $phoneNumber );
				
				if ($custData ['customerId'] == "N/a") {
					$message = "The phoneNumber you sent is not registered with the system." . "Kindly contact nearest branch for more details.";
					$myresponse = $this->corescripts->_send_sms ( $phoneNumber, $message );
					return;
				}
			} else {
				$message = 'Dear customer, the phoneNumber you used is not in our records';
				$this->corescripts->_send_sms2 ( $phoneNumber, $message, $shortCode );
			}
			
			// Lipa Na Mpesa Request
			if (strpos ( $text, "lipa" ) !== false) {
				$this->transferRequest ( $phoneNumber );
			} else {
				$this->login ();
				$response = $this->corescripts->getStatement ( $custData ['customerId'] );
				echo $response;
			}
		} else {
			$message = 'Incorrect Format sent.Please try again by sending "pioneer balance" to 20414"';
			$this->corescripts->_send_sms2 ( $phoneNumber, $message, $shortCode );
		}
	}
	function login() {
		// Create the session
		$userName = 'daniel';
		$password = 'daniel';
		$imeiCode = '4d83b0cb12dcb79e';
		
		$login_ok = $this->users->login ( $userName, $password, $imeiCode );
		
		// Updating Terminal
		$this->users->update_session ( NULL, NULL, 17 );
	}
	function send_email($phone, $text) {
		$config = Array (
				'protocol' => 'smtp',
				'smtp_host' => 'ssl://smtp.googlemail.com',
				'smtp_port' => 465,
				'smtp_user' => 'tosh0948@gmail.com',
				'smtp_pass' => 'g11taru09',
				'charset' => 'iso-8859-1' 
		);
		$this->load->library ( 'email', $config );
		
		$this->email->set_newline ( "\r\n" );
		
		$this->load->library ( 'email' );
		
		$this->email->from ( 'tosh0948@gmail.com', 'EaDataHandlers' );
		$this->email->to ( 'sms@eadatahandlers.co.ke' );
		$this->email->cc ( 'tomkim@wira.io' );
		$this->email->subject ( 'From:'.$phone );
		$this->email->message ( 'Content:'.$text);
		
		$this->email->send ();
		
		echo $this->email->print_debugger ();
	}
	function transferRequest($phone) {
		$serverUrl = "http://localhost:8030/mTransport/index.php/Lipasms/custSms";
		
		$parameters = array (
				'phoneNumber' => $phone 
		);
		
		$response = $this->curl->simple_get ( $serverUrl, $parameters );
		
		// echo "Transfered>>".$phone;
		echo $response;
	}
}