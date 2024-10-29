<?php

require_once AFSA_INCLUDES_DIR . '/defines.php';

class AFSA_WOO_Hooks_Checkout {

	use AFSA_WOO_Hooks_Utils;

	public static function init() {

		static::register_action_hooks(
			array(
				'woocommerce_before_cart',
				'woocommerce_before_checkout_billing_form',
			)
		);

	}

	public static function on_woocommerce_before_cart() {
		AFSA_Woo_Session::get()->set_checkout_step( AFSA_TRACKER_CHEKOUT_STEP_VIEW_CART );
	}

	public static function on_woocommerce_before_checkout_billing_form() {
		AFSA_Woo_Session::get()->set_checkout_step( AFSA_TRACKER_CHEKOUT_STEP_ORDER_FORM );
	}

	public static function on_woocommerce_checkout_fields( $fields ) {
		$fields['billing']['my_billing_infos'] = array(
			'type'        => 'textarea',
			'label'       => __( 'Notes de la commande', 'woocommerce' ),
			'placeholder' => __( 'Commentaires concernant votre commande', 'placeholder', 'woocommerce' ),
			'required'    => false,
			'class'       => array( 'form-row-wide' ),
			'clear'       => true,
		);

		return $fields;
	}

}
