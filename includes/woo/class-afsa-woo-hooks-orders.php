<?php

class AFSA_WOO_Hooks_Orders {

	use AFSA_WOO_Hooks_Utils;

	public static function init() {

		static::register_action_hooks(
			array(
				'woocommerce_thankyou,10,1',
				'woocommerce_order_refunded,10,2',
				'woocommerce_order_status_changed,10,4',
			)
		);
	}

	public static function on_woocommerce_thankyou( $order_id ) {

		AFSA_Tools::log( '[WOO] HOOK on_woocommerce_thankyou ' );

		$db = AFSA_DB::get();

		// ignoring already processed order
		if ( $db->was_order_processed( $order_id ) ) {
			AFSA_Tools::log( '[WOO] HOOK on_woocommerce_thankyou ignoring already processed order ' . $order_id );
			return;
		}

		$db->save_processed_order( $order_id );

		AFSA_Tracker::get()->render_order_info( $order_id );

		$db->clean_processed_order_table();
	}

	public static function on_woocommerce_order_refunded( $order_id, $refund_id ) {
		AFSA_Config::set_refunded_order( $order_id, $refund_id );
	}

	public static function on_woocommerce_order_status_changed( $order_id, $status_from, $status_to, $order ) {

		if ( ! in_array( $status_from, array( 'pending', 'on-hold' ) ) ) {
			return;
		}

		AFSA_Tools::log(
			__METHOD__ . ' updated status from [' . $status_from
				. '] TO [' . $status_to . '] for order ' . $order_id
		);
		switch ( $status_to ) {
			case 'completed':
				AFSA_Config::set_updated_order_status( $order_id, 'completed' );
		}
	}

}
