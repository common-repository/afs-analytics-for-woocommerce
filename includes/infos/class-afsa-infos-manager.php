<?php

require_once 'class-afsa-infos.php';
require_once 'class-afsa-infos-visitor.php';

require_once 'class-afsa-infos-site.php';
require_once 'class-afsa-infos-page.php';

require_once 'woo/class-afsa-infos-product.php';
require_once 'woo/class-afsa-infos-cart.php';
require_once 'woo/class-afsa-infos-order.php';
require_once 'woo/class-afsa-infos-refund.php';
require_once 'woo/class-afsa-infos-customer.php';



class AFSA_Infos_Manager {

	static $instance = null;

		static $info_classes = array(
			'visitor'  => 'AFSA_Visitor_Infos',
			'customer' => 'AFSA_Customer_Infos',
			'site'     => 'AFSA_Site_Infos',
			'page'     => 'AFSA_Page_Infos',
			'product'  => 'AFSA_Product_Infos',
			'cart'     => 'AFSA_Cart_Infos',
			'refund'   => 'AFSA_Refund_Infos',
		);



		private $providers = array();

		public static function get() {
			return static::$instance ?: static::$instance = new static();
		}

		public function visitor() {
			return $this->getProvider( 'visitor' );
		}

		public function customer() {
			return $this->getProvider( 'customer' );
		}

		public function site() {
			return $this->getProvider( 'site' );
		}

		public function page() {
			return $this->getProvider( 'page' );
		}

		public function product() {
			return $this->getProvider( 'product' );
		}

		public function cart() {
			return $this->getProvider( 'cart' );
		}

		public function refund() {
			return $this->getProvider( 'refund' );
		}

		private function getProvider( $type, $params = null ) {

			if ( empty( $this->providers[ $type ] ) ) {
				if ( ! empty( static::$info_classes[ $type ] ) ) {
					$class                    = static::$info_classes[ $type ];
					$this->providers[ $type ] = new $class( $params );
				}
			}

			return $this->providers[ $type ] ?:
				null;
		}

}
