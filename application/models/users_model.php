<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
class Users_Model extends CI_Model {
	var $groups = array ();
	var $user = array ();
	
	// other user defined variables:
	var $cookie_expire = '86500'; // when to expire cookies
	var $cookie_domain = ''; // cookie domain
	function __construct() {
		parent::__construct ();
		// session auth
		if ($this->session->userdata ( 'user' )) {
			$this->user = $this->session->userdata ( 'user' );
		}
	}
	function updatePassword($userId, $newPw, $userName, $imeiCode) {
		$newPassword = array (
				'password' => $newPw 
		);
		$this->db->where ( 'userId', $userId );
		$query = $this->db->update ( 'Users', $newPassword );
		if ($query) {
			return $this->login ( $userName, $newPw, $imeiCode );
		} else {
			return array (
					'success' => false 
			);
		}
	}
	function insertUserToDb($userInfo) {
		// Log Information
		// echo "Inserting User Information";
		$input = array (
				'userName' => $userInfo->tuserid,
				'firstName' => $userInfo->tuserid,
				'lastName' => $userInfo->surname,
				'tuserid' => $userInfo->tuserid 
		);
		
		$query = $this->db->insert ( 'users', $input );
		$userId = $this->db->insert_id ();
		
		if (! $query) {
			return false;
		}
		/*
		 * Insert to UserGroup
		 */
		$userGroup = $userInfo->tfunction;
		if ($userGroup == 'Supervisor') {
			$inp = array (
					'groupId' => 2,
					'userId' => $userId 
			);
			$query = $this->db->insert ( 'usergroup', $inp );
		} else {
			$inp = array (
					'groupId' => 1,
					'userId' => $userId 
			);
			$query = $this->db->insert ( 'usergroup', $inp );
		}
		
		if (! $query) {
			array (
					'success' => false 
			);
		}
		
		return array (
				'authorize' => true,
				'userId' => ( string ) $userId,
				'userName' => $userInfo->tuserid,
				'firstTime' => true 
		);
	}
	function createUser($userInfo) {
		$this->db->query ( 'Use mobileBanking' );
		/*
		 * Insert to Users
		 */
		$query = $this->db->get_where ( 'users', array (
				'tuserid' => $userInfo->tuserid 
		) );
		$existingUser = $query->row ();
		
		if ($query->num_rows () > 0) {
			// echo "Found a user entry";
			return array (
					'authorize' => true,
					'userId' => ( string ) $existingUser->userId,
					'userName' => $existingUser->tuserid,
					'firstTime' => true 
			);
		}
		return $this->insertUserToDb ( $userInfo );
	}
	
	/*
	 * Function to perform Login based on the Local Users table
	 */
	function localLogin($userName = null, $password = null) {
		$this->db->query ( 'Use MergeFinals' );
		
		if ($password != 'pioneer') {
			return array (
					'error' => "Wrong userName or password",
					'authorize' => false 
			);
			return;
		}
		
		// 1. Match the UserName and Password Combination
		$this->db->limit ( 1 );
		$query = $this->db->get_where ( 'users', array (
				'tuserid' => $userName 
		) );
		
		if ($query->num_rows () > 0) {
			// Create this user Locally
			$userInfo = $query->row (); // user Data
			$response = $this->createUser ( $userInfo );
			
			return $response;
		} else {
			return $this->falseAuthentication ();
		}
	}
	
	/*
	 * Login the user to the server on the cloud
	 */
	function login($userName = null, $password = null, $imeiCode = null) {
		// 1. Match the UserName and Password Combination
		$this->db->limit ( 1 );
		$query = $this->db->get_where ( 'users', array (
				'userName' => $userName,
				'password' => $password 
		) );
		
		// echo $this->db->last_query();
		
		// --Results found
		if ($query->num_rows () > 0) {
			// User Data
			$userInfo = $query->row ();
			
			// Check the User's Group
			$groupData = $this->checkUserGroups ( $userInfo );
			
			// Generate Cookie
			$this->generateCookie ( $userInfo );
			
			// Get Session Information
			return $this->getSessionInfo ( $userInfo, $groupData, $imeiCode );
		} else {
			$session_data = $this->falseAuthentication ();
			return $session_data;
		}
	}
	function checkUserGroups($userInfo) {
		$query2 = $this->db->get_where ( 'usergroup', array (
				'userId' => $userInfo->userId 
		) );
		$userdata = $query2->result ();
		
		// Add User Group
		$groupsData = array ();
		foreach ( $userdata as $value ) {
			$this->db->select ( 'groupName' );
			$this->db->where ( array (
					'groupId' => $value->groupId 
			) );
			$query = $this->db->get ( 'groups' );
			$groupsData [] = $query->row ()->groupName;
		}
		
		// Save Data to session
		$this->update_session ( $userInfo, $groupsData, NULL );
		return $groupsData;
	}
	function getSessionInfo($userInfo, $groupsData, $imeiCode) {
		$session_data = array (
				'userId' => ( string ) $userInfo->userId,
				'firstName' => $userInfo->firstName,
				'lastName' => $userInfo->lastName,
				'userName' => $userInfo->userName,
				'firstTime' => false 
		);
		// ---Check for Allocation(Admin has straight Pass) /////
		if (in_array ( 'Admin', $groupsData )) {
				$session_data ['authorize'] = true;
				$session_data ['group'] = 'Admin';
				$session_data ['isAllocated'] = true;
				return $session_data;
		} else if ((in_array ( 'fieldofficer', $groupsData ))) {
			if ($this->check_allocation ( $userInfo, $imeiCode )) {
				$session_data ['authorize'] = true;
				$session_data ['group'] = 'fieldofficer';
				$session_data ['isAllocated'] = true;
				return $session_data;
			} else {
				$session_data ['authorize'] = true;
				$session_data ['group'] = 'fieldofficer';
				$session_data ['isAllocated'] = false;
				$session_data ['error'] = 'You have not been Allocated this device.';
				return $session_data;
			}
		} else {
			$session_data = $this->localLogin ( $userName, $password );
			return $session_data;
		}
	}
	function generateCookie($userInfo) {
		// GENERATE COOKIE
		$salt = microtime ();
		$cookie_hash = $this->_add_salt ( $userInfo->userId . $userInfo->userName . $userInfo->password . $userInfo->email, $salt );
		
		// set cookie with very unique cookie hash
		$cookie = array (
				'name' => 'userInfo',
				'value' => $cookie_hash,
				'expire' => $this->cookie_expire,
				'domain' => $this->cookie_domain,
				'path' => '/',
				'prefix' => 'pioneer_' 
		);
		
		set_cookie ( $cookie );
	}
	function falseAuthentication() {
		return array (
				'authorize' => false,
				'error' => 'Wrong UserName and Password Combination' 
		);
	}
	
	/*
	 * Check If user is allocated to terminal 
	 * @params $Logged-In User Array
	 */
	function check_allocation($userInfo, $imeiCode) {
		// Get the TerminalId
		$this->db->select ( 'terminalId' );
		$this->db->where ( 'imeiCode', $imeiCode );
		$query = $this->db->get ( 'terminal' );
		if ($query->num_rows () == 0) {
			return false;
		}
		$terminalId = $query->row ()->terminalId;
		
		/* 1--Check Allocation for logging In
		 * -- Allocation during Logging In
		 * */
		if ($userInfo) {
			$this->db->select ( 'allocationDate' );
			$this->db->where ( array (
					'allocatedTo' => $userInfo->userId,
					'terminalId' => $query->row ()->terminalId,
					'deallocatedBy' => NULL 
			) );
			$query = $this->db->get ( 'allocation' );
		} 
		/*
		 * 2--Checking for allocation for a terminal(Initial)
		 *  --For the setUp page
		 */ 
		else {
			$this->db->select ( 'allocationDate,allocatedBy,allocatedTo,allocationId' );
			$this->db->where ( array (
					'terminalId' => $query->row ()->terminalId,
					'deallocatedBy' => NULL 
			) );
			$query = $this->db->get ( 'allocation' );
		}
		
		// if Data is Present
		if ($query->num_rows () > 0) {
			if ($userInfo) {
				$this->update_session ( NULL, NULL, $terminalId );
				return true;
			} else {
				return $query->row_array ();
			}
		} else {
			return false;
		}
	}
	function createAllocation($input) {
		if ($this->db->insert ( 'allocation', $input )) {
			return true;
		} else {
			return false;
		}
	}
	function createDeallocation($input, $allocationId) {
		$this->db->where ( 'allocationId', $allocationId );
		$query = $this->db->update ( 'Allocation', $input );
		if ($query) {
			return true;
		} else {
			return false;
		}
	}
	/*
	 * Get Allocation Summary during deallocation
	 */
	function getAllocationDetails($allocationId){
		$queryStatement="select terminalName,Users.userName,Users.firstName +' '+ Users.lastName AS allocateeNames,".
						"allocatedTo,allocationDate,allocationId from Allocation ".
						"INNER JOIN Terminal ON Allocation.terminalId=Terminal.terminalId INNER JOIN Users".
						" ON Allocation.allocatedTo = Users.userId where allocationId=".$allocationId;
		$query = $this->db->query ( $queryStatement);
		
		if ($query->result()) {
			return $query->result()[0];
		} else {
			return false;
		}
	}
	
	/*
	 * update session data with user user data
	 */
	function update_session($user_array, $groupsData, $terminalId) {
		if ($user_array) {
			$ez ['user'] = $user_array;
		} elseif ($groupsData) {
			$ez ['groups'] = $groupsData;
		} elseif ($terminalId) {
			$ez ['terminal'] = $terminalId;
		}
		$this->session->set_userdata ($ez );
		$this->user = $ez;
	}
	// v 0.2
	// used for generating random authorization codes and temporary passwords
	function random_string($length = 4) {
		
		// Generate random 4 character string
		$string = md5 ( microtime () );
		
		// Take a random starting point in the randomly
		// Generated String, not going any higher then $highest_startpoint
		$randomString = substr ( $string, 1, $length );
		
		return $randomString;
	}
	
	// v 0.2
	function _add_salt($pw, $salt = 'shakeit') {
		$md5_string = '';
		if (is_array ( $pw )) {
			foreach ( $pw as $word ) {
				$md5_string .= md5 ( $word );
			}
		} else {
			$md5_string = $pw;
		}
		return md5 ( md5 ( $salt ) . md5 ( $md5_string ) );
	}
	function logout() {
		if ($this->session->sess_destroy ()) {
			return array (
					'success' => true 
			);
		} else {
			return array (
					'success' => false 
			);
		}
	}
	
	/*
	 * Description: Filter the Users List if A user has a previous Allocation or Admin Date: 11/4/2014
	 */
	function filter_by_Allocation($userId) {
		// Get the Allocation
		$this->db->where ( 'allocatedTo', $userId );
		$this->db->where ( 'deallocatedBy', NULL );
		$query = $this->db->get ( 'Allocation' );
		
		if ($query->num_rows () == 0) {
			return true;
		} else {
			return false;
		}
	}
	function filter_by_Group($userId) {
		
		// Check the User's Group
		$query2 = $this->db->get_where ( 'usergroup', array (
				'userId' => $userId 
		) );
		$userdata = $query2->result ();
		
		// Add User Group
		$groupsData = array ();
		foreach ( $userdata as $value ) {
			$this->db->select ( 'groupName' );
			$this->db->where ( array (
					'groupId' => $value->groupId 
			) );
			$query = $this->db->get ( 'groups' );
			$groupsData [] = $query->row ()->groupName;
		}
		
		if (in_array ( 'Admin', $groupsData )) {
			return false;
		} else {
			return true;
		}
	}
	function getUsers() {
		$this->db->select ( 'userId,firstName,lastName,userName' );
		$query = $this->db->get ( 'users' );
		
		$userData = $query->result_array ();
		
		// Do some Checks
		$response = array ();
		
		foreach ( $userData as $row ) {
			// Filter out Administrators
			$admin_check = $this->filter_by_Group ( $row ['userId'] );
			
			if ($admin_check) {
				// echo $row ['userId'] . "-Passed Admin Check";
				$allocation_check = $this->filter_by_Allocation ( $row ['userId'] );
			} else {
				$allocation_check = false;
			}
			
			if ($allocation_check) {
				// echo $row ['userId'] . "-Passed Allocation Check; ";
				$data = array (
						'userId' => ( string ) $row ['userId'],
						'firstName' => trim ( $row ['firstName'] ),
						'lastName' => trim ( $row ['lastName'] ),
						'userName' => trim ( $row ['userName'] ) 
				);
				array_push ( $response, $data );
				// echo $row ['userId'] . "-Added to Array ";
			}
		}
		
		return $response;
	}
	function getUserById($userId) {
		$this->db->select ( 'userId,firstName,lastName,userName' );
		$this->db->where ( array (
				'userId' => $userId 
		) );
		$query = $this->db->get ( 'users' );
		
		$userData = $query->row_array ();
		
		$data = array (
				'userId' => ( string ) $userData ['userId'],
				'firstName' => $userData ['firstName'],
				'lastName' => $userData ['lastName'],
				'userName' => $userData ['userName'] 
		);
		return $userData;
	}
	
	function getUsersByUserName($userName){
		$this->db->select ( 'userId' );
		$this->db->where ( array (
				'userName' => $userName
		) );
		$query = $this->db->get ( 'users' );
		
		$userData = $query->row();
		return $userData;
	}
}

?>