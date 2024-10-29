<?php

class AFSA_Setting_Tab {

	public $id;
	public $section_id = 'default';
	public $label;
	public $page;
	public $settings = array();
	public $settings_group;

	public function __construct( $page, $id ) {

		$this->id       = $id;
		$this->page     = $page;
		$this->settings = array();

		$this->settings_group = str_replace( 'tab', 'settings', $id );

		$this->settings = AFSA_Config::get_option_group( $this->settings_group );

		$this->init();
	}

	// HELPERS

	public function option_value( $field ) {
		return isset( $this->settings[ $field ] ) ?
			$this->settings[ $field ] :
			null;
	}

	// SECTION

	public function add_section( $section_id, $label, $cb ) {

		$this->section_id = $section_id;
		add_settings_section(
			$section_id,
			( empty( $label ) ? '' : __( $label, 'afsanalytics' ) ),
			$cb,
			$this->page
		);
	}

	// CHECKBOX


	public function add_checkboxes( $items ) {
		foreach ( $items as $field => $label ) {
			$this->add_checkbox( $field, $label );
		}
	}

	public function add_checkbox( $field, $label ) {
		add_settings_field(
			$field,
			$label,
			array( $this, 'render_checkbox' ),
			$this->page,
			$this->section_id,
			array( $field, $label )
		);
	}

	public function input_name( $field ) {
		return 'name ="' . $this->settings_group . '[' . $field . ']" ';
	}

	public function render_checkbox( $args ) {
		$field = $args[0];
		$label = $args[1];
		$v     = $this->option_value( $field );

		$id = 'input_' . $field;

		$this->render_field(
			$field,
			'<input type="checkbox" id="' . $id . '" '
				. $this->input_name( $field )
				. 'value="1" '
				. ( $v == 1 ? 'checked="checked" ' : '' )
				. '>'
		);
	}

	public function get_select_options( $field ) {

		$common = array(
			'cookie_setting'       => array(
				array( 0, __( 'First party', 'afsanalytics' ) ),
				array( 1, __( 'No Cookies', 'afsanalytics' ) ),
			),
			'ip_setting'           => array(
				array( 0, __( 'Disabled', 'afsanalytics' ) ),
				array( 1, __( '1 Byte', 'afsanalytics' ) ),
				array( 2, __( '2 Bytes', 'afsanalytics' ) ),
				array( 3, __( '3 Bytes', 'afsanalytics' ) ),
				array( 4, __( '4 Bytes', 'afsanalytics' ) ),
			),
			'user_consent'         => array(
				array( 0, __( 'Disabled', 'afsanalytics' ) ),
				array( 1, __( 'Exemption', 'afsanalytics' ) ),
				array( 2, __( 'Auto', 'afsanalytics' ) ),
			),
			'localization_setting' => array(
				array( 0, __( 'Europe', 'afsanalytics' ) ),
				array( 1, __( 'World', 'afsanalytics' ) ),
			),
		);

		if ( ! empty( $common[ $field ] ) ) {
			return $common[ $field ];
		}

		if ( -1 !== stristr( $field, 'autotrack_' ) ) {
			return array(
				array( 2, __( 'Off', 'afsanalytics' ) ),
				array( 1, __( 'On', 'afsanalytics' ) ),
				array( 0, __( 'Dataset', 'afsanalytics' ) ),
			);
		}

		return null;
	}

	public function add_select_inputs( $items ) {
		foreach ( $items as $field => $label ) {
			$this->add_select( $field, $label );
		}
	}

	public function add_select( $field, $label ) {
		add_settings_field(
			$field,
			$label,
			array( $this, 'render_select' ),
			$this->page,
			$this->section_id,
			$field
		);
	}

	public function render_select( $field ) {
		$v = $this->option_value( $field );

		$ret = '<select  '
			. $this->input_name( $field )
			. '>';

		foreach ( $this->get_select_options( $field ) as $data ) {
			$ret .= '<option value="' . $data[0] . '" '
				. ( $v == $data[0] ? 'selected="selected"' : '' )
				. '>' . $data[1] . '</option>';
		}

		return $this->render_field( $field, $ret . '</select>' );
	}

	public function render_field( $field, $code ) {

		$help = array(
			'admin_pages_tracking'     =>
			__( 'Check this if you want activity on your admin pages to be tracked (default: checked)', 'afsanalytics' ),
			'self_visits_hidden'       =>
			__( 'Check this if you do not want your own visits to be tracked (default: unchecked)', 'afsanalytics' ),
			'user_logged_tracking'     =>
			__( 'Enable user tracking (default: checked)', 'afsanalytics' ),
			'afsa_anon_user_infos'     =>
			__( 'Check this option to anonymize visitor information.', 'afsanalytics' ),
			'gravatar_profile_enabled' =>
			__( 'Use users gravatar profile as default avatar image.', 'afsanalytics' ),
		);

		print $code;
		if ( ! empty( $help[ $field ] ) ) {
			print '<p class=afsa_help>' . $help[ $field ] . '</div>';
		}
	}
}
