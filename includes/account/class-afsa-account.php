<?php

defined( 'ABSPATH' ) || exit;

define( 'AFSA_ACCOUNTS_INFO', 'afsa_accounts' );

class AFSA_Account {

	private $id;

	public function __construct( $id = null ) {

		$this->set_id( $id );
	}

	public function get_id() {
		return $this->id;
	}

	public function set_id( $id ) {
		$this->id = $id ? $id : AFSA_Config::get_account_id();
		return $this->load();
	}

	public function validate() {
		return AFSA_Config::validate_account_id( $this->id );
	}

	public function advanced_ecom_enabled() {
		return $this->ecom_enabled();
	}

	public function ecom_enabled() {
		$id = $this->get_plan_id();
		return $id == 4 || $id == 2;
	}

	public function get_plan_id() {
		return (int) $this->get( 'plan', 0 );
	}

	public function is_free() {
		return $this->get_plan_id() < 1;
	}

	// DATA STORE


	public function set( $mixed, $value = null ) {
		$arr = array();

		is_array( $mixed ) ?
						$arr           = $mixed :
						$arr[ $mixed ] = $value;

		foreach ( $arr as $k => $v ) {
			$this->infos[ $k ] = $v;
		}

		return $this;
	}

	public function get( $key, $default = null ) {
		return empty( $this->infos[ $key ] ) ?
				$default :
				$this->infos[ $key ];
	}

	public function load() {
		$this->infos = array();

		if ( $this->validate() ) {
			$store = get_option( AFSA_ACCOUNTS_INFO, array() );
			if ( ! empty( $store[ $this->id ] ) ) {
				$this->infos = $store[ $this->id ];
			}
		}

		return $this;
	}

	public function save() {
		if ( ! $this->validate() ) {
			return;
		}
		$store              = get_option( AFSA_ACCOUNTS_INFO, array() );
		$store[ $this->id ] = $this->infos;
		update_option( AFSA_ACCOUNTS_INFO, $store );
		return $this;
	}

	public function dump() {
		AFSA_Tools::dump( $this->infos, true );
		AFSA_Tools::dump( $this->trial_infos(), true );
	}

	// TRIAL RELATED


	public function is_trial() {
		return ! empty( $this->infos['trial'] );
	}

	private function days_until( $date_string ) {
		$from = new DateTime();
		$to   = new DateTime( $date_string );
		return abs( $to->diff( $from )->days );
	}

	private function days_since( $date_string ) {
		$to   = new DateTime();
		$from = new DateTime( $date_string );
		return abs( $to->diff( $from )->days );
	}

	public function set_trial( $type, $period = null ) {

		$this->set( 'plan', $type )
				->set( 'trial', 1 );

		if ( ! empty( $period ) ) {
			$date = new DateTime();
			$days = (int) $period;

			$this->set( 'trial_period', $days );
			$this->set( 'start_date', $date->format( 'Y-m-d' ) );
			$date->modify( '+ ' . $days . ' day' );
			$this->set( 'end_date', $date->format( 'Y-m-d' ) );
		}
	}

	public function infos() {
		return array(
			'remaining' => $this->days_until( $this->get( 'end_date' ) ),
			'used'      => $this->days_since( $this->get( 'start_date' ) ),
		);
	}

	// PLAN

	public function update_plan_from_data( $data ) {
		$this->set(
			array(
				'plan'  => $data['id'],
				'name'  => $data['name'],
				'trial' => boolval( $data['trial'] ),
			)
		);

		if ( ! empty( $data['start_date'] ) ) {
			$this->set(
				array(
					'start_date' => $data['start_date'],
					'end_date'   => $data['end_date'],
				)
			);
		}

		$this->save();
	}

	public function plan_infos() {
		$ret = $this->infos;
		// renaming 'plan' property to 'id' as its expected from js
		if ( isset( $ret['plan'] ) ) {
			$ret['id'] = $ret['plan'];
			unset( $ret['plan'] );
		}
		return $ret;
	}

}
