<?php

abstract class AFSA_Infos {

	protected $data;

	public function __construct() {
		$this->data = array();
		$this->retrieve();
	}

	public function validate() {
		return true;
	}

	public function get( $key = null ) {
		return $key ?
				empty( $this->data[ $key ] ) ?
				null :
				$this->data[ $key ] :
				$this->data;
	}

	abstract public function retrieve();
}
