<?php

define( 'AFSA_WORDPRESS_DEMO_TOKEN', 'f5fac1d7fd3eek5a7ddc0cef1f6a7582' );

class AFSA_OAuth_Demo_Token {

	private $data;

	public function save() {

	}

	public function load() {

		$this->data = array(
			'access_token' => AFSA_WORDPRESS_DEMO_TOKEN,
		);

		return $this->get_access_token() != null;
	}

	public function is_demo() {
		return true;
	}

	public static function clear() {

	}

	public function set() {
		return true;
	}

	public function get_data() {
		return $this->data;
	}

	private function get_value( $field ) {
		return empty( $this->data[ $field ] ) ? null : $this->data[ $field ];
	}

	public function get_access_token() {
		return $this->get_value( 'access_token' );
	}

	public function get_refresh_token() {
		return $this->get_value( 'refresh_token' );
	}

	public function is_expired() {
		return false;
	}

	public function seconds_before_expiration() {
		return 9999;
	}

	public function days_before_expiration() {
		return 9999;
	}

	public function hours_before_expiration() {
		return 9999;
	}

	public function dump() {
		AFSA_Tools::log(
			__METHOD__,
			'DEMO TOKEN'
		);
	}

}
