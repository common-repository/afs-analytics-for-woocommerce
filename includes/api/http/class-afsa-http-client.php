<?php

require_once 'class-afsa-http-response.php';

use AFSA_HTTP_Response as response;

class AFSA_HTTP_Client {

	private $response;

	public function __construct( $p = array() ) {

	}

	public function get_version() {
		return AFSA_Config::CMS_version(); // using WP API
	}

	public function post( $url, $args ) {

		$ret = wp_remote_post(
			$url,
			array(
				'method'  => 'POST',
				'body'    => $args,
				'timeout' => 30,
			)
		);

		$this->response = new response( $ret );

		return $this->response;
	}

	public function get( $url, $args ) {
		return $this->response = new response( wp_remote_get( $url, array( 'body' => $args ) ) );
	}

	public function get_json() {
		return $this->response ? $this->response->get_json() : null;
	}

	public function get_headers() {
		return $this->response ? $this->response->get_headers() : null;
	}

	public function get_body() {
		return $this->response ? $this->response->get_body() : null;
	}

}
