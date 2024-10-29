<?php

require_once 'class-afsa-oauth-token.php';
require_once 'class-afsa-oauth-demo-token.php';



require_once AFSA_INCLUDES_DIR . '/api/http/class-afsa-http-client.php';

class AFSA_OAuth_Client {

	private $oauth_server_url = null;
	private $token            = null;
	private $client_id;
	private $client_secret = '';
	private $url;

	public function __construct( $account_id, $callback_url ) {
		$this->oauth_server_url = AFSA_Config::get_oauth_server_url();

		AFSA_Tools::log( 'CB URL ', $callback_url );

		$this->url = array(
			'authorize' => $this->oauth_server_url . 'auth/' . $account_id . '/',
			'token'     => $this->oauth_server_url . 'token',
			'resource'  => $this->oauth_server_url . '',
			'callback'  => $callback_url,
		);

		$this->account_id = $account_id;

		$this->client_id = AFSA_Config::get_oauth_client_id();
		$this->token     = AFSA_Config::is_demo() ?
			new AFSA_OAuth_Demo_Token( $account_id ) :
			new AFSA_OAuth_Token( $account_id );
	}

	public function get_resource_url() {
		return $this->url['resource'];
	}

	public function is_logged() {
		if ( $this->token->load() ) {
			return ! $this->token->is_expired();
		}
		return false;
	}

	/**
	 * only login, do not request auth code if no token
	 */
	public function simple_login() {
		return $this->login( true );
	}

	public function login( $no_authorization_request = false ) {
		AFSA_Tools::log( __METHOD__ );

		if ( $this->token->load() ) {
			AFSA_Tools::log( __METHOd__, $this->token->get_data() );

			AFSA_Tools::log( 'expires in', $this->token->days_before_expiration(), $this->token->is_expired() );

			if ( ! $this->token->is_expired() ) {
				return true;
			}

			// On Expired Token

			AFSA_Tools::log( 'Refreshing token' );
			// getting a new access token from stored refresh token
			if ( $this->refresh_token() ) {
				return true;
			}

			// could not obtain a new access token
			// => clear current token and restart auth process
			$this->token->clear();
			$this->token = new AFSA_OAuth_Token( $this->account_id );

			if ( $no_authorization_request ) {
				return false;
			} else {
				$this->request_authorization_code();
			}

			return true;
		}

		// On No Token available

		AFSA_Tools::log( 'cant load token' );

		if ( ! empty( $_REQUEST['noredir'] ) ) {
			return false;
		}

		if ( ! empty( $_GET['code'] ) ) {
			$ret = $this->on_authorization_code_received();
			return empty( $ret['error'] );
		}

		if ( $no_authorization_request ) {
			return false;
		} else {
			$this->request_authorization_code();
		}

		return false;
	}

	public function logout() {
		$this->token->clear();
	}

	/**
	 * Redirect to Server Authorization page to request an authorization code.
	 */
	private function request_authorization_code() {
		$state = base64_encode( random_bytes( 32 ) );
		AFSA_Config::save_oauth_state( $state );

		$params = array(
			'response_type'   => 'code',
			// 'account_id' => $this->account_id,
			'client_id'       => $this->client_id,
			'client_api'      => AFSA_MODULE_VERSION,
			'redirect_uri'    => $this->url['callback'],
			'state'           => $state,
			'approval_prompt' => 'auto',
			'access_type'     => 'offline',
			'interact'        => 1,
			'resolve_account' => AFSA_Config::get_config_controller_url(),
		);

		AFSA_Config::save_oauth_callback_url( $this->url['callback'] );

		AFSA_Tools::log( __METHOD__, $params );

		AFSA_Tools::redirect( $this->url['authorize'] . '?' . http_build_query( $params ) );
		exit;
	}

	public function on_authorization_code_received() {
		$ret = array(
			'error' => true,
			'msg'   => array(),
		);

		$state = sanitize_text_field( $_REQUEST['state'] );
		AFSA_Tools::log(
			__METHOD__,
			array(
				$state,
				get_option( 'afs_analytics_oauth_state' ),
			)
		);

		if ( $state !== AFSA_Config::get_oauth_state() ) {
			$ret['msg'][] = __( 'oauth_invalid_state', 'afsanalytics' );
		}

		$code = sanitize_text_field( $_REQUEST['code'] );

		// RETRIEVE TOKEN FROM CODE

		$client = new AFSA_HTTP_Client();

		$r = $client->post(
			$this->url['token'],
			array(
				'grant_type'   => 'authorization_code',
				'code'         => $code,
				'client_id'    => $this->client_id,
				'client_api'   => AFSA_MODULE_VERSION,
				// retrieving url saved by request_authorization_code()
				'redirect_uri' => AFSA_Config::get_oauth_callback_url(),
			)
		);

		$data = $client->get_json();

		AFSA_Tools::log(
			__METHOD__,
			array(
				'data'         => $data,
				'url'          => $this->url['token'],
				'grant_type'   => 'authorization_code',
				'code'         => $code,
				'client_id'    => $this->client_id,
				'client_api'   => AFSA_MODULE_VERSION,
				'redirect_uri' => $this->url['callback'],
			)
		);

		if ( empty( $data ) ) {
			$ret['msg'][] = 'Empty data';
		} elseif ( ! empty( $data['error'] ) ) {
			$ret['msg'][] = $data['error_description'];

			AFSA_Tools::log( 'ERR2', $data['error_description'] );

			switch ( $r->get_status_code() ) {
					// authorization code is invalide
					// need to restart auth process
				case 400: // TODO ?
					// $this->request_authorization_code();
					break;
			}
		} else {
			$this->token->set( $data, true );
			$ret['error'] = false;
		}

		return $ret;
	}

	// TOKEN RELATED FUNCTIONS

	public function get_token() {
		return $this->token;
	}

	public function get_access_token() {
		return $this->token->get_access_token();
	}

	public function delete_token() {
		$this->token->clear();
	}

	public function get_token_data() {
		return $this->token->get_data();
	}

	private function refresh_token() {
		$client = new AFSA_HTTP_Client( array( 'auth' => array( $this->client_id, $this->client_secret ) ) );

		// If we have a refresh token => try to get a new access token
		if ( ! empty( $this->token->get_refresh_token() ) ) {
			$client->post(
				$this->url['token'],
				array(
					'grant_type'    => 'refresh_token',
					'client_id'     => $this->client_id,
					'client_api'    => AFSA_MODULE_VERSION,
					'refresh_token' => $this->token->get_refresh_token(),
				)
			);

			$data = $client->get_json();

			$data['method'] = __METHOD__;

			if ( empty( $data['error'] ) ) {
				$this->token->set( $data, true );
				return true;
			}
		}

		return false;
	}
}
