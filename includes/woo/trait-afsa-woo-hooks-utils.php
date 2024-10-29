<?php

trait AFSA_WOO_Hooks_Utils {

	public static function register_action_hooks( $hook_liste, $class_name = null ) {

		foreach (
		$hook_liste
		as $hook_data ) {
			$p        = explode( ',', $hook_data );
			$hook     = $p[0];
			$priority = isset( $p[1] ) ? $p[1] : 10;
			$argc     = isset( $p[2] ) ? $p[2] : 1;

			add_action(
				$hook,
				array(
					$class_name ?: __CLASS__,
					'on_' . $hook,
				),
				$priority ?: 10,
				$argc ?: 1
			);
		}
	}

	public static function register_filter_hooks( $hook_liste, $class_name = null ) {

		foreach (
		$hook_liste
		as $hook_data ) {
			$p        = explode( ',', $hook_data );
			$hook     = $p[0];
			$priority = isset( $p[1] ) ? $p[1] : 10;
			$argc     = isset( $p[2] ) ? $p[2] : 1;

			add_filter(
				$hook,
				array(
					$class_name ?: __CLASS__,
					'on_' . $hook,
				),
				$priority ?: 10,
				$argc ?: 1
			);
		}
	}


	public static function log( $params ) {

		if ( ! AFSA_Config::is_debug() ) {
			return;
		}

		global $product;

		$p = is_string( $params ) ? array( $params ) : $params;

		if ( 0 ) {
			$p['page_infos'] = array(
				'product' => ( empty( $product ) ? 'non' : $product->get_name() ),
				'pcat'    => ( is_product_category() ? '1' : '0' ),
				'tag'     => ( is_product_tag() ? '1' : '0' ),
				'front'   => ( is_front_page() ? '1' : '0' ),
				'shop'    => ( is_shop() ? '1' : '0' ),
			);
		}

		$str = json_encode( $p, JSON_PRETTY_PRINT );

		if ( ! AFSA_Config::is_ajax() ) {
			print '<pre>' . $str . '</pre>';
		}
	}

}
