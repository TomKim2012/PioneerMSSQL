<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class CoreScripts{
	
	public function __construct()
	{
		$this->CI()->load->library('curl');
		$this->CI()-> load->model('Transactions_Model', 'transactions');
		$this->CI()->load->model('Customer_Model', 'customers');
	}
	
	public function CI()
	{
		$CI =& get_instance();
		return $CI;
	}
	
	function updateCustomer($newMobile){
		//updating customer Record
    	$cust = array(
    			'newMobile'=> $newMobile
    	);
    	//updating customer
    	if(strlen($cust['newMobile'])==10){ //0729472421
    		$newInput =array('phone'=>$cust['newMobile']);
    		$this->CI()->customers->UpdateCustomer($inp['clCode'],$newInput);
    	}
	}
	
    
    function getStatement($clCode){
    	if($clCode==""){
    		return;
    	}
    	
    	$sharesBal = $this->CI()->transactions->getCustTransaction($clCode, 1);
    	$savingsBal = $this->CI()->transactions->getCustTransaction($clCode, 2);
    	$loanBal = $this->CI()->transactions->getCustTransaction($clCode, 3);
    	
    	$customerData=$this->CI() ->customers->getSingleCustomer("clCode", $clCode);
    	 
    	if($sharesBal)
    	{
    		$message ="Dear ".$customerData['firstName'].", Your shares Balance is Ksh".number_format($sharesBal).", Savings Balance is Ksh ".
    				number_format($savingsBal).".and Loan Balance is Ksh ".number_format($loanBal);
    	}
    	 
    	$tType = "Mini-Statement";
    	$response = $this->saveMiniStatement($clCode,$tType, 10);
    	
    	if($response['success']){
    		
    		//$smsResponse= $this->_send_sms('0729472421', $message);
    		$smsResponse=$this->_send_sms($customerData['mobileNo'], $message);
    		
    		
    		if($smsResponse){
    			$clientResponse['sms']=true;
    			$clientResponse['success']=true;
    			$clientResponse['sms']=true;
    			$clientResponse['transactionCode']= $response['transaction_code'] ;
    			$clientResponse['transactionType']= $tType;
    			$clientResponse['transactionTime'] = $response['transaction_time'];
    			$clientResponse['transactionDate'] = $response['transaction_date'];
    			$clientResponse['transactionAmount'] = "10";
    			$clientResponse['custNames'] = $customerData['firstName']." ".$customerData['lastName'];
    			$this->CI()->response($clientResponse, 200); // 200 being the HTTP response code
    		}
    		
    		$smsResponse2= $this->_send_sms2('0729472421', $message,'SMSLEOPARD');
    	}
    	
    }
    
    //----------Function to send sms-------------------
    function _send_sms($recipient,$message){
    	$serverUrl= "http://api.smartsms.co.ke/api/sendsms/plain";
    	 
    	if($recipient==""){
    		echo "Message not sent, No phoneNumber passed";
    		return;
    	}
    	 
    	$recipient = "+254".substr($recipient, 1);
    	 
    	$parameters= array( 'user'=>'megarider',
    			'password'=>'ZpmXSCdd',
    			'sender'=>'pioneerFSA',
    			'GSM'=>$recipient,
    			'SMSText'=>$message
    	);
    	 
    	$response = $this->CI()->curl->simple_get($serverUrl,$parameters);
    	
    	//Validate Response
    	//Ascertain -- the necessary return response is sent
    	
    	return true;
    }
    
    /* Africa Is Talking SMS-Sending */
    function _send_sms2($phoneNumber, $message, $shortCode) {
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
    		
    		echo $status." ".$cost;
    	} catch ( AfricasTalkingGatewayException $e ) {
    		// Log the error
    		$errorMessage = $e->getMessage ();
    	}
    }
    
    function saveMiniStatement($clCode, $transactionType, $transactionAmount){
    		$inp= array(
    				'clCode' => $clCode,
    				'transaction_amount' => $transactionAmount,
    				'transaction_type' => $transactionType
    		);
    
    		$response = $this->CI()-> transactions->createTransaction($inp);
    		 
    		return $response;
    }
}

/* End of file CoreScripts.php */