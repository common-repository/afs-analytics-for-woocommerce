<?php

require 'trait-afsa-woo-hooks-utils.php';
require_once AFSA_INCLUDES_DIR . '/class-afsa-db.php';


require 'session/class-afsa-woo-session.php';

require 'class-afsa-woo-hooks-carts.php';
require 'class-afsa-woo-hooks-products.php';
require 'class-afsa-woo-hooks-checkout.php';
require 'class-afsa-woo-hooks-orders.php';

class AFSA_WOO_Hooks {

	use AFSA_WOO_Hooks_Utils;

	public static function init() {

		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		AFSA_WOO_Hooks_Products::init();
		AFSA_WOO_Hooks_Carts::init();
		AFSA_WOO_Hooks_Checkout::init();
		AFSA_WOO_Hooks_Orders::init();

		static::register_action_hooks(
			array(
				'admin_head',
				'wp_admin_enqueue_scripts',
			),
			'AFSA_Tracker'
		);

		static::register_filter_hooks(
			array(
				'woocommerce_get_return_url',
				'afsa_before_bottom_js_rendered',
			)
		);
	}

	public static function on_woocommerce_get_return_url( $url ) {

		return add_query_arg(
			'utm_nooverride',
			'1',
			remove_query_arg( 'utm_nooverride', $url )
		);
	}

	public static function on_afsa_before_bottom_js_rendered() {
		if ( WC()->session ) {
			AFSA_Woo_Session::get()->on_before_bottom_js_rendered();
		}
	}

}
