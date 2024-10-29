<?php

class AFSA_Setting_Tab_Privacy extends AFSA_Setting_Tab {

	public function init() {

		$this->add_section(
			'afsa_privacy_section',
			__( 'Privacy Settings', 'afsanalytics' ),
			function() {
					// return optional description;
			}
		);

		$this->add_checkboxes(
			array(
				'afsa_anon_user_infos' => __( 'Anonymize Users Information', 'afsanalytics' ),
			)
		);

		$this->add_select_inputs(
			array(
				'cookie_setting' => __( 'Cookie Settings', 'afsanalytics' ),
				'ip_setting'     => __( 'Anonymize IP addresses', 'afsanalytics' ),
			)
		);

		$this->add_section(
			'afsa_gdpr_section',
			__( 'EU law on the deposit of cookies', 'afsanalytics' ),
			function() {

			}
		);

		$this->add_select_inputs(
			array(
				'user_consent'         => __( "Ask User's Consent", 'afsanalytics' ),
				'localization_setting' => __( 'Located in', 'afsanalytics' ),
			)
		);
	}

}
