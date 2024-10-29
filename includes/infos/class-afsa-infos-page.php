<?php

class AFSA_Page_Infos extends AFSA_Infos {

	public function get_name( $default = '' ) {
		$ret = $this->get( 'name' );
		return $ret ?: $default;
	}

	public function retrieve() {
		$this->data['name'] = $this->clean_name( $this->set_name() );
	}

	public function set_name() {
			global $post;

			$ID = get_the_ID();

		if ( is_home() || is_front_page() ) {
			return 'home';
		}

		if ( is_attachment( $ID ) == false && is_page( $ID ) == false && is_single( $ID ) == false ) {
			  return null;
		}

		if ( isset( $post->ID ) ) {
			$ret = get_post_meta( $post->ID, 'afstrackername', true );
			return $ret ?
					sanitize_text_field( $ret ) :
					$post->post_title;
		}

		if ( is_admin() ) {
			return 'admin';
		}

				return null;
	}

	private function clean_name( $str ) {
		if ( empty( $str ) ) {
			return null;
		}

		$ret = explode( '|', stripslashes( $str ) )[0];
		return esc_js( trim( $ret ) );
	}

}
