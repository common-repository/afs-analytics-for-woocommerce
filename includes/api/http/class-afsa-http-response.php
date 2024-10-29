<?php

// https://developer.wordpress.org/reference/functions/wp_remote_request/
//
//
//
// $data = array(
// 'headers'  => array(),
// 'response' => array(
// 'code'    => int,
// 'message' => string
// )
// );
//


class AFSA_HTTP_Response {

	public function __construct( $data ) {
		$this->data = $data;
	}

	public function get_json() {
		if ( ! is_array( $this->data ) || empty( $this->data['body'] ) ) {
			return null;
		}

		try {
			return json_decode( $this->data['body'], true );
		} catch ( Exception $e ) {

		}

		return null;
	}

	public function get_headers() {
		try {
			return $this->data['headers'];
		} catch ( Exception $e ) {

		}

		return null;
	}

	public function get_body() {
		try {
			return $this->data['body'];
		} catch ( Exception $e ) {

		}

		return null;
	}

	public function get_status_code() {
		try {
			return $this->data['response']['code'];
		} catch ( Exception $e ) {

		}

		return 0;
	}

}
