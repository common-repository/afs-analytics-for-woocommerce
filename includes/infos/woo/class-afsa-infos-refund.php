<?php

require_once 'class-afsa-infos-product.php';

// https://docs.woocommerce.com/wc-apidocs/source-class-WC_Order_Refund.html
// WC_Order_Refund extends WC_Abstract_Order




class AFSA_Refund_Infos {

	private $data;
	private $refund;
	private $id = 0;

	public function __construct( $mixed = null ) {
		$this->data = array();

		if ( is_int( $mixed ) ) {
			$this->set_refund_by_id( $mixed );
		} else {
			$this->set_refund( $mixed );
		}
	}

	public function validate() {
		return true;
	}

	public function set_refund( $o ) {
		$this->refund = $o;
	}

	public function set_refund_by_id( $id ) {
		if ( $id ) {
			$this->refund = new WC_Order_Refund( $id );
			$this->id     = $id;
		}
	}

	public function set_id( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * return collected data
	 *
	 * @return array
	 */
	public function get() {
		return $this->data;
	}

	public function parse( $format = AFSA_FORMAT_TRANSACTION_ITEM ) {
		if ( ! $this->validate() ) {
			return false;
		}

		$o = $this->refund;

		$refund_id = $this->id ?:
				$o->get_refund_number();

		$index = 0;
		foreach ( $o->get_items() as $item ) {
			$product = $item->get_product();

			$quantity = $item['quantity'];
			if ( $quantity ) {

				$data = $item->get_data();
				if ( empty( $data['currency'] ) ) {
					$data['currency'] = $o->get_currency();
				}

				$p_info                   = new AFSA_Product_Infos();
				$p_info->refund_reference = $refund_id;

				$data['position']      = $index++;
				$data['format']        = $format;
				$this->data['items'][] = $p_info->parse( $product, $data );
			}
		}

		return $this->data;
	}

}
