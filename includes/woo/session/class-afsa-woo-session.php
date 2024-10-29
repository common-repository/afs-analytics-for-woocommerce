<?php

define( 'AFSA_WOO_CART_SESSION_KEY', '_AFSA_cart' );


require_once 'class-afsa-woo-session-item.php';

use AFSA_Woo_Session_Cart_Item as item;

class AFSA_Woo_Session {

	private static $instance   = null;
	private $items             = array();
	private $loaded            = false;
	private $checkout_out_step = array(
		'registered' => 0,
		'current'    => 0,
	);

	static function get() {

		return static::$instance ?: static::$instance = new static();
	}

	public function __construct() {
		$this->load();
	}

	public function get_item( $item_key, $WC_cart = null ) {
		return empty( $this->items[ $item_key ] ) ?
				$this->items[ $item_key ] = new item( $item_key, $WC_cart ) :
				$this->items[ $item_key ];
	}

	// PRODUCT in cart Quantity update


	public function on_add_to_cart( $item_key, $quantity ) {
		$this->get_item( $item_key, null )->on_item_added( $quantity );
		$this->save();
	}

	public function on_before_product_removed( $item_key, $WC_cart = null ) {
		$item = $this->get_item( $item_key, $WC_cart ); // keep a ref to the soon to be removed item
		$this->save();
	}

	public function on_product_removed( $item_key, $WC_cart = null ) {
		$this->update_product_quantity( $item_key, $WC_cart, 0 );
		$this->save();
	}

	public function on_product_restored( $item_key, $WC_cart = null ) {
		$this->get_item( $item_key, $WC_cart )->on_item_restored();
		$this->save();
	}

	public function on_update_quantity( $item_key, $WC_cart, $quantity ) {
		$this->update_product_quantity( $item_key, $WC_cart, $quantity );
		$this->save();
	}

	private function update_product_quantity( $item_key, $WC_cart, $quantity ) {

		if ( ! empty( $item_key ) ) {
			$this->get_item( $item_key, $WC_cart )->set_quantity( $quantity );
		}
	}

	private function item_from_array( $item_key, $data ) {
		if ( ! empty( $item_key ) ) {
			$this->get_item( $item_key )->from_array( $data );
		}
	}

	// LOAD // SAVE


	public function to_array() {
		$data = array();
		foreach ( $this->items as $id => $object ) {
			if ( ! $object->is_empty() ) {
				$data[ $id ] = $object->to_array();
			}
		}
		return array(
			'items'    => $data,
			'checkout' => $this->checkout_out_step,
		);
	}

	public function dump() {
		AFSA_Tools::dump( $this->to_array() );
	}

	public function load() {

		if ( ! WC()->session ) {
			AFSA_Tools::log( __METHOD__ . ' empty session' );
			return;
		}

		if ( $this->loaded ) {
			return;
		}

		$this->loaded = true;
		$data         = WC()->session->get( AFSA_WOO_CART_SESSION_KEY );

		$key = 'items';
		if ( ! empty( $data[ $key ] ) ) {
			foreach ( $data[ $key ] as $item_key => $item ) {
				$this->item_from_array( $item_key, $item );
			}
		}

		if ( ! empty( $data['checkout'] ) ) {
			$this->checkout_out_step = $data['checkout'];
		}

		/*
		  AFSA_Tools::log(
		  json_encode(
		  array(
		  'key'   => is_array( $data ) ? array_keys( $data ) : $data,
		  'data'  => $data['checkout'],
		  'steps' => $this->checkout_out_step,
		  ),
		  JSON_PRETTY_PRINT
		  )
		  ); */
	}

	public function save() {

		if ( ! WC()->session ) {
			AFSA_Tools::log( __METHOD__ . ' empty session' );
			return;
		}

		WC()->session->set(
			AFSA_WOO_CART_SESSION_KEY,
			$this->to_array()
		);
	}

	public function clear() {
		WC()->session->set(
			AFSA_WOO_CART_SESSION_KEY,
			array()
		);
	}

	// Tracker data rendering

	public function on_before_bottom_js_rendered() {

		$tracker = AFSA_Tracker::get();

		$products = array();
		foreach ( $this->items as $id => $object ) {
			$product = $object->get_product();
			if ( $product ) {
				$products[] = $product;
			}

			$tracker_data = $object->render_tracker_data();

			if ( ! $tracker_data ) {
				AFSA_Tools::log( __METHOD__ . ' no data' );
				continue;
			}

			$tracker_data['quantity'] > 0 ?
							$tracker->render_add_to_cart( $tracker_data ) :
							$tracker->render_remove_from_cart( $tracker_data );

			$object->on_rendered_tracker_data();
		}

		// Checkouts
		$this->render_checkout_step( $tracker, $products );

		$this->save();
	}

	// CHECKOUTS

	public function set_checkout_step( $step_number ) {

		if ( $this->checkout_out_step['registered'] !== (int) $step_number ) {
			$this->checkout_out_step['current'] = (int) $step_number;
		}
		return $this;
	}

	public function render_checkout_step( $tracker, $products ) {
		$step = &$this->checkout_out_step;

		if ( (int) $step['current'] > (int) $step['registered'] ) {
			for (
			$s = (int) $step['registered'] + 1; // ignoring 'current'
					$s <= (int) $step['current']; $s++ ) {

				$tracker->render_checkout_step( $s, $products );
			}
		}

		$this->checkout_out_step['registered'] = $step['current'];

		return $this;
	}

}
