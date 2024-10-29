<?php

require_once 'class-afsa-settings.php';


define( 'AFSA_OAUTH_STATE', 'afsa_oauth_state' );
define( 'AFSA_OAUTH_CB_URL', 'afsa_oauth_cb_url' );
define( 'AFSA_REQUEST_STATE', 'afsa_request_state' );

define( 'AFSA_ECOMMERCE_UNSUPPORTED', 0 );
define( 'AFSA_ECOMMERCE_BASIC', 1 );
define( 'AFSA_ECOMMERCE_ADVANCED', 2 );

class AFSA_Config {

	const DEMO_ACCOUNT_ID = 'WPDEMO';

	public static $tr                  = array();
	public static $data_store          = array();
	public static $log_enabled         = true;
	public static $woocommerce_enabled = false;
	public static $plugin_base_url;
	private static $demo_mode_enabled = false;

	public static function CMS() {
		return static::$woocommerce_enabled ?
			'woocommerce' :
			'WordPress';
	}

	public static function CMS_version() {
		return get_bloginfo( 'version' );
	}

	public static function woo_version() {
		if ( static::$woocommerce_enabled ) {

			try {
				return (float) WC()->version;
			} catch ( \Exception $e ) {
			}
		}
		return null;
	}

	public static function is_woo_version( $version ) {
		$woo_version = static::woo_version();

		return $woo_version ?
			version_compare( $woo_version, $version, '>=' ) :
			true;
	}

	public static function plugin_name() {
		return 'afsanalytics';
	}

	public static function is_debug() {
		return defined( 'AFSA_DEBUG_MODE' ) && ! empty( AFSA_DEBUG_MODE );
	}

	public static function is_demo() {
		return static::$demo_mode_enabled;
	}

	public static function set_demo_mode( $b = true ) {
		static::$demo_mode_enabled = $b;
	}

	public static function is_log_enabled() {
		return static::$log_enabled && static::is_debug();
	}

	public static function is_gravatar_enabled() {
		return ! empty( static::get_option( 'gravatar_profile_enabled' ) );
	}

	public static function is_ajax() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		if ( function_exists( 'is_ajax' ) ) {
			return is_ajax();
		}

		try {
			$headers = AFSA_Tools::get_all_headers();
			if ( ! empty( $headers['X-Requested-With'] ) && $headers['X-Requested-With'] == 'XMLHttpRequest' ) {
				return true;
			}
		} catch ( Exception $ex ) {
		}

		return false;
	}

	// INFOS


	public static function get_lng() {
		$p = explode( '-', str_replace( '_', '-', get_bloginfo( 'language' ) ) );
		return $p[0];
	}

	public static function get_infos_manager() {
		return AFSA_Infos_Manager::get();
	}

	// URLS

	public static function get_afsa_home() {
		return 'https://www.afsanalytics.com';
	}

	public static function get_afsa_api_home() {
		return 'https://api.afsanalytics.com';
	}

	public static function get_img_server_url() {
		return static::get_afsa_api_home();
	}

	// root URL for plugin files
	public static function plugin_url( $u = null ) {
		return static::$plugin_base_url . ( $u ? $u : '' );
	}

	public static function get_config_controller_url() {
		return admin_url( 'admin.php?page=' . AFSA_MENU_PAGE_SETTINGS_ID );
	}

	public static function get_dashboard_demo_url() {
		return admin_url( 'admin.php?page=' . AFSA_MENU_PAGE_DASHBOARD_DEMO_ID );
	}

	public static function get_dashboard_url() {
		return admin_url( 'admin.php?page=' . AFSA_MENU_PAGE_DASHBOARD_ID );
	}

	public static function get_account_manager_url() {
		return admin_url( 'admin.php?page=' . AFSA_MENU_PAGE_ACCOUNT_MANAGER );
	}

	public static function get_admin_url() {
		return admin_url();
	}

	public static function get_url( $u = null ) {
		return static::plugin_url( $u );
	}

	public static function get_page_name() {
		$p = AFSA_Infos_Manager::get()->page();
		return $p->get_name();
	}

	public static function get_page_categories() {
		$category = get_queried_object();
		if ( $category && is_array( $category ) ) {
			foreach ( array( 'label', 'name' ) as $field ) {
				if ( ! empty( $category[ $field ] ) ) {
					return $category[ $field ];
				}
			}
		}

		return null;
	}

	public static function is_back_office() {
		return is_admin();
	}

	public static function is_employee() {
		$current_user = wp_get_current_user();
		if ( user_can( $current_user, /* 'administrator' */ 'edit_pages' ) ) {
			return true;
		}
		return false;
	}

	public static function get_shop_name() {
		return get_bloginfo( 'name' );
	}

	public static function get_shop_affiliation() {
		return sanitize_text_field( static::get_option( 'woocommerce_shop_affiliation', 'WooCommerce' ) );
	}

	public static function get_access_key() {
		return trim( sanitize_text_field( static::get_option( 'accesskey' ) ) );
	}

	public static function get_account_id() {
		return static::is_demo() ?
			static::DEMO_ACCOUNT_ID :
			sanitize_text_field( static::get_option( 'account_id' ) );
	}

	public static function woocommerce_enabled() {
		return static::$woocommerce_enabled;
	}

	public static function get_ecommerce_level() {
		if ( static::woocommerce_enabled() ) {

			if ( static::is_demo() ) {
				return AFSA_ECOMMERCE_ADVANCED;
			}

			$account = AFSA_Account_Manager::get()->get_current();
			if ( $account ) {

				if ( $account->advanced_ecom_enabled() ) {
					return AFSA_ECOMMERCE_ADVANCED;
				}

				if ( $account->ecom_enabled() ) {
					return AFSA_ECOMMERCE_BASIC;
				}
			}

			return AFSA_ECOMMERCE_ADVANCED;
		}

		return AFSA_ECOMMERCE_UNSUPPORTED;
	}

	public static function get_paa_rc() {
		$options = get_option( 'afsa_paa_rc' );
		return empty( $options['id'] ) ?
			null :
			$options['id'];
	}

	public static function advanced_ecommerce_enabled() {
		return static::get_ecommerce_level() == AFSA_ECOMMERCE_ADVANCED;
	}

	public static function basic_ecommerce_enabled() {
		return static::get_ecommerce_level() == AFSA_ECOMMERCE_BASIC;
	}

	public static function anonymize_members() {
		return static::get_option( 'afsa_anon_user_infos', false );
	}

	public static function get_global_currency() {
		return static::get_global_currency_code();
	}

	public static function get_global_currency_code() {

		if ( function_exists( 'get_woocommerce_currency' ) ) {
			return strtolower( get_woocommerce_currency() );
		}

		return null;
	}

	/**
	 * Check if we have a valid AFSA account number
	 *
	 * @return bool
	 */
	public static function afsa_enabled() {
		return static::validate_account_id( static::get_account_id() );
	}

	public static function validate_account_id( $id ) {
		return AFSA_Account_Manager::validate_id( $id );
	}

	// DATA STORE (    unused ATM )

	/**
	 * set data store value
	 *
	 * @param string $k key
	 * @param mixed  $v initial value
	 */
	public static function set( $k, $v ) {
		static::$data_store[ $k ] = $v;
	}

	/**
	 * get data stored value
	 *
	 * @param string $k key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get( $k, $default = null ) {
		return empty( static::$data_store[ $k ] ) ? $default : static::$data_store[ $k ];
	}

	// SETTINGS / OPTIONS



	public static function get_option_group( $key ) {
		return AFSA_Settings::manager()->get_group( $key );
	}

	public static function get_option( $key, $def = null ) {
		return AFSA_Settings::manager()->get( $key, $def );
	}

	public static function get_page_title_detect_method() {
		// TODO ?
		// return static::get_option('afsa_page_name_method');
		return null;
	}

	public static function save_account_id( $id ) {
		AFSA_Account_Manager::get()->set_current_id( $id );
	}

	// TRACKER OPTIONS

	public static function should_track() {
		if (
			static::is_ajax() ||
			static::is_demo() ||
			! static::afsa_enabled()
		) {
			return false;
		}

		if ( static::is_back_office() ) {
			return self::track_admin_pages() ||
				self::are_admin_tracking_infos_available();
		}

		return true;
	}

	public static function track_admin_pages() {
		return ! empty( static::get_option( 'admin_pages_tracking' ) );
	}

	public static function get_autotrack_option( $key ) {
		return ! empty( static::get_option( 'autotrack_' . $key ) );
	}

	public static function get_autotrack_all_option() {
		return static::get_option( 'autotrack_all', AFSA_AUTOTRACK_ON );
	}

	public static function get_autotrack_option_array() {
		return static::get_option_group( 'autotrack' );
	}

	// REFUNDED ORDERS

	public static function get_refunded_orders() {
		return get_option( AFSA_OPTION_SAVED_REFUND, array() );
	}

	public static function set_refunded_order( $order_id, $refund_id ) {

		$key   = (string) $order_id;
		$value = (int) $refund_id;

		if ( empty( $key ) || empty( $value ) ) {
			return false;
		}

		$saved_refunds = static::get_refunded_orders();

		if ( empty( $saved_refunds[ $key ] ) ) {

			$saved_refunds[ $key ] = array( (int) $value );
		} elseif ( ! in_array( (int) $value, $saved_refunds[ $key ] ) ) {

			$saved_refunds[ $key ][] = (int) $value;
			sort( $saved_refunds[ $key ] );
		} else {
			return false;
		}

		update_option( AFSA_OPTION_SAVED_REFUND, $saved_refunds );
		return true;
	}

	public static function reset_refunded_orders() {
		update_option( AFSA_OPTION_SAVED_REFUND, array() );
	}

	// do we have some saved infos that need to be sent
	public static function are_admin_tracking_infos_available() {

		if (
			! empty( static::get_refunded_orders() ) ||
			! empty( static::get_updated_order_status() )
		) {
			return true;
		}

		return false;
	}

	// ORDER Status update

	public static function get_updated_order_status() {
		return get_option( AFSA_OPTION_UPDATED_ORDER_STATUS, array() );
	}

	public static function set_updated_order_status( $order_id, $status ) {

		$key = (string) $order_id;

		if ( empty( $key ) || empty( $status ) ) {
			return false;
		}

		$orders         = static::get_updated_order_status();
		$orders[ $key ] = $status;

		update_option( AFSA_OPTION_UPDATED_ORDER_STATUS, $orders );
		return true;
	}

	public static function reset_updated_order_status() {
		update_option( AFSA_OPTION_UPDATED_ORDER_STATUS, array() );
	}

	// VARIATION

	public static function use_variation_sku() {
		return true;
	}

	// UTILS




	public static function get_int_option( $key ) {
		return (int) static::get_option( $key );
	}

	public static function get_string_option( $key ) {
		return (string) static::get_option( $key );
	}

	// URLS

	public static function get_current_sheme() {
		return is_ssl() ? 'https' : 'http';
	}

	public static function get_current_url() {
		return sanitize_url( static::get_current_sheme() . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	}

	public static function get_ajax_server_url() {
		return admin_url( 'admin-ajax.php' );
	}

	// OAUTH

	/**
	 * Save Oauth callback url as we need
	 * to resent the exact same url when requesting
	 * access token from a received auth code
	 *
	 * @param string $u callback url
	 */
	public static function save_oauth_callback_url( $u ) {
		update_option( AFSA_OAUTH_CB_URL, $u );
	}

	public static function get_oauth_callback_url() {
		return get_option( AFSA_OAUTH_CB_URL );
	}

	public static function save_oauth_state( $state ) {
		update_option( AFSA_OAUTH_STATE, $state );
	}

	public static function get_oauth_state() {
		return get_option( AFSA_OAUTH_STATE );
	}

	public static function get_oauth_server_url() {
		return static::get_afsa_api_home() . '/v1/';
	}

	public static function get_oauth_client_id() {
		return 'afsa_wordpress_plugin';
	}

	// ACCOUNT

	public static function save_request_state( $state ) {
		update_option( AFSA_REQUEST_STATE, $state );
	}

	public static function get_request_state() {
		return get_option( AFSA_REQUEST_STATE );
	}
}
