<?php

class AFSA_Site_Infos extends AFSA_Infos {

	public function retrieve() {

		$locale      = get_bloginfo( 'language' );
		$lng         = explode( '-', $locale )[0];
		$currency    = AFSA_Config::get_global_currency_code();
		$woo_version = AFSA_Config::woo_version();

		$this->data = array(
			'name'           => esc_js( get_bloginfo( 'name' ) ),
			'desc'           => esc_js( get_bloginfo( 'description' ) ),
			'url'            => site_url(),
			'domain'         => parse_url( site_url(), PHP_URL_HOST ),
			'lng'            => $lng,
			'email'          => esc_js( get_bloginfo( 'admin_email' ) ),
			'cms'            => AFSA_Config::CMS(),
			'wp_version'     => AFSA_Config::CMS_version(),
			'plugin_version' => AFSA_MODULE_VERSION,
			'tz'             => get_option( 'timezone_string' ),
		);

		if ( $woo_version ) {
			$this->data['woo_version'] = (string) $woo_version;
		}

		if ( $currency ) {
			$this->data['currency'] = $currency;
		}

		return $this->data;
	}

}
