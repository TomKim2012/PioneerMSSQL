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
			$data=array('firstName' => trim($row['clname']),
						'lastName' => trim($row['clsurname']),
						'refNo'  => trim($row['refno']),
						'mobileNo'  => trim($row['phone']),
						'customerId'  => trim($row['clcode'])
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
		
		//echo $this->db->last_query();
		
		$custData= array( 'firstName'=> trim($query->row()->clname),
						  'lastName'=> trim($query->row()->clsurname),
						  'refNo' => trim($query->row()->refno),
						  'mobileNo'=> trim($query->row()->phone),
						  'customerId'=>trim($query->row()->clcode)
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