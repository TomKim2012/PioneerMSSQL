<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );

/**
 * Example
 *
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array.
 *
 * @package CodeIgniter
 * @subpackage Rest Server
 * @category Controller
 * @author Tom Kimani
 *        
 */

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';
class Flexipay_server extends REST_Controller {
	private $isLoggedIn = false;
	function __construct() {
		// header("Access-Control-Allow-Origin: http://192.168.0.106");
		// header("Access-Control-Allow-Origin:".$_SERVER['REMOTE_ADDR'].":".$_SERVER['SERVER_PORT']);
		// header("Access-Control-Allow-Origin:".$_SERVER['REMOTE_ADDR']);
		header ( "Access-Control-Allow-Origin: http://127.0.0.1:8888" );
		header ( "Access-Control-Allow-Credentials:true" );
		header ( "Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method" );
		header ( "Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE" );
		$method = $_SERVER ['REQUEST_METHOD'];
		if ($method == "OPTIONS") {
			die ();
		}
		parent::__construct ();
		date_default_timezone_set ( 'Africa/Nairobi' );
		
		$this->load->library ( 'curl' );
		$this->load->library ( 'coreScripts' );
		$this->load->model ( 'Users_Model', 'users' );
		$this->load->model ( 'Transactions_Model', 'transactions' );
		$this->load->model ( 'Customer_Model', 'customers' );
		$this->load->model ( 'Terminal_Model', 'terminals' );
	}
	function authorize() {
		if ($this->users->user) {
			return true;
		} else {
			$this->response ( array (
					'message' => 'Not Logged In',
					'error' => false,
					'isLogged' => false 
			), 200 );
			return false;
		}
		// return true;
	}
	
	/*
	 * Get customer Transactions
	 */
	function custTransactions_post() {
		if ($this->post ( 'clCode' )) {
			// Check if user is Logged In
			if ($this->authorize ()) {
				$this->corescripts->getStatement ( $this->post ( 'clCode' ) );
			} else {
				$this->response ( array (
						'message' => 'User Not Logged In',
						'error' => true 
				), 200 );
			}
		} else {
			$this->response ( array (
					'message' => 'clCode not sent in the request',
					'error' => true 
			), 200 );
		}
	}
	function transactions_get() {
		// Check if user is Logged In
		if ($this->authorize ()) {
			$transactions = $this->transactions->getTransactions ();
			
			if ($transactions) {
				// Get the Customer Details
				$counter = 0;
				foreach ( $transactions as $row ) {
					// /Get Customer Data
					$customerData = $this->customers->getSingleCustomer ( "clcode", $row ['clCode'] );
					$customer ['custNames'] = $customerData ['firstName'] . " " . $customerData ['lastName'];
					$mergedTransaction [] = array_merge ( $transactions [$counter], $customer );
					$counter += 1;
				}
				// $this->response($mergedTransaction, 200); // 200 being the HTTP response code
				
				header ( 'content-type: application/json; charset=utf-8' );
				$json = json_encode ( $mergedTransaction );
				
				echo isset ( $_GET ['callback'] ) ? "{$_GET['callback']}($json)" : $json;
			} else {
				$this->response ( array (
						'message' => 'No transactions set returned',
						'error' => true 
				), 200 );
			}
		}
	}
	function transactions_post() {
		if ($this->authorize ()) {
			$inp = array (
					'clCode' => $this->post ( 'customerId' ),
					'transaction_amount' => $this->post ( 'transaction_amount' ),
					'transaction_type' => $this->post ( 'transaction_type' ) 
			);
			$cust = array (
					'newMobile' => $this->post ( 'newMobile' ) 
			);
			
			// updating customer
			if (strlen ( $cust ['newMobile'] ) == 10) { // 0729472421
				$newInput = array (
						'phone' => $cust ['newMobile'] 
				);
				$this->customers->UpdateCustomer ( $inp ['clCode'], $newInput );
			}
			
			$customer = $this->customers->getSingleCustomer ( 'clCode', $inp ['clCode'] );
			
			$savingsBal = $this->transactions->getCustTransaction ( $inp ['clCode'], 2 );
			$prevDeposits = $this->transactions->getPrevDeposits ( $inp ['clCode'] );
			$balance = $savingsBal + $prevDeposits + $inp ['transaction_amount'];
			
			if ($customer ['mobileNo']) {
				$response = $this->transactions->createTransaction ( $inp );
				if ($response ['success']) {
					$tDate = date ( "d/m/Y", strtotime ( $response ['transaction_date'] ) );
					$tTime = date ( "h:i A", strtotime ( $response ['transaction_time'] ) );
					$tCode = $response ['transaction_code'];
					$message = "Transaction " . $response ['transaction_code'] . " confirmed on " . $tDate . " at " . $tTime . ". Ksh " . number_format ( $inp ['transaction_amount'] ) . " deposited to A/C " . $customer ['refNo'] . "- " . $customer ['firstName'] . " " . $customer ['lastName'] . ".New balance is Ksh " . number_format ( $balance );
					
					$response = $this->corescripts->_send_sms ( '0729472421', $message );
					// $response = $this->corescripts->_send_sms ( $customer ['mobileNo'], $message );
					
					if ($response) {
						$clientResponse ['sms'] = true;
						$clientResponse ['success'] = true;
						$clientResponse ['transactionCode'] = $tCode;
						$clientResponse ['transactionType'] = "Deposit";
						$clientResponse ['transactionTime'] = $tTime;
						$clientResponse ['transactionDate'] = $tDate;
						$clientResponse ['transactionAmount'] = $inp ['transaction_amount'];
						$clientResponse ['custNames'] = $customer ['firstName'] . " " . $customer ['lastName'];
						$this->response ( $clientResponse, 200 ); // 200 being the HTTP response code
					}
				} else {
					$this->response ( $response, 200 ); // Reject code
				}
			} else {
				return $this->response ( array (
						'success' => false,
						'error' => 'Transaction not posted. Customer does not have mobile Number saved. Please update and Try Again.' 
				), 200 );
			}
		}
	}
	
	/*
	 * Get customer details from parameter value
	 */
	function custDetails_get() {
		$parameter = $this->get ( 'parameter' );
		$value = $this->get ( 'value' );
		
		if ($this->authorize ()) {
			if (! empty ( $parameter ) && ! empty ( $value )) {
				$customers = $this->customers->getCustomer ( $parameter, $value );
				
				if ($customers) {
					$this->response ( $customers, 200 ); // 200 being the HTTP response code
				} else {
					$this->response ( null, 200 );
				}
			} else {
				/* echo 'NotLogged'; */
	    		/* $this->response(array('isLogged'=>false), 200); */
	    	}
		}
	}
	
	// -------Function to login user ------------------
	function login_post() {
		if ($this->post ( 'userName' )) {
			$UserName = $this->post ( 'userName' );
			$password = $this->post ( 'password' );
			$imeiCode = $this->post ( 'imeiCode' );
		}
		
		if ((! empty ( $UserName )) && ! (empty ( $imeiCode ))) {
			// $login_ok is true or false depending on user login information
			$login_ok = $this->users->login ( $UserName, $password, $imeiCode );
			if ($login_ok ['authorize'] == true) {
				/* UserName For testing */
				if (! (isset ( $username ))) {
					$this->response ( $login_ok, 200 );
				} else {
					return true;
				}
			} else {
				$response = $login_ok;
				$this->response ( $response, 200 );
			}
		} else {
			$response = array (
					'message' => 'UserName OR ImeiCode Missing',
					'success' => false 
			);
			$this->response ( $response, 200 );
		}
	}
	
	// --------------Function to logout user----------------------------
	function logout_get() {
		$response = $this->users->logout ();
		if ($response ['success']) {
			$this->response ( $response, 200 );
		} else {
			$this->response ( $response, 404 );
		}
	}
	
	// --------Function to get All Users -------
	function users_get() {
		$response = $this->users->getUsers ();
		
		$this->response ( $response, 200 );
	}
	function terminal_post() {
		if ($this->post ( 'terminalName' )) {
			$terminalName = $this->post ( 'terminalName' );
			$imeiCode = $this->post ( 'imeiCode' );
		}
		
		if ((! empty ( $terminalName )) && ! (empty ( $imeiCode ))) {
			$terminalDetails = array (
					'imeiCode' => $imeiCode,
					'terminalName' => $terminalName 
			);
			$response = $this->terminals->createTerminal ( $terminalDetails );
			if ($response ['success']) {
				echo $response ['terminalId'];
			} else {
				// echo "";
				echo $response ['terminalId'];
			}
		} else {
			$response = array (
					'message' => 'Missing parameters',
					'success' => false 
			);
			$this->response ( $response, 200 );
		}
	}
	
	// ----Check Allocation
	function allocation_get() {
		if ($this->get ( 'imeiCode' )) {
			// Check if user is Logged In
			if ($this->authorize ()) {
				$allocation = $this->users->check_allocation ( NULL, $this->get ( 'imeiCode' ) );
				if ($allocation) {
					$userDetails = $this->users->getUserById ( $allocation ['allocatedTo'] );
					$allocateeDetails = $this->users->getUserById ( $allocation ['allocatedBy'] );
					
					$allocation ['allocationId'] = ( string ) $allocation ['allocationId'];
					$allocation ['allocatedName'] = $userDetails ['firstName'] . " " . $userDetails ['lastName'];
					$allocation ['allocateeName'] = $allocateeDetails ['firstName'] . " " . $allocateeDetails ['lastName'];
					$allocation ['isAllocated'] = true;
					$this->response ( $allocation, 200 );
				} else {
					$this->response ( array (
							'isAllocated' => false 
					), 200 );
				}
			}
		} else {
			$this->response ( array (
					'message' => 'ImeiCode not sent in the request',
					'error' => true 
			), 200 );
		}
	}
	function allocation_post() {
		if ($this->post ( 'allocatedTo' )) {
			$input = array (
					'allocatedTo' => $this->post ( 'allocatedTo' ),
					'allocatedBy' => $this->post ( 'allocatedBy' ),
					'allocationDate' => date ( "Y-m-d H:i:s" ),
					'deallocationDate' => NULL,
					'deallocatedBy' => NULL,
					'terminalId' => $this->post ( 'terminalId' ) 
			);
		}
		
		if ((! empty ( $input ['allocatedTo'] ))) {
			$response = $this->users->createAllocation ( $input );
			if ($response) {
				echo "Saved";
			} else {
				echo "UnSaved";
			}
		} else {
			$response = array (
					'message' => 'Missing parameters',
					'success' => false 
			);
			$this->response ( $response, 200 );
		}
	}
	function deallocation_post() {
		if ($this->post ( 'allocationId' )) {
			$input = array (
					'deallocationDate' => date ( "Y-m-d H:i:s" ),
					'deallocatedBy' => $this->post ( 'deallocatedBy' ) 
			);
		}
		
		if ((! empty ( $input ['deallocatedBy'] ))) {
			$response = $this->users->createDeallocation ( $input, $this->post ( 'allocationId' ) );
			if ($response) {
				echo "Saved";
			} else {
				echo "UnSaved";
			}
		} else {
			$response = array (
					'message' => 'Missing parameters',
					'success' => false 
			);
			$this->response ( $response, 200 );
		}
	}
	function customerSyncCheck_post() {
		if ($this->post ( 'contactCount' )) {
			$contactCount = $this->post ( 'contactCount' );
			
			$response = $this->customers->SyncCheck ( $contactCount );
			
			$this->response ( $response, 200 );
		}else{
			$this->response ("Contact Count Not sent", 404 );
		}
	}
	function customerSync_post() {
		
		if ($this->post ( 'countDifference' ) > 0) {
			$syncStart = $this->post ( 'contactCount' ) + 1;
			$syncStop = $this->post( 'contactCount' ) + $this->post('countDifference');
			$response = array ();
			
			for($i = $syncStart; $i <= $syncStop; $i ++) {
				$data = $this->customers->getSingleCustomer ( 'Recid', $i );
				array_push ( $response, $data );
			}
			
			$this->response ( $response, 200 );
		}
	}
}