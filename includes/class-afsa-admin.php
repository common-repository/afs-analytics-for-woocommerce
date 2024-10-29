<?php

defined( 'ABSPATH' ) || exit;


require_once AFSA_INCLUDES_DIR . '/controllers/settings/class-afsa-setting-page.php';
require_once AFSA_INCLUDES_DIR . '/controllers/class-afsa-dashboard-page.php';
require_once AFSA_INCLUDES_DIR . '/ui/class-afsa-post-title.php';
require_once AFSA_INCLUDES_DIR . '/controllers/renderer/class-afsa-admin-widget-renderer.php';
require_once AFSA_INCLUDES_DIR . '/account/class-afsa-account-controller.php';

class AFSA_Admin {

	public static function init() {

		add_action( 'edit_form_after_title', array( 'AFSA_Post_Title_Field', 'on_edit_form_after_title' ), 10, 1 );
		add_action( 'save_post', array( 'AFSA_Post_Title_Field', 'on_save_post' ), 10, 2 );

		add_action( 'admin_head', array( 'AFSA_Tracker', 'on_admin_header_rendered' ) );
		add_action( 'admin_footer', array( 'AFSA_Tracker', 'on_admin_footer_rendered' ) );

		add_action( 'wp_admin_enqueue_scripts', array( 'AFSA_Tracker', 'on_enqueue_scripts' ) );

		add_action( 'wp_dashboard_setup', array( 'AFSA_Admin', 'on_wp_dashboard_setup' ) );
	}

	public static function on_wp_dashboard_setup() {
		if ( ! \AFSA_Admin_Widget_Renderer::should_display() ) {
			return;
		}

		$widget_id = 'afsa_dashboard_widget';

		wp_add_dashboard_widget(
			$widget_id, // Widget slug.
			esc_html__( 'AFS Analytics', 'afsa_text' ), // Title.
			function() {
					$rdr = new \AFSA_Admin_Widget_Renderer();
					print $rdr->render();
			}
		);

		// Moving widget to top
		//
		// https://developer.wordpress.org/apis/handbook/dashboard-widgets/#forcing-your-widget-to-the-top
		// 'Unfortunately this only works for people who have never re-ordered their widgets. '

		global $wp_meta_boxes;

		// Get the regular dashboard widgets array
		// (which already has our new widget but appended at the end).
		$default_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

		// Backup and delete our new dashboard widget from the end of the array.
		$example_widget_backup = array( $widget_id => $default_dashboard[ $widget_id ] );
		unset( $default_dashboard[ $widget_id ] );

		// Merge the two arrays together so our widget is at the beginning.
		$sorted_dashboard = array_merge( $example_widget_backup, $default_dashboard );

		// Save the sorted array back into the original metaboxes.
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}

	public static function render_admin_widget() {

	}

	// Early redirect to make sure to handle redirect before anything else
	public static function ensure_early_redirects() {

		AFSA_Account_Form_Renderer::redirect_on_account_linked();

		$page = empty( $_GET['page'] ) ?
				null :
				sanitize_text_field( $_GET['page'] );

		switch ( $page ) {

			case AFSA_MENU_PAGE_ACCOUNT_MANAGER:
				if ( AFSA_Config::afsa_enabled() ) {
					AFSA_Dashboard_Renderer::init_api();
				}
				AFSA_Account_Controller::get()->on_action_completed();
				break;

			case AFSA_MENU_PAGE_UPGRADE_ID:
				static::afsa_upgrade_redirect();
				break;

			case AFSA_MENU_PAGE_DASHBOARD_EXTRA_ID:
				static::afsa_dboard_redirect();
				break;

			case AFSA_MENU_PAGE_DASHBOARD_ID:
				if ( AFSA_Config::afsa_enabled() ) {
					AFSA_Dashboard_Renderer::init_api();
				}
				break;
		}
	}

	public static function on_enqueue_scripts( $hook ) {

		// ADMIN DASHBOARD
		if ( $hook == AFSA_MENU_PAGE_ADMIN_DASHBOARD ) {

			add_action( 'wp_dashboard_setup', array( 'AFSA_Admin', 'on_wp_dashboard_setup' ) );

			\AFSA_Admin_Widget_Renderer::action_enqueue_scripts();
		}

		// AFSA EMBEDDED DASHBOARD
		elseif ( strpos( $hook, AFSA_MENU_PAGE_DASHBOARD_ID ) ) {

			if ( AFSA_Config::afsa_enabled() ) {

				AFSA_Dashboard_Renderer::action_enqueue_scripts();
			} else {
				AFSA_Intro_Renderer::action_enqueue_scripts();
			}
		}

		// ACCOUNT MANAGER
		elseif ( strpos( $hook, AFSA_MENU_PAGE_ACCOUNT_MANAGER ) ) {
			wp_enqueue_style(
				'afsa_welcome',
				AFSA_Config::get_url( 'css/welcome.css' )
			);
		}

		// AFSA DASHBOARD DEMO
		elseif ( strpos( $hook, AFSA_MENU_PAGE_DASHBOARD_DEMO_ID ) ) {

			AFSA_Dashboard_Renderer::action_enqueue_scripts();
		}
		// SETTINGS page
		elseif ( strpos( $hook, AFSA_MENU_PAGE_SETTINGS_ID ) ) {
			wp_enqueue_style(
				'afsa_dashboard_local',
				AFSA_Config::get_url( 'css/settings.css' )
			);

			wp_enqueue_script(
				'afsa_setting_script',
				AFSA_Config::get_url( 'js/intro.js' ),
				array( 'jquery' )
			);
		}
	}

	public static function render_menu() {

		AFSA_Setting_Page::create(); // NEEDED to register settings

		$render_setting_cb = array( 'AFSA_Setting_Page', 'render' );

		$is_account_set = AFSA_Config::afsa_enabled();

		$dashboard_cb = $is_account_set ?
				array( 'AFSA_Dashboard_Page', 'render' ) :
				array( 'AFSA_Dashboard_Page', 'render_intro' );

		add_menu_page(
			__( 'AFS Analytics - Dashboard', 'afsanalytics' ), // page title
			__( 'AFS Analytics', 'afsanalytics' ), // menu title
			'manage_options', // perms
			AFSA_MENU_PAGE_DASHBOARD_ID, // id
			$dashboard_cb,
			AFSA_Config::get_url( 'assets/images/icon.small.png' ),
			2 // dashboar section
		);

		add_submenu_page(
			AFSA_MENU_PAGE_DASHBOARD_ID, // parent_id
			__( 'AFS Analytics - Plugin settings', 'afsanalytics' ),
			__( 'Plugin settings', 'afsanalytics' ),
			'manage_options',
			AFSA_MENU_PAGE_SETTINGS_ID,
			$render_setting_cb
		);

		add_options_page(
			__( 'AFS Analytics - Plugin settings', 'afsanalytics' ),
			__( 'AFS Analytics', 'afsanalytics' ),
			'manage_options',
			AFSA_MENU_PAGE_SETTINGS_ID,
			$render_setting_cb
		);

		if ( $is_account_set ) {

			$account = AFSA_Account_Manager::get()->get_current();

			if ( $account->is_free() ) {
				add_submenu_page(
					AFSA_MENU_PAGE_DASHBOARD_ID, // parent_id
					__( 'AFS Analytics - Upgrade', 'afsanalytics' ),
					__( 'Upgrade Account', 'afsanalytics' ),
					'manage_options',
					AFSA_MENU_PAGE_UPGRADE_ID,
					array( 'AFSA_Admin', 'afsa_upgrade_redirect' )
				);
			}

			add_submenu_page(
				AFSA_MENU_PAGE_DASHBOARD_ID, // parent_id
				__( 'AFS Analytics - Online Dashboard', 'afsanalytics' ),
				__( 'View extra reports on AFSAnalytics.com online dashboard', 'afsanalytics' ),
				'manage_options',
				AFSA_MENU_PAGE_DASHBOARD_EXTRA_ID,
				array( 'AFSA_Admin', 'afsa_dboard_redirect' )
			);
		} else {

		}

		add_submenu_page(
			null, // parent_id
			__( 'AFS Analytics - Account manager', 'afsanalytics' ),
			__( 'account_manager', 'afsanalytics' ),
			'manage_options',
			AFSA_MENU_PAGE_ACCOUNT_MANAGER,
			array( AFSA_Account_Controller::get(), 'render' )
		);

		if ( ! $is_account_set ) {
			add_submenu_page(
				AFSA_MENU_PAGE_DASHBOARD_ID,
				__( 'AFS Analytics - Demo', 'afsanalytics' ),
				__( 'Dashboard Demo', 'afsanalytics' ),
				'manage_options',
				AFSA_MENU_PAGE_DASHBOARD_DEMO_ID,
				array( 'AFSA_Dashboard_Page', 'run_demo' )
			);
		}

		add_submenu_page(
			AFSA_MENU_PAGE_DASHBOARD_ID, // parent_id
			__( 'AFS Analytics - Contact Us', 'afsanalytics' ),
			__( 'Contact us', 'afsanalytics' ),
			'manage_options',
			AFSA_MENU_PAGE_SUPPORT,
			array( 'AFSA_Admin', 'afsa_contact_redirect' )
		);
	}

	public static function afsa_dboard_redirect() {
		wp_redirect(
			AFSA_Route_Manager::get_dashboard_url(
				'dashboard.php',
				array()
				// array( 'utm_medium' => 'admin_menu_dashboard' )
			)
		);
	}

	public static function afsa_upgrade_redirect() {
		wp_redirect(
			AFSA_Route_Manager::get_dashboard_url(
				'pricing.php',
				array()
				// array( 'utm_medium' => 'admin_menu_upgrade' )
			)
		);
	}

	public static function afsa_contact_redirect() {
		wp_redirect(
			AFSA_Route_Manager::get_dashboard_url(
				'contact.html',
				array()
				// array( 'utm_medium' => 'admin_menu_upgrade' )
			)
		);
	}

}
