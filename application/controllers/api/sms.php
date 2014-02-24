<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
// Receiving messages boils down to reading values in the POST array
// This example will read in the values received and compose a response.

// 1.Import the helper Gateway class
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/AfricasTalkingGateway.php';
class Sms extends REST_Controller {
	function __construct() {
		parent::__construct ();
		$this->load->library ( 'curl' );
		$this->load->library ( 'CoreScripts' );
		$this->load->model ( 'Customer_Model', 'customers' );
		$this->load->model ( 'Users_Model', 'users' );
	}
	function custSms_post() {
		
		// 2.Read in the received values
		$phoneNumber = $this->post ( "from" ); // sender's Phone Number
		$shortCode = $this->post ( "to" ); // The short code that received the message
		$text = $this->post ( "text" ); // Message text
		$linkId = $this->post ( "linkId" ); // Used To bill the user for the response
		$date = $this->post ( "date" ); // The time we received the message
		$id = $this->post ( "id" ); // A unique id for this message
		                         
		// Add Balance from the text
		
		if ($text) {
			// 1. Use phoneNumber to get Client Code
			if ($phoneNumber) {
				$phoneNumber = "0" . substr ( $phoneNumber, 4 );
				$custData = $this->customers->getSingleCustomer ('phone', $phoneNumber );

				echo $custData['customerId'];
				
				if($custData['customerId']=="N/a"){
					$message="The phoneNumber you sent is not registered with the system. Kindly contact the Bank for more details.";
					$myresponse= $this->corescripts->_send_sms($phoneNumber,$message);
					return;
				}
				$this->login();
			}
			
			$response = $this->corescripts->getStatement ( $custData ['customerId'] );
			echo $response;
		} else {
			$message = 'Incorrect Format sent.Please add "Balance" to the Message text, then send again';
			$this->_send_sms ( $phoneNumber, $message, $shortCode );
		}
	}
	function login() {
		// Create the session
		$userName = 'daniel';
		$password = 'daniel';
		$imeiCode = '4d83b0cb12dcb79e';
		
		$login_ok = $this->users->login ( $userName, $password, $imeiCode );
		$this->users->update_session ( NULL, NULL, 17 );
		
		// echo $login_ok;
	}
	
	/* Africa Is Talking SMS-Sending */
	function _send_sms($phoneNumber, $message, $shortCode) {
		// Create an instance of the gateway class
		$username = "TomKim";
		$apiKey = "1473c117e56c4f2df393c36dda15138a57b277f5683943288c189b966aae83b4";
		$gateway = new AfricasTalkingGateway ( $username, $apiKey );
		
		try {
			// Send a response originating from the short code that received the message
			$results = $gateway->sendMessage ( $phoneNumber, $message, $shortCode );
			// Read in the gateway response and persist if necessary
			$response = $results [0];
			$status = $response->status;
			$cost = $response->cost;
		} catch ( AfricasTalkingGatewayException $e ) {
			// Log the error
			$errorMessage = $e->getMessage ();
		}
	}
}