<?php

require_once 'class-afsa-admin-widget-renderer.php';

class AFSA_Admin_Widget_Dashboard_Renderer extends AFSA_Dashboard_Renderer {

	public static function should_display() {
		return AFSA_config::afsa_enabled();
	}

	public static function get_dashboard_scripts() {
		return array( 'dashboard' );
	}

	public function __construct() {
		$this->embedded = true;
		parent::__construct();
	}

	public function api_login() {
		$this->afsa_api = $api = new AFSA_Api();
		$api->simple_login();
		return $api->is_logged();
	}

	protected function render_js_config() {
		$cfg = parent::render_js_config();
		unset( $cfg['dashboard']['container'] );
		$cfg['dashboard']['do_not_parse'] = 0;
		return $cfg;
	}

	public function render() {

		if ( ! AFSA_Config::afsa_enabled() && ! AFSA_Config::is_demo() ) {
			return '';
		}

		if ( ! $this->api_login() ) {
			AFSA_Tools::log( 'API not logged' );
			return '<div  id = afsa_dashboard> '
					. '<div style="display: flex;align-items: center;" class=afsa_notice >'
					. '<img style="flex: 0 0 auto;margin-right:30px;"  class=afsa_logo src=' . AFSA_Config::get_url( 'assets/images/logo.small.png' ) . '>'
					. '<div style="flex: 0 0 auto;max-width: 60%;" class=afsa_help>'
					. __( 'Days Trends Summary will start to be displayed here as soon as you open the embedded', 'afsanalytics' )
					. ' <a href="' . $this->dashboard_url . '">'
					. __( 'dashboard', 'afsanalytics' )
					. '.</a>'
					. '</div></div></div>';
		}

		return '<div id = afsa_dashboard>'
				. $this->render_widget( 'Overview' )
				. '</div>'
				. $this->render_js_data()
				. '<script src="' . AFSA_Config::get_url( 'js/admin.widget.js' ) . '"></script>';
	}

}
