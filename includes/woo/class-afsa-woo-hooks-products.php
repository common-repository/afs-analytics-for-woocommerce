<?php

class AFSA_WOO_Hooks_Products {

	use AFSA_WOO_Hooks_Utils;

	static $product_count   = 0;
	static $listed_products = null;
	static $current_list    = null;

	public static function init() {

		static::register_action_hooks(
			array(
				// Impressions
				'woocommerce_before_shop_loop',
				'woocommerce_before_shop_loop_item',
				'woocommerce_after_shop_loop',
				// 'woocommerce_product_loop_end',
				//
				'woocommerce_after_template_part,0,4',
				// Detailed
				'woocommerce_before_single_product',
				'woocommerce_after_single_product',
				'wc_quick_view_before_single_product',
				//
				// Set current list from shortcode hooks
				'woocommerce_shortcode_before_recent_products_loop',
				'woocommerce_shortcode_before_sale_products_loop',
				'woocommerce_shortcode_before_best_selling_products_loop',
				'woocommerce_shortcode_before_top_rated_products_loop',
				'woocommerce_shortcode_before_featured_products_loop',
				'woocommerce_shortcode_before_related_products_loop',
				'woocommerce_cart_collaterals',
			)
		);
	}

	// PRODUCTS IMPRESSSIONS, categories, search, etc.


	private static function set_current_list( $list ) {
		if ( $list ) {
			static::$current_list = $list;
			AFSA_Tracker::get()->set_last_product_list( $list );
		}
	}

	private static function render_products_impressions() {
		if ( ! empty( static::$listed_products ) ) {
			AFSA_Tracker::get()->render_products_impression( static::$listed_products );
			static::$listed_products = array();
			static::$current_list    = null;
			return true;
		}
		return false;
	}

	public static function on_woocommerce_before_shop_loop() {
		static::$listed_products = array();

		$list = null;
		if ( is_search() ) {
			$list = __( 'search', 'afsanalytics' );
		} elseif ( is_product_category() ) {
			$list = __( 'product categorie', 'afsanalytics' );
		} elseif ( is_front_page() ) {
			$list = __( 'front page', 'afsanalytics' );
		} elseif ( is_shop() ) {
			$list = __( 'shop index', 'afsanalytics' );
		}

		static::set_current_list( $list );
	}

	public static function on_woocommerce_before_shop_loop_item() {
		global $product;
		if ( $product ) {
			static::$listed_products[] = $product;
		}
	}

	public static function on_woocommerce_after_shop_loop() {
		static::render_products_impressions();
	}

	// Setting current list from shortcode hooks

	public static function on_woocommerce_shortcode_before_recent_products_loop() {
		static::set_current_list( 'recent' );
	}

	public static function on_woocommerce_shortcode_before_sale_products_loop() {
		static::set_current_list( 'sale' );
	}

	public static function on_woocommerce_shortcode_before_best_selling_products_loop() {
		static::set_current_list( 'best selling' );
	}

	public static function on_woocommerce_shortcode_before_top_rated_products_loop() {
		static::set_current_list( 'top rated' );
	}

	public static function on_woocommerce_shortcode_before_featured_products_loop() {
		static::set_current_list( 'featured' );
	}

	public static function on_woocommerce_shortcode_before_related_products_loop() {
		static::set_current_list( 'related' );
	}

	public static function on_woocommerce_cart_collaterals() {
		static::set_current_list( 'cross sell' );
	}

	/*
	  //    public static function on_woocommerce_product_loop_end() {
	  //        unused ATM;
	  //    }
	 */

	// Finding what kind of product list is displayed
	// from template part name
	// and rendering impressions if product not empty
	public static function on_woocommerce_after_template_part( $template_name, $template_path, $located, $args ) {

		if ( empty( static::$listed_products ) ) {
			return;
		}

		$arr = explode( '/', $template_name );
		if ( $arr[0] === 'loop' || count( $arr ) < 2 ) {
			return;
		}

		if ( ! static::$current_list ) {
			$part = $arr[1];
			if ( stripos( $part, 'related' ) !== false ) {
				AFSA_Tracker::get()->set_last_product_list( 'related' );
			}
		}

		return static::render_products_impressions();
	}

	// SINGLE PRODUCT Detailed view.







	public static function on_woocommerce_before_single_product() {
		global $product;
		if ( $product ) {
			AFSA_Tracker::get()
					->set_last_product_list( __( 'product detail', 'afsanalytics' ) )
					->render_product_detail_view( $product );
		}
	}

	// Bottom of detailed view
	public static function on_woocommerce_after_single_product() {
		// AFSA_Tracker::get()->render_bottom_js();
	}

}
