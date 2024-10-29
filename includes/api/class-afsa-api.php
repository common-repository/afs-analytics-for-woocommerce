<?php

require_once AFSA_INCLUDES_DIR . '/api/oauth/class-afsa-oauth-client.php';

class AFSA_Api {

	private $access_token;
	private $http_client;
	private $oauth_client;
	private $client_id;
	private $server_url;
	private $account_id;
	private $callback_url = null;

	public function __construct( $cfg = array() ) {

		if ( ! empty( $cfg['callback_url'] ) ) {
			$this->callback_url = $cfg['callback_url'];
		}

		$this->client_id = AFSA_Config::get_oauth_client_id();

		$this->account_id = empty( $cfg['account_id'] ) ?
				AFSA_Config::get_account_id() :
				$cfg['account_id'];
	}

	public function simple_login() {
		return $this->login( array( 'simple_login' => true ) );
	}

	public function login( $params = array() ) {

		if ( empty( $this->account_id ) ) {
			return false;
		}

		$c = $this->get_oauth_client();

		$this->server_url = $c->get_resource_url();

		$ret = empty( $params['simple_login'] ) ?
				$c->login() :
				$c->simple_login();

		if ( $ret ) {
			$this->access_token = $c->get_access_token();
		}

		return $ret;
	}

	public function is_logged() {

		$c = $this->get_oauth_client();
		if ( $c->is_logged() ) {
			$this->server_url   = $c->get_resource_url();
			$this->access_token = $c->get_access_token();
			return true;
		}

		return false;
	}

	public function get_access_token() {
		return $this->access_token;
	}

	public function logout() {
		$this->get_oauth_client()->logout();
	}

	private function get_oauth_client() {
		return empty( $this->oauth_client ) ?
				$this->oauth_client = new AFSA_OAuth_Client(
					$this->account_id,
					$this->callback_url ?
					$this->callback_url :
					AFSA_Config::get_current_url()
				) :
				$this->oauth_client;
	}

	private function get_http_client() {
		return empty( $this->http_client ) ?
				$this->http_client = new AFSA_HTTP_Client() :
				$this->http_client;
	}

	private function prepare_params( &$p ) {
		foreach (
		array(
			'client_id'    => $this->client_id,
			'access_token' => $this->access_token,
		) as $k => $v ) {
			$p[ $k ] = $v;
		}

		return $p;
	}

	/**
	 * SEND request.
	 *
	 * @param string $endpoint
	 * @param array  $param
	 *
	 * @return array
	 */
	public function send_request( $endpoint, $params = array(), $method = 'get' ) {

		if ( empty( $this->access_token ) ) {
			AFSA_Tools::log( __METHOD__ . ' empty token' );
			return null;
		}

		$this->prepare_params( $params );
		$url = chop( $this->server_url, '/' ) . $endpoint;

		AFSA_Tools::log( '[PS]API ' . $method . ': ' . $url . PHP_EOL . json_encode( $params, JSON_PRETTY_PRINT ) );

		$method == 'post' ?
						$this->get_HTTP_Client()->post( $url, $params ) :
						$this->get_HTTP_Client()->get( $url, $params );

		return $this->get_HTTP_Client()->get_json();
	}

	public function get( $endpoint, $params = array() ) {
		return $this->send_request( $endpoint, $params, 'get' );
	}

	public function post( $endpoint, $params = array() ) {
		return $this->send_request( $endpoint, $params, 'post' );
	}

}
