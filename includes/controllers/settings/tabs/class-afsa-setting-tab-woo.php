<?php

class AFSA_Setting_Tab_Woo extends AFSA_Setting_Tab {

	public function init() {

		$this->add_section(
			'afsa_woocommerce_section',
			__( 'WooCommerce Settings', 'afsanalytics' ),
			function() {
					// optional description
			}
		);

				/*
				unused / deprecated
		$this->add_checkboxes(
			array(
				'woocommerce_usertracking' => __( 'Track customer profile', 'afsanalytics' ),
			)
		);
				*/

		add_settings_field(
			'woocommerce_shop_affiliation',
			__( 'Default sales Chanel', 'afsanalytics' ),
			function() {
					print '<input type="text"   '
					. $this->input_name( 'woocommerce_shop_affiliation' )
					. 'value=' . (
											   empty( $this->settings['woocommerce_shop_affiliation'] ) ?
												'' :
												'WooCommerce'
												) . '>'
					. '<p class=afsa_help>' .
					__(
						'The name of default the sale channel you want to be shown as origin of any order processed by WooCommerce.',
						'afsanalytics'
					)
					. '</p>';
			},
			$this->page,
			$this->section_id
		);
	}

}
