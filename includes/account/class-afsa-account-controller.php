<?php

defined( 'ABSPATH' ) || exit;

class AFSA_Account_Controller {

	private $api_auto_login = true;
	private static $instance;

	public static function get() {
		return static::$instance ?
			static::$instance :
			static::$instance = new AFSA_Account_Controller();
	}

	private function validate_state() {
		$state = ! empty( $_REQUEST['afsa_state'] ) &&
			AFSA_Config::get_request_state() === sanitize_text_field( $_REQUEST['afsa_state'] );

		if ( ! $state ) {
			AFSA_Tools::log( __METHOD__, 'invalid state' );
			AFSA_Tools::redirect( AFSA_Config::get_admin_url() );
		}

		return $state;
	}

	public function on_action_completed() {

		if ( ! empty( $_REQUEST['afsa_action'] ) ) {
			switch ( sanitize_text_field( $_REQUEST['afsa_action'] ) ) {

				case 'account_created':
					$this->on_account_created();
					break;

				case 'api_initial_login': {
						// completing login process
						$api = new AFSA_Api();
						$api->simple_login();

						$this->welcome();
				}
					break;

				case 'link_account':
					$this->link_account();
					break;

				case 'welcome':
					// Do nothing
					break;
			}
		}
	}

	public function render() {
		$this->render_welcome();
	}

	public function get_welcome_url( $is_new ) {
		return AFSA_Tools::build_url(
			AFSA_Config::get_account_manager_url(),
			array(
				'afsa_action' => 'welcome',
				'afsa_new'    => (int) $is_new,
			)
		);
	}

	public function welcome( $is_new = true ) {
		AFSA_Tools::redirect( $this->get_welcome_url( $is_new ) );
	}

	// EXITING ACCOUNT LINK ACTION



	public function link_account() {
		if ( empty( $_REQUEST['account_id'] ) ) {
			return false;
		}

		$account_id = sanitize_text_field( $_REQUEST['account_id'] );
		if ( AFSA_Account_Manager::get()->set_current( $account_id ) ) {

			$this->on_account_set( $account_id );

			$this->welcome();
			return true;
		}

		return false;
	}

	// ACCOUNT CREATION





	public function on_account_created() {

		$this->validate_state();

		$id = sanitize_text_field( $_REQUEST['afsa_account_id'] );

		// Saving account infos
		$account = AFSA_Account_Manager::get()->set_current( $id );
		if ( ! $account ) {
			return;
		}

		if ( ! empty( $_REQUEST['afsa_trial_type'] ) ) {
			$account->set_trial(
				sanitize_text_field( $_REQUEST['afsa_trial_type'] ),
				empty( $_REQUEST['afsa_trial_period'] ) ? null : sanitize_text_field( $_REQUEST['afsa_trial_period'] )
			);
		}
		$account->save();

		$this->on_account_set( $id );

		$this->welcome();
	}

	private function on_account_set( $id ) {
		// Initiate api login since user is authenticated
		if ( $this->api_auto_login ) {
			$api = new AFSA_Api(
				array(
					'account_id'   => $id,
					'callback_url' => AFSA_Tools::build_url(
						AFSA_Config::get_account_manager_url(),
						array( 'afsa_action' => 'api_initial_login' )
					),
				)
			);
			$api->login();
		}
	}

	// WELCOME PAGE

	public function render_welcome() {

		print '<script>'
			. "document.cookie = 'afssetuser=0;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/';\n"
			. '</script>';

		print '<div class=afsa_welcome_container>'
			. '<div class=afsa_content>'
			. '<div class=afsa_header>'
			. '<div class=afsa_title>'
			. '<img class=afsa_logo src=' . AFSA_Config::get_url( 'assets/images/logo.small.png' ) . '>'
			. '<div class=afsa_label>'
			. __(
				'Congratulations!',
				'afsanalytics'
			)
			. '</div>'
			. '</div>'
			. '<div class=afsa_headline>'
			. __(
				'Your AFS Analytics plugin is now fully configured.',
				'afsanalytics'
			)
			. '</div>'
			. '</div>'
			. '<p>'
			. __( 'Traffic and activity on your shop is now monitored in real time.', 'afsanalytics' )
			. ' '
			. __(
				'Detailed statistics will begin to appear in your plugin dashboard as soon as new visitors will be visiting it.',
				'afsanalytics'
			)
			. '</p>'
			. '<p>'
			. __(
				'You can retrieve your Account ID at any time by visiting this plugin configuration page.',
				'afsanalytics'
			)
			. '</p>'
			. '<div class=afsa_footer>'
			. '<div class=afsa_thanks>'
			. __(
				'Thanks for using AFS analytics.',
				'afsanalytics'
			)
			. '</div>'
			. '</div>'
			. '<div class=afsa_button_bar>'
			. '<a href="' . AFSA_Config::get_config_controller_url() . '" '
			. ' class="afsa_button">'
			. __( 'Advanced configuration', 'afsanalytics' )
			. '</a>'
			. '<a href="' . AFSA_Config::get_dashboard_url() . '" '
			. ' class="afsa_button afsa_selected">'
			. __( 'Open Dashboard', 'afsanalytics' )
			. '</a>'
			. '<a href="' . AFSA_Route_Manager::get_dashboard_url() . '" '
			. ' class="afsa_button">'
			. __( 'Visit AFSAnalytics.com', 'afsanalytics' )
			. '</a>'
			. '</div>'
			. '</div>' // afsa_content
			. '</div>'; // container
	}
}
