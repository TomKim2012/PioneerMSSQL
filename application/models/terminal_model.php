<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
class Terminal_Model extends CI_Model {
	var $terminal;
	function __construct() {
		parent::__construct ();
		if ($this->session->userdata ( 'user' )) {
			$this->terminal = $this->session->userdata ( 'terminal' );
		}
	}
	function createTerminal($terminalDetails) {
		$response = $this->checkTerminal ( $terminalDetails ['imeiCode'] );
		if ($response ['success']) {
			if ($this->db->insert ( 'terminal', $terminalDetails )) {
				return array (
						'success' => true,
						'terminalId' => $this->db->insert_id () 
				);
			} else {
				return array (
						'success' => true 
				);
			}
		} else {
			return $response;
		}
	}
	function checkTerminal($imeiCode) {
		$this->db->where ( array (
				'imeiCode' => $imeiCode 
		) );
		$query = $this->db->get ( 'terminal' );
		if ($query->num_rows () > 0) {
			return array (
					'success' => false,
					'terminalId' => $query->row ()->terminalId 
			);
		} else {
			return array (
					'success' => true 
			);
		}
	}
	function getTerminalById() {
		$this->db->where ( array (
				'terminalId' => $this->terminal 
		) );
		$query = $this->db->get ( 'terminal' );
		return $query-> row();
		//print_r ( $query->row () );
	}
}

?>