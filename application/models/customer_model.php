<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
class Customer_Model extends CI_Model {
	function getCustomer($parameter, $value) {
		$this->db->where ( array (
				$parameter => $value 
		) );
		$query = $this->db->get ( 'Client' );
		$this->db->order_by ( "Recid", "asc" );
		
		// echo $this->db->last_query();
		
		$response = array ();
		$customers = $query->result_array ();
		
		foreach ( $customers as $row ) {
			$fullNames = trim ( isset ( $query->row ()->refno ) ) . " " . 
						 trim ( isset ( $query->row ()->clname ) ) . " " .
						 trim ( isset ( $query->row ()->middlename ) ) . " " .
						 trim ( isset ( $query->row ()->clsurname ) );
			
			$data = array (
					'firstName' => trim ( isset ( $query->row ()->clname ) ),
					'middleName' => trim ( isset ( $query->row ()->middlename ) ),
					'lastName' => trim ( isset ( $query->row ()->middlename ) ),
					'fullNames' => $fullNames,
					'refNo' => trim ( isset ( $query->row ()->refno ) ),
					'mobileNo' => trim ( isset ( $query->row ()->phone ) ),
					'customerId' => trim ( isset ( $query->row ()->clcode ) ) 
			);
			array_push ( $response, $data );
		}
		return $response;
	}
	
	/*
	 * Repetition -Should find a solution to this immediately
	 */
	function getSingleCustomer($parameter, $value) {
		$this->db->where ( array (
				$parameter => $value 
		) );
		$query = $this->db->get ( 'Client' );
		
		// print_r($query->result());
		$fullNames = trim ( (isset ( $query->row ()->refno )) ? ($query->row ()->refno) : "N/a" ) . " " .
				trim ( (isset ( $query->row ()->clname )) ? ($query->row ()->clname) : "N/a" ) . " " .
				trim ( (isset ( $query->row ()->middlename )) ? ($query->row ()->middlename) : "N/a" ) . " " .
				trim ( (isset ( $query->row ()->clsurname )) ? ($query->row ()->clsurname) : "N/a" );
			
		$custData = array (
				'firstName' => trim ( (isset ( $query->row ()->clname )) ? ($query->row ()->clname) : "N/a" ),
				'middleName' => trim ( (isset ( $query->row ()->middlename )) ? ($query->row ()->middlename) : "N/a" ),
				'lastName' => trim ( (isset ( $query->row ()->clsurname )) ? ($query->row ()->clsurname) : "N/a" ),
				'fullNames' => $fullNames,
				'refNo' => trim ( (isset ( $query->row ()->refno )) ? ($query->row ()->refno) : "N/a" ),
				'mobileNo' => trim ( (isset ( $query->row ()->phone )) ? ($query->row ()->phone) : "N/a" ),
				'customerId' => trim ( (isset ( $query->row ()->clcode )) ? ($query->row ()->clcode) : "N/a" ) 
		);
		return $custData;
	}
	function UpdateCustomer($clCode, $newValue) {
		$this->db->where ( 'clcode', $clCode );
		$query = $this->db->update ( 'Client', $newValue );
		if ($query) {
			// echo $this->db->last_query();
			return true;
		} else {
			return false;
		}
	}
	/* Check the difference in Customer Numbers */
	function syncCheck($clientCount) {
		$query = $this->db->get ( 'Client' );
		$count = $query->num_rows ();
		
		$countDifference = $count - $clientCount;
		return array (
				'countDifference' => $countDifference 
		);
	}
}