<?php

// Renders AFSAnalytics Script

require_once AFSA_INCLUDES_DIR . '/defines.php';

class AFSA_Tracker {

	public $buffer;
	private $account_id;
	private $rendered      = false;
	private $advanced_mode = true;
	private $_log;
	private static $instance = null;
	private $product_list    = null;
	private $banner          = 'Advanced';

	static function get() {
		return static::$instance ?: static::$instance = new static();
	}

	// Action callbacks

	public static function on_enqueue_script() {
		/*
		  Disabled for now */
		/*
		  if ( AFSA_Config::should_track() ) {
		  wp_enqueue_script(
		  'afsa_tracker_local',
		  AFSA_Config::get_url( 'js/afsa.tracker.js' ),
		  array( 'jquery' )
		  );
		  }
		 */
	}

	// Admin header / footer



	/* DEPRECATED ( why is this still called? ) */
	public static function on_admin_head() {
		static::on_admin_header_rendered();
	}

	public static function on_admin_header_rendered() {
		static::on_header_rendered();
	}

	public static function on_admin_footer_rendered() {
		static::on_footer_rendered();
	}

	// Front end header
	public static function on_header_rendered() {
		if ( AFSA_Config::should_track() ) {
			print static::get()->render();
		}
	}

	public static function on_footer_rendered() {
		if ( AFSA_Config::should_track() ) {
			print static::get()->render_bottom_js();
		}
	}

	public function __construct() {
		$this->account_id    = AFSA_Config::get_account_id();
		$this->buffer        = array();
		$this->advanced_mode = AFSA_Config::advanced_ecommerce_enabled();
	}

	public function __destruct() {
		$this->save_log();
	}

	public function set_last_product_list( $name ) {
		$this->product_list = $name;
		return $this;
	}

	public function get_last_product_list() {
		return $this->product_list;
	}

	private function get_infos_manager() {
		return AFSA_Infos_Manager::get();
	}

	private function get_visitor_infos() {
		return $this->get_infos_manager()->visitor();
	}

	private function validate() {
		return ! empty( $this->account_id );
	}

	public function is_advanced_ecommerce_enabled() {
		return $this->advanced_mode;
	}

	private function should_track() {
		return AFSA_Config::should_track() && $this->validate();
	}

	// CODE RENDERING

	private function aa_create( $value ) {
		return 'aa("create", "' . $this->account_id . '", "' . $value . '");';
	}

	private function aa_send( $what ) {
		switch ( $what ) {
			case 'refund':
				return `aa('send', 'event', 'Ecommerce', 'Refund', {'nonInteraction': 1});`;
			case 'checkout':
				return `aa('send', 'event', 'Checkout', 'Option');`;
		}

		return 'aa("send", "' . $what . '");';
	}

	// AA SET

	private function aa_set( $what, $value ) {
		return 'aa("set", "' . $what . '", '
				. ( is_array( $value ) || is_string( $value ) ? json_encode( $value ) : "'$value'" )
				. ');';
	}

	private function aa_set_raw( $what, $value ) {
		return 'aa("set", "' . $what . '", ' . $value . ');';
	}

	public function aa_add_item( $data ) {
		return $this->aa_set( 'addItem', $data );
	}

	public function aa_add_product( $data ) {
		return $this->aa_set( 'addProduct', $data );
	}

	public function aa_add_impression( $data ) {
		return $this->aa_set( 'addImpression', $data );
	}

	public function aa_set_action( $action_name, $data = null ) {
		if ( empty( $data ) ) {
			return $this->aa_set( 'setAction', $action_name );
		}

		return 'aa("set", "setAction", "' . $action_name . '", '
				. ( is_array( $data ) ? json_encode( $data ) : "'$data'" )
				. ');';
	}

	// ADVANCED ECOM

	private function aa_ec_add_impression( $data ) {
		return 'aa("ec:addImpression", '
				. json_encode( $data )
				. ');';
	}

	private function aa_ec_add_product( $data ) {
		return 'aa("ec:addProduct", '
				. json_encode( $data )
				. ');';
	}

	private function aa_ec_action( $action, $param = array() ) {
		$ret = "aa('ec:setAction', '$action'";

		if ( is_array( $param ) ) {
			$param['beacon'] = 'enabled';
		}

		if ( $param ) {
			$ret .= ',' . json_encode( $param );
		}

		return $ret . ');';
	}

	private function aa_set_option( $what, $data, $key ) {
		return $this->aa_set( $what, $data[ $key ] );
	}

	private function aa_set_autotrack_option( $what, $key ) {
		$data = array(
			AFSA_AUTOTRACK_DATASET => 'dataset',
			AFSA_AUTOTRACK_ON      => 'on',
			AFSA_AUTOTRACK_OFF     => 'off',
		);

		return $this->aa_set( $what, $data[ $key ] );
	}

	public function render() {
		if ( ! $this->should_track() ) {
			return '';
		}

		if ( $this->rendered ) {
			AFSA_Tools::log( 'script already rendered' );

			return null;
		}
		$this->rendered = true;

		$ip_setting           = AFSA_Config::get_int_option( 'ip_setting' );
		$localization_setting = AFSA_Config::get_int_option( 'localization_setting' );
		$user_consent         = AFSA_Config::get_int_option( 'user_consent' );
		$cookie_setting       = AFSA_Config::get_int_option( 'cookie_setting' );

		// CREATE

		$aa = array();

		$aa[] = $this->aa_create( $cookie_setting == 1 ? 'nocookie' : 'auto' );

		// TRACKERNAME (page name)

		$trackername = AFSA_Config::get_page_name();
		if ( empty( $trackername ) || $trackername == 'title' ) {
			$aa[] = $this->aa_set_raw( 'title', 'document.title' );
		} elseif ( ! empty( $trackername ) ) {
			$aa[] = $this->aa_set( 'title', $trackername );
		}

		// CMS

		$aa[] = $this->aa_set( 'cms', AFSA_Config::CMS() );
		$aa[] = $this->aa_set( 'api', AFSA_MODULE_VERSION );

		// ECOM INFOS

		if ( AFSA_Config::advanced_ecommerce_enabled() ) {
			$currency = AFSA_Config::get_global_currency_code();
			if ( ! empty( $currency ) ) {
				$aa[] = $this->aa_set( 'currencyCode', AFSA_Config::get_global_currency_code() );
			}

			$this->banner = 'Enhanced ECommerce';
		}

		// PRIVACY

		if ( $ip_setting != 0 ) {
			$aa[] = $this->aa_set( 'anonymizeip', $ip_setting );
		}

		if ( $user_consent != 0 ) {
			$aa[] = $this->aa_set_option(
				'cookieconsent_mode',
				array(
					1 => 'exemption',
					2 => 'consent_auto',
				),
				$user_consent
			);

			$aa[] = $this->aa_set_option(
				'cookieconsent_audience',
				array(
					0 => 'eu',
					1 => 'all',
				),
				$localization_setting
			);
		}

		// PAGE

		$aa[] = $this->render_category_info( AFSA_Config::get_page_categories() );

		// AUTOTRACK

		$autotrack_all = AFSA_Config::get_autotrack_all_option();

		$aa[] = $this->aa_set_autotrack_option( 'autotrack', $autotrack_all );

		$autotrack_options = AFSA_Config::get_autotrack_option_array();
		if ( ! empty( $autotrack_options ) ) {
			foreach ( $autotrack_options as $name => $value ) {
				if ( $value != $autotrack_all ) {
					$aa[] = $this->aa_set_autotrack_option(
						str_replace( '_', '.', $name ),
						$value
					);
				}
			}
		}

		// Should come before user infos
		if ( ! ( AFSA_Config::is_back_office() && ! AFSA_Config::track_admin_pages() ) ) {
			$aa[] = 'aa("send", "pageview");';
		}

		// USER / LOGIN infos

		if ( AFSA_Config::get_int_option( 'user_logged_tracking' ) == 1 ) {
			$aa[] = $this->render_loggin_info();
			$aa[] = $this->render_user_info();
		}

		$aa[] = $this->render_buffer();

		$js_url = '//code.afsanalytics.com/js2/analytics.js';

		$cms_version = AFSA_Config::CMS() . ' ' . AFSA_Config::CMS_version() . ' ';
		$ret         = "\n<!-- AFS Analytics V7 - "
				. $cms_version
				. ' Module ' . AFSA_MODULE_VERSION
				. " -->\n"
				. "\n<script  type='text/javascript'>"
				. "\n(function(i,s,o,g,r,a,m){i['AfsAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','$js_url','aa');\n"
				. implode( "\n", array_filter( $aa ) )
				. "\n</script>\n"
				. '<!-- [ END ] ' . $this->banner . " Analytics Code by AFSAnalytics.com -->\n";

		return $this->log( $ret );
	}

	/**
	 * Render additional tracker code at the bottom of the page
	 *
	 * @return string js code
	 */
	public function render_bottom_js() {

		if ( ! AFSA_Config::afsa_enabled() ) {
			return '';
		}

		$this->render_admin_bottom_js();

		do_action( 'afsa_before_bottom_js_rendered' );

		if ( empty( $js = $this->render_buffer() ) ) {
			AFSA_Tools::log( 'empy bottom js' );
			return '';
		}

		AFSA_Tools::log( '[WOO] RDR Tracker BOTTOM ' . json_encode( $js, JSON_PRETTY_PRINT ) );

		$ret = '<!-- AFS Analytics V7  Bottom JS [ START ] -->' . "\n"
				. AFSA_Tools::render_js_script( $js )
				. '<!-- [ END ] ' . $this->banner . " Analytics Code by AFSAnalytics.com -->\n";

		return $this->log( $ret );
	}

	public function render_admin_bottom_js() {
		if ( AFSA_Config::is_back_office() ) {
			$this->render_refunded_orders();
			$this->render_updated_orders();
		}
	}

	/**
	 * render code buffer then reset it
	 *
	 * @return type
	 */
	public function render_buffer() {
		if ( empty( $this->buffer ) ) {
			return null;
		}

		$aa   = empty( $this->buffer['aa'] ) ? null : implode( "\n", $this->buffer['aa'] );
		$AFSA = empty( $this->buffer['AFSA'] ) ? null : implode( "\n", $this->buffer['AFSA'] );

		$this->buffer = array();

		$ret = $aa
				. ( empty( $AFSA ) ? null : "\nfunction onAFSATrackerLoaded() {\n$AFSA\n};" );

		return empty( $ret ) ? null : $ret;
	}

	/**
	 * Save code to be rendered at the bottom of the page
	 * in a tmp buffer.
	 * ( see previous method  render_buffer() )
	 *
	 * @param array  $p js code lines
	 * @param string $type aa for basic aa instructions, AFSA for functions requring AFSA.tracker to be loaded
	 *
	 * @return bool success
	 */
	public function assimilate( $p, $type = 'aa' ) {
		if ( empty( $p ) ) {
			return false;
		}

		if ( ! isset( $this->buffer[ $type ] ) ) {
			$this->buffer[ $type ] = array();
		}

		$this->buffer[ $type ] = array_merge( $this->buffer[ $type ], (array) $p );

		return true;
	}

	/*
	 * User Info
	 *
	 *
	 */

	private function render_loggin_info() {
		if ( $this->get_visitor_infos()->is_logged() && ! isset( $_COOKIE['afslogged'] ) ) {
			return "aa('set','visitor.logged','1');\n"
					. "var d = new Date();\n"
					. "d.setTime(d.getTime() +(3600*1000));\n"
					. "var expires = 'expires='+ d.toUTCString();\n"
					. "document.cookie='afslogged=1;'+expires+';path=/';\n";
		}

		return null;
	}

	private function render_user_info() {
		$infos = $this->get_visitor_infos();
		$infos->retrieve();

		if ( $infos->is_logged() ) {

			if ( ! isset( $_COOKIE['afssetuser'] ) ) {
				$ret = "var vdata={job:'update'};\n";

				$data = $infos->get();
				foreach ( $data as $k => $v ) {
					if ( ! in_array( $k, array( 'id', 'logged' ) ) ) { // excluded infos
						$ret .= "vdata.$k='$v';\n";
					}
				}

				return $ret
						. "var ol= Object.keys(vdata).length;\n"
						. "if (ol>2){\n"
						. "aa('set','visitor',vdata);\n"
						. "aa('send','visitor');\n"
						. "var d = new Date();\n"
						. "d.setTime(d.getTime() +((3600*12)*1000));\n"
						. "var expires = 'expires='+ d.toUTCString();\n"
						. "document.cookie = 'afssetuser=1;'+expires+';path=/';\n"
						. "}\n";
			}
		}

		return null;
	}

	public function render_category_info( $arr ) {
		$aa = array();
		if ( ! empty( $arr ) ) {
			foreach ( $arr as $c ) {
				$aa[] = $this->aa_set( 'contentGroup1', $c );
			}
		}

		return empty( $aa ) ? null : implode( "\n", $aa );
	}

	// ADDITIONNALS INFOS (sent after main tracker has been rendered )
	// ECOMMERCE INFOS

	/**
	 * Render order informations (including products)
	 * on order confirmation page
	 *
	 * @param object|int $mixed order (object | id)
	 *
	 * @return string js code
	 */
	public function render_order_info( $mixed, $p = array() ) {

		return $this->advanced_mode ?
				$this->render_advanced_order_info( $mixed, $p ) :
				$this->render_simple_order_info( $mixed );
	}

	public function render_simple_order_info( $mixed ) {
		if ( empty( $mixed ) ) {
			return;
		}

		if ( ! AFSA_Config::basic_ecommerce_enabled() ) {
			return;
		}

		$aa   = array();
		$o    = new AFSA_Order_Infos( $mixed );
		$data = $o->parse( AFSA_FORMAT_TRANSACTION_ITEM );

		$aa[] = $this->aa_set( 'addTransaction', $data['order'] );

		foreach ( $data['items'] as $item ) {
			$item['id'] = $data['order']['id'];

			$aa[] = $this->aa_add_item( $item );
		}

		$aa[] = $this->aa_send( 'ecommerce' );

		return $this->assimilate( $aa );
	}

	// ADVANCED ECOMMERCE
	// ORDER

	public function render_advanced_order_info( $mixed, $p = array() ) {
		if ( empty( $mixed ) ) {
			return;
		}

		$aa = array();
		$o  = new AFSA_Order_Infos( $mixed );

		// rendering billing infos first
		$this->render_billing_info( $o->get_order() );

		$data = $o->parse( AFSA_FORMAT_PRODUCT );

		foreach ( $data['items'] as $item ) {
			$aa[] = $this->aa_ec_add_product( $item );
		}

		// setting clear options to no as checkout will follow
		$aa[] = 'aa("ec:SetOption", {"clear": "no"});';

		$aa[] = $this->aa_ec_action( 'purchase', $data['order'] );

		// setting clear options to default value;
		 $aa[] = 'aa("ec:SetOption", {"clear": "yes"});';

		$ret = $this->assimilate( $aa ); // should come before vv

		if ( empty( $p['nostep'] ) ) {
			$this->render_checkout_step(
				AFSA_TRACKER_CHEKOUT_STEP_ORDER_CONFIRMATION,
				null,
				// payment_method {code,title}
					$o->get_payment_method_infos()
			);
		}

		return $ret;
	}

	public function render_billing_info( $order ) {

		$infos = $this->get_infos_manager()->customer();
		$data  = $infos->parseFromOrder( $order );

		if ( ! empty( $data ) ) {
			$data['job'] = 'update';
			$this->assimilate(
				array(
					$this->aa_set( 'visitor', $data ),
					$this->aa_send( 'visitor' ),
				)
			);
		}
	}

	// REFUNDS


	public function render_refunded_orders() {
		$items = AFSA_Config::get_refunded_orders();
		if ( ! empty( $items ) ) {
			foreach ( $items as $order_id => $refunds ) {
				foreach ( $refunds as $refund_id ) {
					$o     = new AFSA_Order_Infos( $order_id );
					$items = $o->parse_refunded_items( $refund_id );
					$this->render_refunded_order( $order_id, $items );
				}
			}
		}

		AFSA_Config::reset_refunded_orders();
	}

	public function render_refunded_order( $order_id, $products = null ) {
		if ( ! $this->advanced_mode ) {
			return null;
		}

		$aa = array();

		if ( ! empty( $products ) ) {
			foreach ( $products as $product ) {
				$aa[] = $this->aa_add_product(
					array(
						'id'       => $product['sku'],
						'quantity' => $product['quantity'],
					)
				);
			}
		}

		$aa[] = $this->aa_set_action( 'refund', json_encode( array( 'id' => $order_id ) ) );
		$aa[] = $this->aa_send( 'refund' );

		return $this->assimilate( $aa );
	}

	// UPdated Orders (status change)

	public function render_updated_orders() {
		$items = AFSA_Config::get_updated_order_status();
		if ( empty( $items ) ) {
			return;
		}

		$aa = array();

		foreach ( $items as $order_id => $status ) {

			$aa[] = $this->aa_set_action(
				'orderStatus',
				json_encode(
					array(
						'id'     => $order_id,
						'status' => $status,
					)
				)
			);
		}

		AFSA_Config::reset_updated_order_status();

		$aa[] = $this->aa_send( 'orderStatus' );
		return $this->assimilate( $aa );
	}

	// CHECKOUT
	/*
	 *
	 * Step 1 : cart view
	 * Step 2 : checkout form
	 * Step 3 : on order confirmation page
	 *
	 *
	 */

	private function render_checkout_action_params( $step, $option = null ) {
		$ret = array(
			'step' => $step,
		);
		$op  = null;
		if ( ! empty( $option ) ) {

			if ( is_string( $option ) ) {
				$op = &$option;
			} else {

				if ( ! empty( $option['code'] ) ) {
					$op = $option['code'];
				} elseif ( ! empty( $option['title'] ) ) {
					$op = $option['title'];
				}
			}

			if ( $op ) {
				$ret['option'] = $op;
			}
		}

		return $ret;
	}

	public function render_checkout_step( $step, $products = null, $option = null ) {
		if ( ! $this->advanced_mode || ! $step ) {
			return null;
		}

		$aa = array();

		// only add products on first step
		if ( $products ) {

			$infos  = new AFSA_Product_Infos();
			$p_data = $infos->parse_multiple(
				$products,
				array( 'format' => AFSA_FORMAT_PRODUCT )
			);

			foreach ( $p_data as $data ) {
				$aa[] = $this->aa_ec_add_product( $data );
			}
		}

		$aa[] = $this->aa_ec_action( 'checkout', $this->render_checkout_action_params( $step, $option ) );

		return $this->assimilate( $aa );
	}

	// CART

	/**
	 * Render js For AddToCart Event
	 *
	 * @param type $product_data product data (format:  AFSA_FORMAT_PRODUCT)
	 *
	 * @return string js code
	 */
	public function render_add_to_cart( $product_data ) {
		if ( ! $this->advanced_mode ) {
			return null;
		}

		return $this->assimilate(
			array(
				$this->aa_ec_add_product( $product_data ),
				$this->aa_ec_action( 'add' ),
			)
		);
	}

	/**
	 * Render js For RemoveFromCart Event
	 *
	 * @param type $product_data product data (format:  AFSA_FORMAT_PRODUCT)
	 *
	 * @return string js code
	 */
	public function render_remove_from_cart( $product_data ) {
		if ( ! $this->advanced_mode ) {
			return null;
		}

		$product_data['quantity'] = abs( $product_data['quantity'] );

		return $this->assimilate(
			array(
				$this->aa_ec_add_product( $product_data ),
				$this->aa_ec_action( 'remove' ),
			)
		);
	}

	// PRODUCT

	/**
	 * Render Product Impressions
	 *
	 * @param array  $products raw product data
	 * @param string $src name of the product list (search result, hom, etc.)
	 *
	 * @return string rendered js code
	 */
	public function render_products_impression( $products, $src = null ) {
		if ( ! $this->advanced_mode ) {
			return null;
		}

		$aa = array();

		$infos  = $this->get_infos_manager()->product();
		$p_data = $infos->parse_multiple(
			$products,
			array(
				'format' => AFSA_FORMAT_IMPRESSION,
				'list'   => $src ? $src : $this->get_last_product_list(),
			)
		);

		foreach ( $p_data as $data ) {
			$aa[] = $this->aa_ec_add_impression( $data );
		}

		return $this->assimilate( $aa );
	}

	public function render_product_detail_view( $product ) {
		if ( ! $this->advanced_mode ) {
			return null;
		}

		$aa            = array();
		$action_params = array();

		$infos = $this->get_infos_manager()->product();

		$p_data = $infos->parse(
			$product,
			null,
			0,
			AFSA_FORMAT_PRODUCT
		);
		unset( $p_data['url'], $p_data['quantity'] );

		if ( $this->product_list ) {
			$action_params['list'] = $this->product_list;
		} else {

			unset( $p_data['position'] );
		}

		$aa[] = $this->aa_ec_add_product( $p_data );

		$aa[] = $this->aa_ec_action( 'detail', $action_params );

		return $this->assimilate( $aa );
	}

	/**
	 * Render Product Click tracking code
	 * (call AFSA.trackProductClick from AFSA.js)
	 *
	 * @param array  $products raw product data
	 * @param string $src name of the product list (search result, hom, etc.)
	 *
	 * @return string rendered js code
	 */
	public function render_products_click( $products, $src ) {
		if ( ! $this->advanced_mode ) {
			return null;
		}

		$aa = array();

		// need to make sure that products
		// contains url for product detail ( as $p['link'])

		$infos  = new AFSA_Product_Infos();
		$p_data = $infos->parse_multiple(
			$products,
			array(
				'list'    => $src,
				'format'  => AFSA_FORMAT_PRODUCT,
				'add_url' => 1,
			)
		);

		foreach ( $p_data as $data ) {
			$aa[] = 'AFSA.tracker.listenProductClick(' . json_encode( $data ) . ');';
		}

		return $this->assimilate( $aa, 'AFSA' );
	}

	/**
	 * @param array  $products raw product data
	 * @param string $src name of the product list (search result, hom, etc.)
	 *
	 * @return string rendered js code
	 */
	public function render_product_click_by_http_referal( $products, $src = 'detail' ) {
		if ( ! $this->advanced_mode ) {
			return null;
		}

		$infos  = new AFSA_Product_Infos();
		$p_data = $infos->parse_multiple(
			$products,
			array(
				'list'   => $src,
				'format' => AFSA_FORMAT_PRODUCT,
			)
		);
		$aa     = array();
		foreach ( $p_data as $data ) {
			$aa[] = 'AFSA.tracker.sendProductClickByHttpReferal(' . json_encode( $data ) . ');';
		}

		return $this->assimilate( $aa, 'AFSA' );
	}

	// DEBUG

	private function get_log_filename() {
		return __DIR__ . '/../logs/'
				. ( AFSA_Config::is_ajax() ? 'ajax/' : '' )
				. time() . '.' . AFSA_Config::get_page_name() . '.txt';
	}

	private function save_log() {
		if ( ! AFSA_Config::is_log_enabled() || empty( $this->_log ) ) {
			return false;
		}

		file_put_contents( $this->get_log_filename(), str_replace( "\n", PHP_EOL, $this->_log ) );
		$this->_log = null;

		return true;
	}

	public function log( $data, $title = '' ) {
		$title .= sanitize_url( $_SERVER['REQUEST_URI'] ) . "\n\n"; // "\n[" . Tools::getValue('controller') . '/' . Tools::getValue('action') . ']';

		if ( AFSA_Config::is_ajax() ) {
			$title .= ' [ CALLED VIA AJAX ] ';
		}

		$str = '';

				$customer_id = get_current_user_id();

		$str .= is_array( $data ) ?
				json_encode( $data, JSON_PRETTY_PRINT ) :
				$data;

		$this->_log .= empty( $title ) ?
				"\n$str\n" :
				"\n" . strtoupper( $title )
								. "\ncustomer_id $customer_id \n "
								. "\n----\n$str\n";

		return $data;
	}

}
