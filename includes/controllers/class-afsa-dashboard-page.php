<?php

require_once AFSA_INCLUDES_DIR . '/controllers/renderer/class-afsa-dashboard-renderer.php';
require_once AFSA_INCLUDES_DIR . '/controllers/renderer/class-afsa-intro-renderer.php';

class AFSA_Dashboard_Page {

	public static function render() {
		if ( is_admin() ) {
			$renderer = new AFSA_Dashboard_Renderer();
			print $renderer->render_view();
		}
	}

	public static function run_demo() {
		AFSA_Config::set_demo_mode();
		static::render();
	}

	public static function render_intro() {
		if ( is_admin() ) {
			$renderer = new AFSA_Intro_Renderer();
			print $renderer->render_view();
		}
	}

}
