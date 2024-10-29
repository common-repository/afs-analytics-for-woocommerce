<?php

require_once 'class-afsa-infos-product.php';

// https://docs.woocommerce.com/wc-apidocs/source-class-WC_Order.html

define( 'AFSA_ORDER_STATE_UNKNOWN', 'unknown' );
define( 'AFSA_ORDER_STATE_CANCELLED', 'cancelled' );
define( 'AFSA_ORDER_STATE_PENDING', 'pending' );
define( 'AFSA_ORDER_STATE_COMPLETED', 'completed' );

class AFSA_Order_Infos {

	private $data;
	private $order;

	public function __construct( $mixed = null ) {
		$this->data = array();

		if ( is_int( $mixed ) ) {
			$this->set_order_by_id( $mixed );
		} else {
			$this->set_order( $mixed );
		}
	}

	public function validate() {
		return true;
	}

	public function set_order( $o ) {

		$this->order = $o;
	}

	public function get_order() {
		return $this->order;
	}

	public function set_order_by_id( $id ) {
		if ( $id ) {
			$this->order = wc_get_order( $id );
		}
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

		$o = $this->order;

		$state = $this->parse_order_state( $o );
		if ( $state === AFSA_ORDER_STATE_CANCELLED ) {
			return false;
		}

		$total_ttc    = $o->get_total();
		$revenue_tax  = (float) $o->get_total_tax();
		$shipping_tax = (float) $o->get_shipping_tax();

		$order_id = $o->get_order_number();

		$data = array(
			'id'          => $order_id,
			'affiliation' => AFSA_Config::get_shop_affiliation(),
			'revenue'     => $total_ttc, // TTC.
			'revenue_net' => $total_ttc - $revenue_tax, // H.T.
			'revenue_tax' => $revenue_tax,
			'tax'         => $revenue_tax + $shipping_tax,
			'customer'    => $o->get_customer_id(),
			'currency'    => $o->get_currency(),
			'state'       => $state,
		);

		$data['shipping']     = $o->get_shipping_total() + $shipping_tax; // TTC
		$data['shipping_tax'] = $shipping_tax; // TTC
		$data['shipping_net'] = $o->get_shipping_total(); // HT

		$this->data['order'] = $data;

		$this->data['items'] = array();
		$index               = 0;
		foreach ( $o->get_items() as $item ) {
			$product = $item->get_product();

			if ( $product->get_type() === 'variation' ) {
				$variation      = $product;
				$product        = wc_get_product( $variation->get_parent_id() );
				$variation_id   = $product->get_id();
				$variation_name = $variation->get_name();

								 $sku = $variation->get_sku();
				if ( AFSA_Config::use_variation_sku() && ! empty( $sku ) ) {
					$variation_name = $sku;
				}
			} else {
				$variation_id = null;
			}

			$quantity = $item['quantity'];
			if ( $quantity ) {

				$data = $item->get_data();
				if ( empty( $data['currency'] ) ) {
					$data['currency'] = $o->get_currency();
				}

				$p_info                  = new AFSA_Product_Infos();
				$p_info->order_reference = $order_id;

				$data['position'] = $index++;
				$data['format']   = AFSA_FORMAT_TRANSACTION_ITEM;
				if ( ! empty( $variation_id ) ) {
					$data['variation_name'] = $variation_name;
				}

				$this->data['items'][] = $p_info->parse( $product, $data );
			}
		}

		// COUPON

		try {
			$coupons = $this->parse_coupons( $o );
			if ( $coupons ) {
				$this->data['order']['coupon'] = $coupons;
			}
		} catch ( Exception $ex ) {

		}

		return $this->data;
	}

	// hook:  woocommerce_order_status_changed
	//
	// (order)  do_action( 'woocommerce_order_status_changed',
	// $this_get_id,
	// $this_status_transition_from,
	// $this_status_transition_to,
	// $instance );
	//
	// add_action( 'woocommerce_order_status_changed', 'action_woocommerce_order_status_changed', 10, 4 );

	public function parse_order_state( $o ) {

		if ( $o->is_paid() ) {
			return AFSA_ORDER_STATE_COMPLETED;
		}

		switch ( $o->get_status() ) {

			case 'completed':
				return AFSA_ORDER_STATE_COMPLETED;

			case 'failed': // canceled
			case 'cancelled': // refund
			case 'refunded': // payment error
				return AFSA_ORDER_STATE_CANCELLED;

			case 'pending':
			case 'on-hold':
			default:
				return AFSA_ORDER_STATE_PENDING;
		}
	}

	public function parse_coupons( $order ) {

		$coupons = AFSA_Config::is_woo_version( '3.7.0' ) ?
				$order->get_coupon_codes() :
				$order->get_used_coupons();

		return empty( $coupons ) ?
				null :
				implode( ',', $coupons );
	}

	// Payment Method

	public function get_payment_method() {

		if ( method_exists( $this->order, 'get_payment_method' ) ) {
			return $this->order->get_payment_method();
		}

		if ( ! empty( $this->order->payment_method ) ) {
			return $this->order->payment_method;
		}

		return null;
	}

	public function get_payment_method_title() {

		if ( method_exists( $this->order, 'get_payment_method_title' ) ) {
			return $this->order->get_payment_method_title();
		}

		if ( ! empty( $this->order->payment_method_title ) ) {
			return $this->order->payment_method_title;
		}

		return null;
	}

	public function get_payment_method_infos() {
		$code = $this->get_payment_method();
		if ( ! $code ) {
			return null;
		}
		$title = $this->get_payment_method_title();
		$ret   = array( 'code' => $code );

		if ( $title ) {
			$ret['label'] = $title;
		}

		return $ret;
	}

	// REFUNDS



	public function parse_refunded_items( $refund_id = 0 ) {

		$refund_was_found = false;

		$order_refunds = $this->order->get_refunds();
		if ( empty( $order_refunds ) ) {
			return null;
		}

		// finding last refund id (greater one)
		if ( ! $refund_id ) {
			foreach ( $order_refunds as $refund ) {
				$refund_id = max( $refund_id, $refund->get_id() );
			}
		}

		/*
		 Assuming that all previous refunds have been tracked
		 * we only need to send last refund infos
		 *
		 * we iterate over previous refunds to calc
		 * previous refunded quantity by product
		 * in order to only send the difference for the last refunded products
		 * (in order to handle a same product being refunded not at once)
		 *
		 */

		$history = array();

		// var_dump($order_refunds);

		$items = array();
		foreach ( $order_refunds as $refund ) {

			// ignore refunds made after the one we parsing
			if ( $refund->get_id() > $refund_id ) {
				continue;
			}

			$is_current_refund = $refund->get_id() === $refund_id;

			// Loop through the order refund line items
			foreach ( $refund->get_items() as $item_id => $item ) {

				$refunded_quantity = abs( $item->get_quantity() ); // Quantity: zero or negative integer
				$product           = $item->get_product();
				$sku               = $product->get_sku();

				$product_id = $product->get_id();

				if ( empty( $sku ) ) {
					$sku = (string) $product_id;
				}

				if ( $is_current_refund ) {
					$refund_was_found = true;

					$data              = $item->get_data();
					$p_info            = new AFSA_Product_Infos();
					$data['format']    = AFSA_FORMAT_TRANSACTION_ITEM;
					$infos             = $p_info->parse( $product, $data );
					$infos['id']       = $product_id;
					$infos['quantity'] = $refunded_quantity * 5;
					$items[]           = $infos;
				}
				// building history ( targeting only previous refunds )
				elseif ( $refund->get_id() < $refund_id ) {
					empty( $history['sku'] ) ?
									$history['sku']  = $refunded_quantity :
									$history['sku'] += $refunded_quantity;
				}
			}
		}

		if ( ! $refund_was_found ) {
			return null;
		}

		// update quantity (last_refund quantity - previous refunded quantiry )
		$updated_items = array();
		foreach ( $items as $item ) {
			$sku = $item['sku'];

			if ( ! empty( $history[ $sku ] ) ) {
				$item['quantity'] -= $history[ $sku ];
			}
			$updated_items[] = $item;
		}

		return $updated_items;
	}

}
