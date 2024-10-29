<?php

require_once AFSA_INCLUDES_DIR . '/infos/class-afsa-infos-visitor.php';

class AFSA_Customer_Infos extends AFSA_Visitor_Infos {

	public function retrieve() {
		// do not remove
	}

	public function parseFromOrder( $order ) {

		// known user
		$customer_id = $order->get_customer_id();

		// Guest user
		$data = $order->get_data();
		if ( ! empty( $data['billing'] ) ) {

			$infos   = &$data['billing'];
			$country = WC()->countries->countries[ $infos['country'] ];

				$customer_data = array(
					'yourid'       => $customer_id,
					'firstname'    => $infos['first_name'],
					'lastname'     => static::render_lastname( $infos['last_name'] ),
					'email'        => static::render_email( $infos['email'] ),
					'company'      => $infos['company'],
					'address'      => $infos['address_1'],
					'addressplus'  => $infos['address_2'],
					'city'         => $infos['city'],
					'state'        => $infos['state'],
					'zipcode'      => $infos['postcode'],
					'country_code' => $infos['country'],
					'country'      => $country,
					'phone'        => static::render_phone( $infos['phone'] ),
				);

				return $this->data = apply_filters( 'afsa_tracker_visitor_infos', $customer_data, null, $this );
		}

		return null;
	}

	public function parse( $customer ) {

		if ( ! $customer ) {
					AFSA_Tools::log( __METHOD__ . ' EMPTY $customer' );
		}

		$o = &$customer;

		if ( ! $this->validate() ) {
			return null;
		}

		$email = $o->get_billing_email();
		if ( empty( $email ) ) {
			$email = $o->get_email();
		}

		$country_code = $o->get_billing_country();
		$country      = WC()->countries->countries[ $country_code ];

		$ret = array(
			'yourid'       => $o->get_id(),
			'firstname'    => $o->get_first_name(),
			'lastname'     => static::render_lastname( $o->get_last_name() ),
			'email'        => static::render_email( $email ),
			'company'      => $o->get_billing_company(),
			'address'      => $o->get_billing_address_1(),
			'addressplus'  => $o->get_billing_address_2(),
			'city'         => $o->get_billing_city(),
			'state'        => $o->get_billing_state(),
			'zipcode'      => $o->get_billing_postcode(),
			'country_code' => $country_code,
			'country'      => $country,
			'phone'        => static::render_phone( $o->get_billing_phone() ),
		);

		return $this->data = $ret;
	}

}
