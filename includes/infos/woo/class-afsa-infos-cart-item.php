<?php

/*
 * https://docs.woocommerce.com/wc-apidocs/class-WC_Product.html
 * https://docs.woocommerce.com/wc-apidocs/class-WC_Product_Variation.html
 */

class AFSA_Cart_Item_Infos {

	private $cart_infos;
	private $key;
	private $product;
	private $variation_id = 0;

	public function __construct( $cart_infos, $key ) {

		$this->cart_infos = $cart_infos;
		$this->key        = $key;
		$data             = $cart_infos->items[ $key ];

		$this->product = wc_get_product( $data['product_id'] );

		if ( ! empty( $data['variation_id'] ) ) {
			$this->variation_id = $data['variation_id'];
		}
	}

	public function get_product() {
		return $this->product;
	}

	public function get_tracker_infos( $format = AFSA_FORMAT_PRODUCT ) {
		$inf    = new AFSA_Product_Infos();
		$params = array( 'format' => $format );

		if ( $this->variation_id ) {
			$params['variation_id'] = $this->variation_id;
		}

		$ret = $inf->parse( $this->product, $params );

		if ( $this->variation_id ) {
			$ret['variant_custom_id'] = $this->variation_id;
		}

		return $ret;
	}

	public function get_current_quantity() {
		return $this->cart_infos->get_current_quantity_for_item_key( $this->key );
	}

}
