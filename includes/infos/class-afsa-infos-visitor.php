<?php

class AFSA_Visitor_Infos extends AFSA_Infos {

	public function is_logged() {
		return ! empty( $this->data['logged'] );
	}

	public function get_ID() {
		return empty( $this->data['id'] ) ? 0 : $this->data['id'];
	}

	public function retrieve() {

		$user = wp_get_current_user();

		$roles = $user->roles;
		$role  = array_shift( $roles );

		$info = array(
			'role'   => $role ?: 'visitor',
			'logged' => is_user_logged_in(),
		);

				$custom_id = isset( $user->ID ) ? (int) $user->ID : 0;
		if ( $custom_id ) {
			$info['yourid'] = $custom_id;

			error_log( '[YOURID] ' . $custom_id );
		}

		if ( ! empty( $user->user_login ) ) {
			$info['username'] = esc_js( $user->user_login );
		}
		if ( ! empty( $user->user_email ) ) {
			$info['email'] = esc_js( $this->render_email( $user->user_email ) );
			try {

				if ( AFSA_Config::is_gravatar_enabled() ) {
					$info['photourl'] = 'gravatar:' . md5( strtolower( trim( $user->user_email ) ) );
				}
			} catch ( Exception $ex ) {

			}
		}

		if ( ! empty( $user->user_firstname ) ) {
			$info['firstname'] = esc_js( $user->user_firstname );
		}
		if ( ! empty( $user->user_lastname ) ) {
			$info['lastname'] = $this->render_lastname( $user->user_lastname );
		}
		if ( ! empty( $user->display_name ) ) {
			$info['displayedname'] = esc_js( $user->display_name );
		}

		if ( AFSA_Config::anonymize_members() ) {
			$info['anonymised'] = 1;
		}

		if ( ! empty( $user->company ) ) {
			$info['company'] = $o->company;
		}

		/*
		  if ($o->birthday != '0000-00-00') {
		  $info['birthday'] = $o->birthday;
		  }
		 */

		return $this->data = apply_filters( 'afsa_tracker_visitor_infos', $info, $user, $this );

	}

	// ANONYMIZE functions

	public static function render_lastname( $name ) {
		$n = trim( $name );

		if ( ! AFSA_Config::anonymize_members() ) {
			return $n;
		}

		return strtoupper( substr( $n, 0, 1 ) ) . '.';
	}

	public static function render_email( $str ) {
		$email = trim( $str );

		if ( ! AFSA_Config::anonymize_members() ) {
			return $email;
		}

		$p   = explode( '@', $email );
		$ret = '...';
		if ( ! empty( $p[1] ) ) {
			$ret .= '@' . $p[1];
		}

		return $ret;
	}

	public static function render_phone( $str ) {
		$chars = str_split( trim( $str ) );

		if ( ! AFSA_Config::anonymize_members() ) {
			return $str;
		}

		$ret   = array();
		$count = 0;
		foreach ( $chars as $ch ) {
			if ( ctype_digit( $ch ) ) {
				if ( ++$count > 4 ) {
					$ch = 'x';
				}
			}
			$ret[] = $ch;
		}

		return implode( '', $ret );
	}

}
