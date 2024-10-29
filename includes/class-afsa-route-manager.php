<?php

/**
 * Build different URLs to Dashboard
 * and other pages on AFSAnalytics.com
 *
 * Insert Access Key if set
 */
class AFSA_Route_Manager {

	public static $host = 'https://www.afsanalytics.com';

	public static function get_dashboard_url( $u = '', $extra = array() ) {
		/*
		 $args = array(
		  'utm_source'   => strtolower( AFSA_Config::CMS() ),
		  'utm_campaign' => 'plugin',
		  'utm_medium'   => 'backoffice',
		  );
		 */

		$args = array();

		foreach ( $extra as $k => $v ) {
			$args[ $k ] = $v;
		}

		$paa = AFSA_Config::get_paa_rc();

		if ( $paa ) {
			$args['paa_rc'] = $paa;
		}

		$access_key = AFSA_Config::get_access_key();
		if ( $access_key ) {
			$args['accesskey'] = $access_key;
		} elseif ( AFSA_Config::get_account_id() ) {
			$args['usr'] = AFSA_Config::get_account_id();
		}

		return add_query_arg( $args, static::$host . '/' . $u );
	}

	public static function get_dashboard_page( $w = null ) {
		$arr = array(
			'rightnow'       => 'rightnow.php',
			'lastvisitors'   => 'lastvisitors.php',
			'heatmaps'       => 'heatmaps.php',
			'keywordchecker' => 'keywords_monitoring.php',
			'pdf'            => 'edpdf.php',
		);

		if ( ! empty( $arr[ $w ] ) ) {
			return static::get_dashboard_url( $arr[ $w ] );
		}

		if ( empty( $w ) ) {
			$w = 'dashboard.php';
		}

		return static::get_dashboard_url( $w );
	}

	// Various URLS


	public static function keys() {
		return static::get_dashboard_url( 'accesskeys.php' );
	}

	public static function profile() {
		return static::get_dashboard_url( 'edprofile.php' );
	}

	public static function options() {
		return static::get_dashboard_url( 'edaccounts.php' );
	}

	public static function password() {
		return static::get_dashboard_url( '', array( 'lostpass' => 1 ) );
	}

	public static function upgrade() {
		return static::get_dashboard_url( 'pricing.php' );
	}

	public static function onlineHelp() {
		return static::get_dashboard_url( 'articles/web-statistics-reports/' );
	}

	public static function contact() {
		return static::get_dashboard_url( 'contact.html' );
	}

}
