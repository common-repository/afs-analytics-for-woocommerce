<?php

defined( 'ABSPATH' ) || exit;

require_once AFSA_INCLUDES_DIR . '/account/class-afsa-account.php';

class AFSA_Account_Manager {

	private static $instance;
	private $accounts = array();

	public static function get() {
		return static::$instance ?
				static::$instance :
				static::$instance = new AFSA_Account_Manager();
	}

	public static function validate_id( $id ) {
		return ! empty( $id ) && ctype_digit( $id ) && strlen( $id ) == 8 && (int) $id > 0;
	}

	public static function on_ajax_infos_received( $data ) {

		if ( empty( $data['plan'] ) || empty( $data['id'] ) ) {
			return;
		}

		$account_id = $data['id'];
		$account    = new AFSA_Account( $account_id );
		$account->update_plan_from_data( $data['plan'] );
	}

	public function get_current( $forced_id = null ) {
		$id = $forced_id ? $forced_id : AFSA_Config::get_account_id();
		if ( ! $id ) {
			return null;
		}

		if ( empty( $this->accounts[ $id ] ) ) {
			$this->accounts[ $id ] = new AFSA_Account( $id );
		}

		return $this->accounts[ $id ];
	}

	public function set_current_id( $id ) {
		$key                = AFSA_SETTINGS_PREFIX . 'main';
		$data               = get_option( $key, AFSA_Settings::manager()->get_default_for( 'main' ) );
		$data['account_id'] = $id;
		update_option( $key, $data );
	}

	public function set_current( $id ) {
		if ( ! $this->validate_id( $id ) ) {
			return null;
		}
		$this->set_current_id( $id );

		return $this->get_current();
	}


	public function get_account_creation_params( $return_url = null ) {
		$ret = AFSA_Config::get_infos_manager()->site()->get();

		if ( AFSA_Config::get_paa_rc() ) {
			$ret['paa_rc'] = AFSA_Config::get_paa_rc();
		}

		$ret['return_url'] = $return_url ?
				$return_url :
				AFSA_Config::get_account_manager_url();

		$state = base64_encode( random_bytes( 32 ) );
		AFSA_Config::save_request_state( $state );
		$ret['state'] = $state;

		return $ret;
	}

}
