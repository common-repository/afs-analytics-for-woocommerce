<?php

if ( ! defined( 'AFSA_INCLUDES_DIR' ) ) {

	define( 'AFSA_INCLUDES_DIR', __DIR__ );



	define( 'AFSA_MENU_PAGE_SETTINGS_ID', 'afsa_settings_page' );
	define( 'AFSA_MENU_PAGE_DASHBOARD_DEMO_ID', 'afsa_demo_dashboard_page' );
	define( 'AFSA_MENU_PAGE_DASHBOARD_ID', 'afsa_dashboard' );
	define( 'AFSA_MENU_PAGE_DASHBOARD_EXTRA_ID', 'afsa_dashboard_extra' );
	define( 'AFSA_MENU_PAGE_UPGRADE_ID', 'afsa_upgrade' );
	define( 'AFSA_MENU_PAGE_ADMIN_DASHBOARD', 'index.php' );
	define( 'AFSA_MENU_PAGE_ACCOUNT_MANAGER', 'afsa_account_manager' );
	define( 'AFSA_MENU_PAGE_SUPPORT', 'afsa_support' );


	define( 'AFSA_OPTION_SAVED_REFUND', 'afsa_saved_refunds' );
	define( 'AFSA_OPTION_UPDATED_ORDER_STATUS', 'afsa_updated_orders' );
	define( 'AFSA_OPTION_OAUTH_TOKEN', 'afsa_analytics_oauth_token' );



	define( 'AFSA_TRACKER_CHEKOUT_STEP_VIEW_CART', 1 );
	define( 'AFSA_TRACKER_CHEKOUT_STEP_ORDER_FORM', 2 );
	define( 'AFSA_TRACKER_CHEKOUT_STEP_ORDER_CONFIRMATION', 3 );

	define( 'AFSA_AUTOTRACK_DATASET', 0 );
	define( 'AFSA_AUTOTRACK_ON', 1 );
	define( 'AFSA_AUTOTRACK_OFF', 2 );
}
