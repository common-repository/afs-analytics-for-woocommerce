<?php

define( 'AFSA_SETTINGS_PREFIX', 'afsa_settings_' );

class AFSA_Settings {

	public $options = null;
	public $default_settings;
	public $groups = array(
		'main',
		'autotrack',
		'privacy',
		'woo',
	);
	private static $instance;

	public static function manager() {
		return static::$instance ?: static::$instance = new static();
	}

	public function __construct() {
		if ( $this->options ) {
			return $this->options;
		}

		$this->init_default_settings();
		foreach ( $this->groups as $group ) {
			$this->init_group_options( $group );
		}
	}

	public function init_default_settings() {

		return $this->default_settings = array(
			'main'      => array(
				'account_id'               => get_option( 'afs_account', 0 ),
				'accesskey'                => get_option( 'afs_accesskey' ),
				'admin_pages_tracking'     => get_option( 'afs_admin_pages_tracking', 1 ),
				'self_visits_hidden'       => get_option( 'afs_self_visits_hidden', 0 ),
				'user_logged_tracking'     => get_option( 'afs_user_logged_tracking', 1 ),
				'display_admin_summary'    => get_option( 'afs_udisplay_admin_summary', 1 ),
				'gravatar_profile_enabled' => get_option( 'afs_gravatar_profile_enabled', 0 ),
			),
			'autotrack' => array(
				'autotrack_all'      => $this->get_old_autotrack_setting( 'afs_autotrack_all', AFSA_AUTOTRACK_ON ),
				'autotrack_outbound' => $this->get_old_autotrack_setting( 'afs_autotrack_outbound', AFSA_AUTOTRACK_ON ),
				'autotrack_inside'   => $this->get_old_autotrack_setting( 'afs_autotrack_inside', AFSA_AUTOTRACK_ON ),
				'autotrack_download' => $this->get_old_autotrack_setting( 'afs_autotrack_download', AFSA_AUTOTRACK_ON ),
				'autotrack_video'    => $this->get_old_autotrack_setting( 'afs_autotrack_video', AFSA_AUTOTRACK_ON ),
				'autotrack_iframe'   => $this->get_old_autotrack_setting( 'afs_autotrack_iframe', AFSA_AUTOTRACK_OFF ),
			),
			'privacy'   => array(
				'cookie_setting'       => get_option( 'afs_cookie_setting' ),
				'ip_setting'           => get_option( 'afs_ip_setting' ),
				'user_consent'         => get_option( 'afs_user_consent' ),
				'localization_setting' => get_option( 'afs_localization_setting' ),
			),
			'woo'       => array(
				/*
								 unused / deprecated
				'woocommerce_usertracking'     => get_option( 'afs_woocommerce_usertracking', 1 ),
								 */
				'woocommerce_shop_affiliation' => 'WooCommerce',
			),
		);
	}

	// convert old autotrack setting
	/*
	 OLD
	 * 0 => off
	 * 1 => Dataset
	 * 2 => on
	 */
	private function get_old_autotrack_setting( $key, $default ) {
		$ret = (int) get_option( $key, -1 );
		if ( $ret == -1 ) {
			return $default;
		}

		$convert = array( AFSA_AUTOTRACK_OFF, AFSA_AUTOTRACK_DATASET, AFSA_AUTOTRACK_ON );
		return $convert[ $ret ];
	}

	public function init_group_options( $group ) {

		$this->options[ $group ] = get_option(
			AFSA_SETTINGS_PREFIX . $group,
			$this->default_settings[ $group ]
		);

		/*
		  if ($group === 'autotrack') {
		  print '<pre style="margin:50px">';
		  print_r($group);
		  print_r($this->default_settings[$group]);
		  print_r($this->options[$group]);
		  print '</pre>';
		  } */
	}



	public function get_group( $name ) {
		$key = str_replace( AFSA_SETTINGS_PREFIX, '', $name );
		return empty( $this->options[ $key ] ) ?
				array() :
				$this->options[ $key ];
	}

	public function get( $name, $def = null ) {
		if ( ! empty( $this->options ) ) {
			foreach ( $this->options as $group ) {
				if ( ! empty( $group ) ) {
					foreach ( $group as $key => $value ) {
						if ( $key === $name ) {
							return $value;
						}
					}
				}
			}
		}

		return $def;
	}

	public function get_default_for( $group ) {
		return empty( $this->default_settings[ $group ] ) ?
				array() :
				$this->default_settings[ $group ];
	}

}
