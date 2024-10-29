<?php

/*
  Plugin Name: AFS Analytics for WooCommerce
  Plugin URI: https://www.afsanalytics.com/
  Description: Advanced eCommerce Analytics solution. Grow your online business by measuring user satisfaction and site efficiency.
  Version: 2.18
  Author: AFS Analytics
  Author URI: https://www.afsanalytics.com/
  Text Domain: afsanalytics
  Domain Path: /languages
  WC requires at least: 1.4.1
  WC tested up to: 8.0.2
 */




defined( 'ABSPATH' ) || exit;



require_once __DIR__ . '/includes/defines.php';

require_once __DIR__ . '/includes/class-afsa-tools.php';
require_once __DIR__ . '/includes/config/class-afsa-config.php';
require_once __DIR__ . '/includes/ajax/class-afsa-ajax.php';
require_once __DIR__ . '/includes/infos/class-afsa-infos-manager.php';
require_once __DIR__ . '/includes/class-afsa-tracker.php';
require_once __DIR__ . '/includes/class-afsa-admin.php';
require_once __DIR__ . '/includes/account/class-afsa-account-manager.php';



if ( ! class_exists( 'AFSA_Core_Stats_Plugin' ) ) :


	define( 'AFSA_DEBUG_MODE', false );
	define( 'AFSA_MODULE_VERSION', '2.0.18' );

	class AFSA_Core_Stats_Plugin {

		public function __construct() {

			AFSA_Config::$plugin_base_url = plugin_dir_url( __FILE__ );

			load_plugin_textdomain( 'afsanalytics', false, basename( dirname( __FILE__ ) ) . '/languages' );

			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );

			add_action( 'admin_init', array( 'AFSA_Admin', 'init' ) );
			add_action( 'admin_menu', array( 'AFSA_Admin', 'render_menu' ) );
			add_action( 'admin_enqueue_scripts', array( 'AFSA_Admin', 'on_enqueue_scripts' ), 10, 1 );

			add_action( 'wp_enqueue_scripts', array( 'AFSA_Tracker', 'on_enqueue_script' ) );
			add_action( 'wp_head', array( 'AFSA_Tracker', 'on_header_rendered' ) );
			add_action( 'wp_footer', array( 'AFSA_Tracker', 'on_footer_rendered' ) );

			// ajax requests
			add_action( 'wp_ajax_afsa_stats_server', array( 'AFSA_Ajax', 'stats_server' ) );
		}

		public function on_plugins_loaded() {

			if (
					in_array(
						'woocommerce/woocommerce.php',
						apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
					)
			) {
				AFSA_Config::$woocommerce_enabled = true;
				require_once __DIR__ . '/includes/woo/class-afsa-woo-hooks.php';
				AFSA_WOO_Hooks::init();
			}

			AFSA_Admin::ensure_early_redirects();
		}



		// INSTALL / UNINSTALL

		public static function activate() {
			AFSA_DB::get()->create_tables();
		}

		public static function deactivate() {
			AFSA_DB::get()->drop_tables();
			AFSA_OAuth_Token::clear();
		}

	}

	register_activation_hook( __FILE__, array( 'AFSA_Core_Stats_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'AFSA_Core_Stats_Plugin', 'deactivate' ) );


	$AFSA_Core_Stats_Plugin = new AFSA_Core_Stats_Plugin();





endif;



