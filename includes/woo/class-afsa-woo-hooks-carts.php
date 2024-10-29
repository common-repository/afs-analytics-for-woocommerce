<?php

class AFSA_WOO_Hooks_Carts {

	use AFSA_WOO_Hooks_Utils;

	public static function init() {

		static::register_action_hooks(
			array(
				'woocommerce_add_to_cart,0,6',
				'woocommerce_remove_cart_item,0,2',
				'woocommerce_cart_item_removed,0,2', // o
				'woocommerce_cart_item_restored,0,2', // o
				'woocommerce_after_cart_item_quantity_update,0,4',
			)
		);
	}

	public static function on_woocommerce_cart_updated() {

	}

	// ADD

	public static function on_woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		AFSA_Woo_Session::get()->on_add_to_cart( $cart_item_key, $quantity );
	}

	// REMOVE

	public static function on_woocommerce_remove_cart_item( $cart_item_key, $WC_cart ) {
		// keeping track of product_id as cart item data will not be available after removal
		AFSA_Woo_Session::get()->on_before_product_removed( $cart_item_key, $WC_cart );
	}

	public static function on_woocommerce_cart_item_removed( $cart_item_key, $WC_cart ) {
		AFSA_Woo_Session::get()->on_product_removed( $cart_item_key, $WC_cart );
	}

	// RESTORE

	public static function on_woocommerce_cart_item_restored( $cart_item_key, $WC_cart ) {
		AFSA_Woo_Session::get()->on_product_restored( $cart_item_key, $WC_cart );
	}

	// QUANTITY UPDATE

	public static function on_woocommerce_after_cart_item_quantity_update( $cart_item_key, $quantity, $old_quantity, $WC_cart ) {
		AFSA_Woo_Session::get()->on_update_quantity( $cart_item_key, $WC_cart, $quantity );
	}

}
