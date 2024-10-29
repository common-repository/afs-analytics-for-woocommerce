<?php

class AFSA_Account_Form_Renderer {

	public static function render_account_form( $type = 'intro' ) {

		$logo = '<div class=afsa_logo_container>'
				. '<img class=afsa_logo src=' . AFSA_Config::get_url( 'assets/images/logo.small.png' ) . '>'
				. '<div class=afsa_intro_title>'
				. __( 'Configure your AFS Analytics plugin in one click', 'afsanalytics' ) . '</div>'
				. '</div>';

		$ret = $type == 'intro' ?
				'<div class=afsa_account_form>' . $logo :
				$logo . '<div class=afsa_account_form>';

		return $ret . '<form  method=post class=afsa_existing_account>'
				. '<div class="afsa_form_help">'
				. __( 'If you already possess an AFS analytics account, enter your Website ID below, click on "Link website", and you are done.', 'afsanalytics' )
				. '</div>'
				. '<input type="text" pattern="[0-9]{8}" maxlength="8" name="afsa_linked_account_id" '
				. 'value="" placeholder="' . __( 'Website ID', 'afsanalytics' ) . '">'
				. '<input type=hidden name=page value=afsa_settings_page>'
				. '<input class="afsa_button" type=submit value="' . __( 'Link Website', 'afsanalytics' ) . '">'
				. '</form>'
				. '<div  class=afsa_new_account>'
				. '<div class="afsa_form_help">'
				. __( 'Or create a free account by clicking on "Create Free Account".', 'afsanalytics' )
				. ' '
				. __( 'Would you wish to, you will be able to upgrade your free account to an advanced one at any time.', 'afsanalytics' )
				. '</div>'
				. '<div class="afsa_create_account afsa_button"> ' . __( 'Create Free Account', 'afsanalytics' ) . '</div>'
				. '</div>'
				. '</div>'
				. AFSA_Tools::render_js_data(
					array( 'AFSA_site_infos' => AFSA_Account_Manager::get()->get_account_creation_params() )
				);
	}

	public static function redirect_on_account_linked() {

		$account_id = empty( $_POST['afsa_linked_account_id'] ) ?
				null :
				sanitize_text_field( $_POST['afsa_linked_account_id'] );

		if ( AFSA_Config::validate_account_id( $account_id ) ) {

			AFSA_Config::save_account_id( $account_id );

			$url = AFSA_Tools::build_url(
				AFSA_Config::get_account_manager_url(),
				array(
					'afsa_action' => 'link_account',
					'account_id'  => $account_id,
				)
			);

			AFSA_Tools::redirect( $url );
		}
	}

	public static function render_demo_form() {

		return '<div class=afsa_demo_form>'
				. '<div class=afsa_row>'
				. '<div class=afsa_text_container>'
				. '<div class=afsa_title>' . __( 'Live Demo', 'afsanalytics' ) . '</div>'
				. '<div class="afsa_text">'
				. __( 'Experience the full power of AFS Analytics with our live demo. No Account required.', 'afsanalytics' )
				. '</div>'
				. '<a href="' . AFSA_Config::get_dashboard_demo_url() . '" '
				. ' class="afsa_launch_demo afsa_button">' . __( 'Launch Demo', 'afsanalytics' ) . '</a>'
				. '</div>'
				. '<div class=afsa_img_container>'
				. '<img class=afsa_screen src=' . AFSA_Config::get_url( 'assets/images/welcome.png' ) . '>'
				. '</div>'
				. '</div>'
				. '<div class=afsa_row>'
				. '</div>'
				. '</div>';
	}

}
