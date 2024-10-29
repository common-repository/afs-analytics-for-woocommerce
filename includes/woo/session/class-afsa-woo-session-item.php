<?php

class AFSA_Woo_Session_Cart_Item {

	public $tracker_infos        = null;
	private $key                 = null;
	private $registered_quantity = 0;
	private $current_quantity    = 0;
	private $item_infos;

	public function __construct( $item_key, $cart = null ) {

		$this->key = $item_key;

		$this->registered_quantity = 0;
		$this->current_quantity    = 0;

		$cart             = new AFSA_Cart_Infos( $cart );
		$this->item_infos = $cart->get_item( $this->key );
		if ( $this->item_infos ) {
			$this->tracker_infos = $this->item_infos->get_tracker_infos();
		}
	}

	public function get_product() {
		return $this->item_infos ? $this->item_infos->get_product() : null;
	}

	public function get_current_cart_quantity() {
		return $this->item_infos ?
				$this->item_infos->get_current_quantity() :
				0;
	}

	public function on_item_added( $quantity ) {
		$this->set_quantity( $this->get_current_cart_quantity() );
	}

	public function on_item_restored() {
		$this->set_quantity( $this->get_current_cart_quantity() ); // ?
	}

	public function set_quantity( $quantity ) {
		$this->current_quantity = $quantity;
	}

	public function set_registered_quantity( $quantity ) {
		$this->registered_quantity = $quantity;
	}

	public function from_array( $data ) {
		foreach (
		array(
			'registered_quantity',
			'current_quantity',
		) as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$this->$field = $data[ $field ];
			}
		}

		if ( empty( $this->tracker_infos ) && ! empty( $data['infos'] ) ) {
			$this->tracker_infos = $data['infos'];
		}
	}

	public function is_empty() {
		return $this->current_quantity == 0;
	}

	public function to_array() {

		return array(
			'registered_quantity' => $this->registered_quantity,
			'current_quantity'    => $this->current_quantity,
			'change'              => $this->current_quantity - $this->registered_quantity,
			'infos'               => $this->tracker_infos,
		);
	}

	public function render_tracker_data() {
		$change = $this->current_quantity - $this->registered_quantity;
		if ( $change === 0 ) { // no changes , ignore
			return null;
		}

		$ret                  = $this->tracker_infos;
		$ret['quantity']      = $change;
		$ret['cart_quantity'] = $this->current_quantity;

		return $ret;
	}

	public function on_rendered_tracker_data() {
		$this->registered_quantity = $this->current_quantity;
	}

}
