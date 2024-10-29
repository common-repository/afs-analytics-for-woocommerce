<?php

class AFSA_Setting_Tab_Main extends AFSA_Setting_Tab {

	public function init() {

		if ( isset( $_GET['account_id'] ) ) {
			$account_id = sanitize_text_field( $_GET['account_id'] );

			if ( AFSA_Config::validate_account_id( $account_id ) ) {
				$this->settings['account_id'] = $account_id;
			}
		}

		if ( isset( $_GET['afsa_result_ip_removal'] ) ) {
			$result  = (int) sanitize_text_field( $_GET['afsa_result_ip_removal'] );
			$enabled = $result % 2;
			update_option( 'afs_self_visits_hidden', $enabled );
		}

		$this->settings['self_visits_hidden'] = get_option( 'afs_self_visits_hidden' );

		$account_id = empty( $this->settings['account_id'] ) ?
			'00000000' :
			$this->settings['account_id'];

		$this->add_section(
			'afsa_main_section',
			__( 'Main Settings' ),
			function () {
				// return optional description;
			}
		);

		add_settings_field(
			'account_id',
			__( 'Website ID', 'afsanalytics' ),
			function () use ( $account_id ) {

				print AFSA_Tools::render_js_data(
					array( 'AFSA_site_infos' => AFSA_Account_Manager::get()->get_account_creation_params() )
				);

				$help = AFSA_Config::get_account_id() ?
					__( 'Your AFS Analytics Website ID', 'afsanalytics' ) :
					__( 'Enter your AFS Analytics Website ID or', 'afsanalytics' )
					. ' <span class="afsa_create_account afsa_link">'
					. __( 'Create a free Account', 'afsanalytics' ) . '</span> '
					. __( 'if you do not have one yet', 'afsanalytics' );

				print '<input type="text"  pattern="[0-9]*" maxlength=8 '
					. $this->input_name( 'account_id' )
					. 'value=' . $account_id . '>'
					. '<p class=afsa_help>'
					. $help
					. '.</p>';
			},
			$this->page,
			$this->section_id
		);

		$arr = array(
			'admin_pages_tracking'     => __( 'Track Admin Page', 'afsanalytics' ),
			'self_visits_hidden'       => __( 'Ignore my visits', 'afsanalytics' ),
			'user_logged_tracking'     => __( 'Track user login', 'afsanalytics' ),
			'display_admin_summary'    => __( 'Display day trends summary on Admin Dashboard', 'afsanalytics' ),
			'gravatar_profile_enabled' => __( 'Enable gravatar profile support', 'afsanalytics' ),
		);

		if ( empty( (int) AFSA_Config::get_account_id() ) ) {
			unset( $arr['self_visits_hidden'] );
		}

		$this->add_checkboxes( $arr );

		add_settings_field(
			'accesskey',
			__( 'Access Key', 'afsanalytics' ),
			function () {

				$key_value = ! empty( $this->settings['accesskey'] ) ?
				strip_tags( $this->settings['accesskey'] ) : '';

				print '<input type="text"   '
					. $this->input_name( 'accesskey' )
					. 'value=' . ( htmlspecialchars( $key_value ) )
					. '>'
					. '<p class="afsa_help ">'
					. __( 'An Access key allow you to access your AFS Analytics Dashboard without providing a password each time.', 'afsanalytics' )
					. '</p>'
					. ' <div class="afsa_warpto afsa_access_key" data-to="' . AFSA_Route_Manager::keys() . '">'
					. __( 'Create an access key', 'afsanalytics' ) . '</div> ';
			},
			$this->page,
			$this->section_id
		);

		// access
	}
}
