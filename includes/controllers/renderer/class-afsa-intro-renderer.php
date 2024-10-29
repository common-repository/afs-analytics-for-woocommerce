<?php

require_once AFSA_INCLUDES_DIR . '/class-afsa-route-manager.php';
require_once 'class-afsa-account-form-renderer.php';
require_once 'class-afsa-renderer.php';


class AFSA_Intro_Renderer {

	public static function action_enqueue_scripts() {

		wp_enqueue_style(
			'afsa_intro_forms',
			AFSA_Config::get_url( 'css/intro.forms.css' )
		);

		wp_enqueue_style(
			'afsa_intro_local',
			AFSA_Config::get_url( 'css/intro.css' )
		);

		wp_enqueue_style(
			'gfonts',
			'https://fonts.googleapis.com/css?family=Lato:700|Open+Sans:500'
		);

		wp_enqueue_script(
			'afsa_intro_script',
			AFSA_Config::get_url( 'js/intro.js' ),
			array( 'jquery' )
		);
	}

	public function __construct() {

		AFSA_Renderer::hook_footer_line();
	}

	public function render_view() {
		return '<div class=afsa_intro_container>'
				. AFSA_Account_Form_Renderer::render_account_form()
				. AFSA_Account_Form_Renderer::render_demo_form()
				. '</div>';
	}

}
