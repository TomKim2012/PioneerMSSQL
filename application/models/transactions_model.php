<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions_Model extends CI_Model {
	var $userData;
	var $terminal;
	
	function __construct(){
		parent::__construct();
		date_default_timezone_set('Africa/Nairobi');
		if ($this->session->userdata('user')) {
		$this->userData= $this->session->userdata('user');
		$this->terminal=$this->session->userdata('terminal');
		}
	}
	
	function getTransactions(){
		//Users transaction for Today
		$this->db->where(array('userId'=>$this->userData->userId
						      ));
		$query=$this->db->get('transactions');
		$transactions= $query->result_array();
		
		$response=array();
		foreach ($transactions as $row) {
			$data=array('transaction_date' => $row['transaction_date'],
					    'transaction_code' => $row['transaction_code'],
						'transaction_amount'=> (String)$row['transaction_amount'],
					    'transaction_time'=> $row['transaction_time'],
						'transaction_id'=>(String)$row['transaction_id'],
					    'transaction_type'=>$row['transaction_type'],
					    'clCode'=>$row['clCode'],
			);
			array_push($response, $data);
		}
		
		return $response;
	}
	
	function createTransaction($inp){
			$inp['transaction_code']= $this->random_string(7);
			$inp['transaction_date']=date("Y-m-d H:i:s");
			$inp['transaction_time']=date("G:i:s");
			$inp['userId']=$this->userData->userId;
			$inp['terminalId']=$this->terminal;

		 	if($this->db->insert('transactions', $inp)){
		 	return array('success'=>true,
		 					 'transaction_code'=>$inp['transaction_code'],
		 					 'transaction_date'=>$inp['transaction_date'],
		 					 'transaction_time'=>$inp['transaction_time'],
		 					 'officer_names' =>$this->userData->firstName." ".$this->userData->lastName
		 					);
		 	}
		 	else{
		 		return  array('success'=>false);
		 	}
	}
	
	function getCustTransaction($customerId, $transactionId){
		$this->db->query('Use MergeFinal');
        $rs = $this->db->query('SELECT Dbo.SP_GetBalances(\''.$customerId.'\','.$transactionId.') AS balance');
		//echo $this->db->last_query();

        $balance = $rs->row()->balance;
        $this->db->query('Use mobileBanking');
		return $balance;

		/*//Users transaction for Today
		$this->db->select('transaction_amount,transaction_date,transaction_type');
		$this->db->where(
						array('customerId'=>$customerId,
						));
		$query=$this->db->get('transactions',5);
		$transactions= $query->result_array();
		return $transactions;
		*/	
	}
	
	function random_string($length = 4) {
		$firstPart=substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,2);
		// Generate random 4 character string
		$string = md5(microtime());
		$secondPart = substr($string,1,$length);
		$randomString = $firstPart.strtoupper($secondPart);
		
		//Confirm its not a duplicate
		$this->db->where('transaction_code',$randomString);
		$query=$this->db->get('transactions');
		if($query->num_rows()>0){
			random_string($length);
		}else{
		return $randomString;
		}
	}
	
}