<?php

require_once AFSA_INCLUDES_DIR . '/class-afsa-route-manager.php';
require_once AFSA_INCLUDES_DIR . '/api/class-afsa-api.php';

class AFSA_Dashboard_Renderer {

	public static $api = null;

	protected $account_id;
	protected $afsa_api = null;
	protected $widgets  = array();
	protected $template = '';
	protected $embedded = false;



	public static function init_api() {
		static::$api = $api = new AFSA_Api();
		$api->login();
		return $api->is_logged();
	}



	public static function action_enqueue_scripts() {

		$afsa_home = AFSA_Config::get_afsa_api_home();

		wp_enqueue_style(
			'afsa_gfonts',
			'https://fonts.googleapis.com/css?family=Lato:700|Open+Sans:500|Roboto'
		);

		wp_enqueue_style(
			'afsa_font_awesome',
			'//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'
		);

		wp_enqueue_style(
			'afsa_dashboard_base',
			$afsa_home . '/assets/css/wordpress/current/packed.css?v=1'
		);

		wp_enqueue_style(
			'afsa_dashboard_local',
			AFSA_Config::get_url( 'css/dashboard.css' )
		);

		foreach ( static::get_dashboard_scripts() as $n ) {

			wp_enqueue_script(
				$n . '.script',
				$afsa_home . '/assets/js/common/v2/' . $n . '.js',
				array( 'jquery' ),
				false // version
			);
		}
	}

	public static function get_dashboard_scripts() {
		return array( 'd3.min', 'c3.min', 'dashboard' );
	}

	public function __construct() {

		$this->account_id    = AFSA_Config::get_account_id();
		$this->dashboard_url = admin_url( 'admin.php?page=' . AFSA_MENU_PAGE_DASHBOARD_ID );

		if ( ! $this->embedded ) {
			AFSA_Renderer::hook_footer_line();
		}
	}

	public function api_login() {

		$api = static::$api ?
			static::$api :
			new AFSA_Api();

		$this->afsa_api = $api;

		$api->simple_login();
		return $api->is_logged();
	}

	public function add_widgets( $arr ) {
		if ( ! empty( $arr ) ) {
			foreach ( array_keys( $arr ) as $n ) {
				$this->add_widget( $n );
			}
		}
	}

	public function add_widget( $t ) {
		$this->widgets[] = array(
			'id'      => $t,
			'options' => array(),
		);
	}

	public function prepare_common_template( $content = '' ) {
		$this->template = $this->render_widget( 'topmenubar' )
			. '<div id=afsa_col_infos>'
			. $this->render_widget( 'config' )
			. '</div>'
			. '<div id=afsa_col_widgets>'
			. $content
			. '</div>';
	}

	public function render_widget( $type, $options = null ) {

		$dataset = ' data-type="' . $type . '"';
		if ( $options ) {
			$dataset .= ' data-options=\'' . json_encode( $options ) . '\'';
		}

		return '<div class="afsa_requested_widget afsa_widget_' . $type . '"'
			. $dataset . '>'
			. '</div>';
	}

	protected function render_js_config() {

		$account = AFSA_Account_Manager::get()->get_current();

		$cfg = array(
			'lng'         => AFSA_Config::get_lng(),
			'account_id'  => $this->account_id,
			'server_host' => AFSA_Config::get_afsa_api_home(),
			'ecom'        => array(),
			'ajax'        => array(
				'server' => AFSA_Config::get_ajax_server_url(),
			),
			'dashboard'   => array(
				'container' => array(
					'template' => 'traffic',
				),
			),
			'url'         => array(
				'dashboard' => $this->dashboard_url,
			),
		);

		$access_key = AFSA_Config::get_access_key();
		if ( $access_key ) {
			$cfg['access_key'] = $access_key;
		}

		if ( AFSA_Config::advanced_ecommerce_enabled() ) {
			$cfg['ecom'] = array(
				'enabled'            => 1,
				'level'              => 'advanced',
				'currency'           => AFSA_Config::get_global_currency_code(),
				'unsupported_brands' => true,
			);

			$cfg['dashboard'] = array(
				'container' => array(
					'template' => 'ecom',
				),
			);
		} else {

			$cfg['ecom'] = array(
				'enabled'            => 0,
				'level'              => 'limited',
				'currency'           => AFSA_Config::get_global_currency_code(),
				'unsupported_brands' => true,
			);
		}

		$cfg['dashboard']['host'] = AFSA_Config::CMS();
		$cfg['dashboard']['type'] = 'plugin';

		if ( AFSA_Config::is_demo() ) {
			$cfg['dashboard']['orders_ip_check_disabled'] = 1;
			$cfg['demo_mode']                             = 1;
		} else {

			$plan = $account->plan_infos();

			$cfg['account_infos'] = array(
				'id'   => $this->account_id,
				'plan' => $account->plan_infos(),
			);
		}

		return $cfg;
	}

	public function render_js_data() {
		return AFSA_Tools::render_js_data(
			array(
				'AFSA_dashboard_config' => $this->render_js_config(),
				's_data'                => 1,
				's_verif'               => 1,
				's_nonce'               => wp_create_nonce( 'AFSA' ),
				'logo_url'              => AFSA_Config::get_url( 'assets/images/logo.square.png' ),
			)
		);
	}

	public function render_view( $params = array() ) {

		if ( ! AFSA_Config::afsa_enabled() && ! AFSA_Config::is_demo() ) {
			return $this->render_no_acccount_set_notice();
		}

		if ( ! $this->api_login() ) {
			AFSA_Tools::log( 'API not logged' );
		}

		if ( ! empty( $params['widgets'] ) ) {
			$this->add_widgets( $params['widgets'] );
		}

		return $this->render_notice()
			. '<div id = afsa_container></div>'
			. $this->render_js_data()
			. '<script src = "' . AFSA_Config::get_url( '/js/dashboard.js' ) . '"></script>';
	}

	private function render_notice() {
		return AFSA_Config::is_demo() ?
			'<div id=afsa_demo_notice>'
			. '<div class=afsa_logo_container>'
			. '<img class=afsa_logo src=' . AFSA_Config::get_url( 'assets/images/logo.small.png' ) . '>'
			. '<div class=afsa_form>'
			. '<div class="afsa_create_account afsa_button"> ' . __( 'Create your very own Account', 'afsanalytics' ) . '</div>'
			. '</div>'
			. '</div>'
			. '<div class=afsa_content>'
			. '<div class=afsa_headline>'
			. __( 'Live Demo', 'afsanalytics', 'afsanalytics' )
			. '</div>'
			. '<div class=afsa_text><div>'
			. __( 'This dashboard is displaying in real time the activity of a WordPress website running a Woocommerce eShop.', 'afsanalytics' )
			. '</div><div>'
			. __( 'To monitor your own website, you will have to open your own account.', 'afsanalytics' )
			. '</div></div>'
			. '</div>'
			. '</div>'
			. AFSA_Tools::render_js_data(
				array( 'AFSA_site_infos' => AFSA_Account_Manager::get()->get_account_creation_params() )
			)
			. '<script src = "' . AFSA_Config::get_url( '/js/intro.js' ) . '"></script>' :
			null;
	}
}
