<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_Model extends CI_Model {

	function getCustomer($parameter, $value){
		$this->db->where(
				array($parameter=>$value,
				));
		$query=$this->db->get('Client');
		
		//echo $this->db->last_query();
		
		$response=array();
		$customers=$query->result_array();
		
		foreach ($customers as $row) {
			$data= array( 'firstName'=> trim(isset($query->row()->clname)),
						  'middleName'=> trim(isset($query->row()->middlename)),
						  'lastName'=> trim(isset($query->row()->clsurname)),
						  'refNo' => trim(isset($query->row()->refno)),
						  'mobileNo'=> trim(isset($query->row()->phone)),
						  'customerId'=>trim(isset($query->row()->clcode))
						);
			array_push($response, $data);
		}
		return $response;
	}
	
	
	/*
	 * Repetition -Should find a solution to this immediately
	 */
	function getSingleCustomer($parameter, $value){
		$this->db->where(
				array($parameter=>$value,
				));
		$query=$this->db->get('Client');
		
		//print_r($query->result());
		
		$custData= array( 
						 'firstName'=> trim((isset($query->row()->clname))?($query->row()->clname):"N/a"),
						 'middleName'=> trim((isset($query->row()->middlename))?($query->row()->middlename):"N/a"),
						 'lastName'=> trim((isset($query->row()->clsurname))?($query->row()->clsurname):"N/a"),
						 'refNo' => trim((isset($query->row()->refno))?($query->row()->refno):"N/a"),
						 'mobileNo'=> trim((isset($query->row()->phone))?($query->row()->phone):"N/a"),
						 'customerId'=>trim((isset($query->row()->clcode))?($query->row()->clcode):"N/a")
						);
		return $custData;
	}
	
	function UpdateCustomer($clCode, $newValue){
		$this->db->where('clcode', $clCode);
		$query=$this->db->update('Client',$newValue);
		if($query){
			//echo $this->db->last_query();
			return true;
		}else{
			return false;
		}
	}
	
}