<?php

require_once 'class-afsa-infos-cart-item.php';

/*
 * https://docs.woocommerce.com/wc-apidocs/source-class-WC_Cart.html
 */

use AFSA_Cart_Item_Infos as item;

class AFSA_Cart_Infos {

	public $items;

	public function __construct( $cart = null ) {

		$this->set_cart( $cart ?: $this->get_global_cart() );
	}

	public function get_global_cart() {
		global $woocommerce;
		return $woocommerce->cart;
	}

	public function set_cart( $cart ) {
		$this->cart  = $cart;
		$this->items = method_exists( $cart, 'get_cart_contents' ) ?
				$cart->get_cart() :
				$cart->cart_contents;
	}

	public function get_item_keys() {
		return empty( $this->items ) ?
				null :
				array_keys( $this->items );
	}

	public function get_item( $key ) {
		return empty( $this->items[ $key ] ) ?
				null :
				new item( $this, $key );
	}

	public function get_current_quantity_for_item_key( $key ) {
		return empty( $this->items[ $key ] ) ?
				null :
				$this->items[ $key ] ['quantity'];
	}


}
