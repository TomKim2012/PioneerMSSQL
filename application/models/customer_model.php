<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_Model extends CI_Model {

	function getCustomer($parameter, $value){
		$this->db->where(
				array($parameter=>$value,
				));
		$query=$this->db->get('Client');
		
// 		echo $this->db->last_query();
		$custData=$query->result_array();
		return $custData;
	}
	
	
	/*
	 * Repetition -Should find a solution to this immediately
	 */
	function getSingleCustomer($parameter, $value){
		$this->db->where(
				array($parameter=>$value,
				));
		$query=$this->db->get('Client');
		//print_r($query->row()->clname);
		$custData= array( 'firstName' => $query->row()->clname,
						  'lastName' => $query->row()->clsurname,
						  'refNo' => $query->row()->refno
						);
		return $custData;
	}
	
}