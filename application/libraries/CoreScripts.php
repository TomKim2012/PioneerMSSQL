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
    		
    		$smsResponse= $this->_send_sms('0729472421', $message);
    		//$smsResponse=$this->_send_sms($customerData['mobileNo'], $message);
    		
    		
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
    	
    	return true;
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