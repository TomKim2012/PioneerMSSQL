<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );

require APPPATH . '/libraries/AfricasTalkingGateway.php';
class CoreScripts {
	public function __construct() {
		$this->CI ()->load->library ( 'curl' );
		$this->CI ()->load->model ( 'Transactions_Model', 'transactions' );
		$this->CI ()->load->model ( 'Customer_Model', 'customers' );
	}
	
	public function CI() {
		$CI = & get_instance ();
		return $CI;
	}
	
	function updateCustomer($newMobile) {
		// updating customer Record
		$cust = array (
				'newMobile' => $newMobile 
		);
		// updating customer
		if (strlen ( $cust ['newMobile'] ) == 10) { // 0729472421
			$newInput = array (
					'phone' => $cust ['newMobile'] 
			);
			$this->CI ()->customers->UpdateCustomer ( $inp ['clCode'], $newInput );
		}
	}
	
	function getStatement($clCode) {
		if ($clCode == "") {
			return;
		}
		
		$sharesBal = $this->CI ()->transactions->getCustTransaction ( $clCode, 1 );
		$savingsBal = $this->CI()->transactions->getCustTransaction ( $clCode, 2 );
		$loanBal = $this->CI ()->transactions->getCustTransaction ( $clCode, 3 );
		
		$customerData = $this->CI ()->customers->getSingleCustomer ( "clCode", $clCode );
		
		/* No mobile Number */
		if (! $customerData ['mobileNo']) {
			return $this->response ( array (
					'success' => false,
					'error' => 'Transaction not posted. Customer does not have mobile Number saved. Please update and Try Again.' 
			), 200 );
		}
		
		if ($sharesBal) {
			$message = "Dear " . $customerData ['firstName'] . ", Your shares Balance is Ksh " .
						number_format ( $sharesBal ) . ", Savings Balance is Ksh " . number_format ( $savingsBal ) .
						".and Loan Balance is Ksh " . number_format ( $loanBal );
		}
		
		$tType = "Mini-Statement";
		$response = $this->saveMiniStatement($clCode, $tType, 10 );
		
		if ($response ['success']) {
			
			//$smsResponse= $this->_send_sms('0729472421', $message);
			$smsResponse = $this->_send_sms ( $customerData ['mobileNo'], $message );
			
			if ($smsResponse) {
				$clientResponse ['sms'] = true;
				$clientResponse ['success'] = true;
				$clientResponse ['sms'] = true;
				$clientResponse ['transactionCode'] = $response ['transaction_code'];
				$clientResponse ['transactionType'] = $tType;
				$clientResponse ['transactionTime'] = $response ['transaction_time'];
				$clientResponse ['transactionDate'] = $response ['transaction_date'];
				$clientResponse ['transactionAmount'] = "10";
				$clientResponse ['custNames'] = $customerData ['firstName'] . " " . $customerData ['lastName'];
				$this->CI ()->response ( $clientResponse, 200 ); // 200 being the HTTP response code
			}
			
			// $smsResponse2= $this->_send_sms2('0729472421', $message,'SMSLEOPARD');
		}
	}
	
	function _send_sms($recipient, $message) {
		// Set the prefered message provider from here
		return $this->_send_sms2 ( $recipient, $message );
	}
	
	// ----------Function to send sms-------------------
	function _send_sms1($recipient, $message) {
		$serverUrl = "http://api.smartsms.co.ke/api/sendsms/plain";
		
		if ($recipient == "") {
			return array (
					'error' => "Message not sent, No phoneNumber passed" 
			);
		}
		
		$recipient = "+254" . substr ( $recipient, 1 );
		
		$parameters = array (
				'user' => 'megarider',
				'password' => 'ZpmXSCdd',
				'sender' => 'PioneerFSA',
				'GSM' => $recipient,
				'SMSText' => $message 
		);
		
		$response = $this->CI ()->curl->simple_get ( $serverUrl, $parameters );
		
		// Validate Response
		// Ascertain -- the necessary return response is sent
		
		return true;
	}
	
	/* Africa Is Talking SMS-Sending */
	function _send_sms2($phoneNumber, $message) {
		if ($phoneNumber == "") {
			return array (
					'error' => "Message not sent, No phoneNumber passed" 
			);
		}
		
		$recipient = "+254" . substr ( $phoneNumber, 1 );
		
		// Create an instance of the gateway class
		$username = "TomKim";
		$shortCode = "PioneerFSA";
		$apiKey = "1473c117e56c4f2df393c36dda15138a57b277f5683943288c189b966aae83b4";
		$gateway = new AfricasTalkingGateway ( $username, $apiKey );
		
		try {
			// Send a response originating from the short code that received the message
			/*
			 * Bug:: If you put shortcode - It fails completely.
			 */
			
			$results = $gateway->sendMessage ( $recipient, $message, $shortCode );
			
			// Read in the gateway response and persist if necessary
			$response = $results [0];
			$status = $response->status;
			$cost = $response->cost;
			
			// echo $status . " " . $cost;
			
			if ($status = "Success") {
				return true;
			} else {
				return false;
			}
		} catch ( AfricasTalkingGatewayException $e ) {
			// Log the error
			$errorMessage = $e->getMessage ();
			return false;
		}
	}
	
	function saveMiniStatement($clCode, $transactionType, $transactionAmount) {
		$inp = array (
				'clCode' => $clCode,
				'transaction_amount' => $transactionAmount,
				'transaction_type' => $transactionType 
		);
		
		$response = $this->CI ()->transactions->createTransaction ( $inp );
		
		return $response;
	}
	
}

/* End of file CoreScripts.php */