<?php

define( 'AFSA_FORMAT_COMPACT', 'compact' );
define( 'AFSA_FORMAT_TRANSACTION_ITEM', 'transaction' );
define( 'AFSA_FORMAT_IMPRESSION', 'impression' );
define( 'AFSA_FORMAT_PRODUCT', 'product' );

require_once 'class-afsa-infos-cat.php';

/*
 * Brands
 *
 * https://stackoverflow.com/questions/56276674/how-to-get-the-brand-name-of-product-in-woocommerce
 *
 *
 */

class AFSA_Product_Infos {

	public static $position = 1;
	public $order_reference;
	public $add_url = false;
	public $infos; // extras infos from cart

	public function __construct() {

	}

	public function get_by_id( $id ) {
		$pf = new WC_Product_Factory();
		return $pf->get_product( $id );
	}

	/**
	 * Add extra product infos retrieved
	 * from cart / order / or other sources
	 * used when parsing product
	 *
	 * @param type $data info array
	 */
	public function register_extra_data( $data ) {
		$this->infos = $data;
	}

	public function parse_multiple( $products, $params ) {
		$ret = array();

		static::$position = 1;
		foreach ( $products as $product ) {
			$ret[] = $this->parse( $product, $params );
		}
		return $ret;
	}

	/**
	 * Main Parse function
	 *
	 * @param object $o WC_product
	 * @param array  $p optional product infos
	 * @param int    $position
	 * @param string $format item format (transcation, cart list, etc...)
	 *
	 * @return array
	 */
	public function parse( $o, $params = array() ) {

		$p = $params;

		$format = empty( $p['format'] ) ?
				AFSA_FORMAT_PRODUCT :
				$p['format'];

		$position = empty( $p['position'] ) ?
				0 :
				(int) $p['position'];

		if ( ! $o ) {
			return null;
		}

		$qty = 1;
		if ( isset( $p['quantity'] ) ) {
			$qty = $p['quantity'];
		}

		$product_name = AFSA_Tools::normalize_string( $o->get_name() );

		$sku          = $this->get_sku( $o );
		$product_id   = $o->get_ID();
		$product_type = $o->get_type();

		$id = empty( $sku ) ?
				(string) $product_id :
				$sku;

		$full_cat_name = esc_js( AFSA_Category_Infos::get_name_from_product( $o ) );

		$price = isset( $p['total'] ) ?
				(float) $p['total'] / $qty :
				$o->get_price();

		$variant = $this->parse_variation( $p );

		if ( empty( $position ) ) {
			$position = static::$position++;
		}

		$list = empty( $p['list'] ) ? null : $p['list'];

		try {
			$brand = $this->get_brand_name( $product_id );
		} catch ( \Exception $e ) {
			$brand = null;
		}

		// RENDERING
		// COMPACT DATA - Only add basic Product Infos (id, name)

		if ( $format == AFSA_FORMAT_COMPACT ) {
			return array(
				'id'   => $id,
				'name' => $product_name,
			);
		}

		// TRANSACTION ITEM DATA

		if ( $format == AFSA_FORMAT_TRANSACTION_ITEM ) {
			$ret = array(
				'id'       => $id,
				'name'     => $product_name,
				'category' => $full_cat_name,
				'price'    => $price,
				'quantity' => "$qty",
			);

			$op_fields = array( 'variant', 'brand' );
		}

		// IMPRESSION DATA

		if ( $format == AFSA_FORMAT_IMPRESSION ) {
			$ret       = array(
				'id'       => $id,
				'name'     => $product_name,
				'list'     => $list,
				'category' => $full_cat_name,
				'position' => "$position",
				'price'    => $price,
			);
			$op_fields = array( 'variant', 'position', 'brand' );
		}

		// PRODUCT DATA

		if ( $format == AFSA_FORMAT_PRODUCT ) {
			$ret = array(
				'id'       => $id,
				'name'     => $product_name,
				'category' => $full_cat_name,
				'price'    => $price,
				'quantity' => "$qty",
			);

			$op_fields = array( 'variant', 'coupon', 'position', 'brand' );
		}

		if ( ! empty( $op_fields ) ) {
			foreach ( $op_fields as $field ) {
				switch ( $field ) {
					case 'position':
						if ( $position != -1 ) {
							$ret['position'] = "$position";
						}
						break;
					default:
						if ( ! empty( ${$field} ) ) {
							$ret[ $field ] = ${$field};
						}
				}
			}
		}

		// Product URL

		if ( $this->add_url && ! empty( $p['link'] ) ) {
			$ret['url'] = urlencode( get_permalink( $product_id ) );
		}

		foreach ( array( 'brand' ) as $field ) {
			if ( empty( $ret[ $field ] ) ) {
				unset( $ret[ $field ] );
			}
		}

		return $ret;
	}

	// SKU

	public function get_sku( $product ) {
		return $sku = $product->get_sku();
	}

	// BRAND

	public function get_brand_name( $product_id ) {

		foreach (
		array(
			'product_brand', // woocommerce brands
			'pwb-brand', // Perfect Brands Woocommerce
			'yith_product_brand', // YITH WooCommerce Brands plugin
			'pa_brand',
		) as $taxonomy ) {

			$brand_names = /* wp_get_post_terms */get_the_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );

			if ( is_array( $brand_names ) && ! empty( $brand_names ) ) {
				$names = array();
				foreach ( $brand_names as $wp_term ) {
					$names[] = $wp_term->name;
				}

				return implode( ', ', $names );
			}
		}

		return null;
	}

	// VARIATION

	private function parse_variation( $p ) {

		if ( ! empty( $p['variation_name'] ) ) {
			return $p['variation_name'];
		}

		if ( ! empty( $p['variation_id'] ) ) {
			return $this->get_variation_name( $p['variation_id'] );
		}

		return empty( $p['variant'] ) ?
				null :
				$p['variant'];
	}

	public function get_variation_name( $variation_id ) {

		$variation = wc_get_product( $variation_id );

				$sku = $variation->get_sku();
		if ( AFSA_Config::use_variation_sku() && ! empty( $sku ) ) {
			return $sku;
		}

		return $variation->get_name();
	}

	// CONTEXT

	public static function get_ajax_context_info( $product ) {

		// https://stackoverflow.com/questions/32837968/how-to-get-featured-image-of-a-product-in-woocommerce
		$img = wp_get_attachment_url( $product->get_image_id() );

		return array(
			'url'   => get_permalink( $product->get_id() ),
			'image' => array(
				'default' => $img,
			),
			'name'  => AFSA_Tools::normalize_string( $product->get_name() ),
		);
	}

}
