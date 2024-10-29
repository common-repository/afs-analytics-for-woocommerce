<?php

class AFSA_OAuth_Token {

	private $account_id;
	private $tokens = array();

	public function __construct( $account_id ) {
		$this->account_id = $account_id;
		$this->load_all();
	}

	public function load() {
		return $this->get_access_token() != null;
	}

	public function load_all() {
		$json         = get_option( AFSA_OPTION_OAUTH_TOKEN );
		$this->tokens = $json ? json_decode( $json, 1 ) : array();
	}

	public function save() {
		update_option( AFSA_OPTION_OAUTH_TOKEN, json_encode( $this->tokens ) );
	}

	public static function clear() {
		delete_option( AFSA_OPTION_OAUTH_TOKEN );
		delete_option( AFSA_OAUTH_CB_URL );
	}


	public function set( $data, $save = true ) {
		AFSA_Tools::log( 'TOKEN', __METHOD__, $data );

		if ( empty( $data ) || empty( $data['access_token'] ) ) {
			return false;
		}

		if ( empty( $data['expires_at'] ) ) {
			$data['expires_at'] = time() + $data['expires_in'];
		}

		$p            = parse_url( AFSA_Config::get_afsa_home() );
		$data['host'] = $p['host'];

		$this->tokens[ $this->account_id ] = $data;

		if ( $save ) {
			$this->save();
		}

		return true;
	}

	public function get_data() {
		return empty( $this->tokens[ $this->account_id ] ) ?
				null :
				$this->tokens[ $this->account_id ];
	}

	private function get_value( $field ) {
		$data = $this->get_data();
		if ( empty( $data ) ) {
			return null;
		}

		return empty( $data ) || empty( $data[ $field ] ) ?
				null : $data[ $field ];
	}

	public function get_access_token() {
		return $this->get_value( 'access_token' );
	}

	public function get_refresh_token() {
		return $this->get_value( 'refresh_token' );
	}

	public function is_expired( $min_seconds = 60 ) {
		return $this->seconds_before_expiration() < $min_seconds;
	}

	public function seconds_before_expiration() {
		AFSA_Tools::log( 'E:' . $this->get_value( 'expires_at' ) . ' N:' . time() );

		return $this->get_value( 'expires_at' ) - time();
	}

	public function days_before_expiration() {
		return floor( $this->seconds_before_expiration() / ( 3600 * 24 ) );
	}

	public function hours_before_expiration() {
		return floor( $this->seconds_before_expiration() / 3600 );
	}

	public function dump() {
		AFSA_Tools::log(
			__METHOD__,
			array_merge(
				$this->get_data(),
				array(
					'sec'       => $this->seconds_before_expiration(),
					'hours'     => $this->hours_before_expiration(),
					'days'      => $this->days_before_expiration(),
					'isexpired' => $this->is_expired(),
				)
			)
		);
	}

}
