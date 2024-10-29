<?php

require_once 'class-afsa-admin-widget-dashboard-renderer.php';
require_once 'class-afsa-account-form-renderer.php';

class AFSA_Admin_Widget_Renderer {

	public static function should_display() {
		return AFSA_Config::get_option( 'display_admin_summary' );
	}

	public static function action_enqueue_scripts() {

		if ( AFSA_Admin_Widget_Dashboard_Renderer::should_display() ) {
			AFSA_Admin_Widget_Dashboard_Renderer::action_enqueue_scripts();
		}

		// "intro" widget
		else {
			wp_enqueue_style(
				'afsa_intro_forms',
				AFSA_Config::get_url( 'css/intro.forms.css' )
			);

			wp_enqueue_style(
				'afsa_intro_local',
				AFSA_Config::get_url( 'css/intro.widget.css' )
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

						wp_enqueue_script(
							'afsa_intro_repos_script',
							AFSA_Config::get_url( 'js/admin.widget.repos.js' ),
							array( 'jquery' )
						);
		}
	}

	public function render() {

		if ( AFSA_Admin_Widget_Dashboard_Renderer::should_display() ) {
			$rdr = new AFSA_Admin_Widget_Dashboard_Renderer();
			return $rdr->render();
		}

		return '<div class=afsa_intro_container>'
				. AFSA_Account_Form_Renderer::render_account_form( 'widget' )
				. AFSA_Account_Form_Renderer::render_demo_form()
				. '</div>';

	}

}
