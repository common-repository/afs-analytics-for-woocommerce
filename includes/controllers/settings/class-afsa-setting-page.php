<?php

require_once 'tabs/class-afsa-setting-tab.php';
require_once 'tabs/class-afsa-setting-tab-main.php';
require_once 'tabs/class-afsa-setting-tab-privacy.php';
require_once 'tabs/class-afsa-setting-tab-autotrack.php';
require_once 'tabs/class-afsa-setting-tab-woo.php';



/*
  https://wordpress.stackexchange.com/questions/139660/error-options-page-not-found-on-settings-page-submission-for-an-oop-plugin
 */

class AFSA_Setting_Page {

	static $page;

	public static function create() {
		if ( ! static::$page ) {
			static::$page = new static();
		}

		return static::$page;
	}

	public static function render() {
		static::create()->renderContent();
	}

	public $name = 'afsa_setting_page';
	public $selected_tab;
	public $tabs;







	public function __construct() {

		$this->tabs = array(
			'afsa_tab_main'      => __( 'Main Settings', 'afsanalytics' ),
			'afsa_tab_autotrack' => __( 'Advanced Settings', 'afsanalytics' ),
			'afsa_tab_privacy'   => __( 'Privacy', 'afsanalytics' ),
		);

		if ( AFSA_Config::woocommerce_enabled() ) {
			$this->tabs['afsa_tab_woo'] = 'ECommerce';
		}

		register_setting( 'afsa_settings_main', 'afsa_settings_main' );
		register_setting( 'afsa_settings_privacy', 'afsa_settings_privacy' );
		register_setting( 'afsa_settings_autotrack', 'afsa_settings_autotrack' );
		register_setting( 'afsa_settings_woo', 'afsa_settings_woo' );

		$tab_id = empty( $_GET['afsa_tab'] ) ?
				'afsa_tab_main' :
				sanitize_text_field( $_GET['afsa_tab'] );

		$class              = $this->class_from_tab_id( $tab_id );
		$this->selected_tab = new $class(
			$this->name,
			$tab_id
		);
	}

	public function class_from_tab_id( $id ) {

		$classes = array(
			'afsa_tab_main'      => 'AFSA_Setting_Tab_Main',
			'afsa_tab_privacy'   => 'AFSA_Setting_Tab_Privacy',
			'afsa_tab_autotrack' => 'AFSA_Setting_Tab_Autotrack',
			'afsa_tab_woo'       => 'AFSA_Setting_Tab_Woo',
		);

		return empty( $classes[ $id ] ) ?
				'AFSA_Setting_Tab_Main' :
				$classes[ $id ];
	}

	// FORM RENDERING

	public function renderContent() {
		// moved from __construct() to avoid user check firing when just registering settings
				// (when called from class AFSA_Admin )

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		print '<h1>' . __( 'AFS Analytics settings', 'afsanalytics' ) . '</h1>';

		$this->render_tabs();

		print '<form class=afsa_settings_form action="options.php" method="post">';

		settings_fields( $this->selected_tab->settings_group );
		do_settings_sections( $this->name );
		submit_button();

		print '</form>';
	}

	private function render_tabs() {

		$active_tab = $this->selected_tab->id;
		print '<h2 class="nav-tab-wrapper">';

		foreach ( $this->tabs as $tab => $label ) {

			print '<a href="?page=' . sanitize_text_field( $_GET['page'] ) . '&afsa_tab=' . $tab
					. '" class="nav-tab '
					. ( $active_tab === $tab ? 'nav-tab-active' : '' )
					. '">'
					. $label
					. '</a>';
		}

		print '</h2>';
	}

}
