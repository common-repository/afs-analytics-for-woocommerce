<?php

class AFSA_Tools {

	public static function render_js_data( array $arr ) {
		$js = '';

		if ( count( $arr ) ) {
			foreach ( $arr as $k => $v ) {
				if ( is_array( $v ) || is_string( $v ) ) {
					$js .= 'var ' . $k . '=' . json_encode( $v, JSON_UNESCAPED_SLASHES ) . ';';
				} elseif ( is_string( $v ) ) {
					$js .= 'var ' . $k . '=' . static::js_escape( $v ) . ';';
				} else {
					$js .= 'var ' . $k . '="' . $v . '";';
				}
			}
		}

		return static::render_js_script( $js );
	}

	public static function render_js_script( $js ) {
		return empty( $js ) ?
				'' :
				"<script>\n$js\n</script>\n";
	}

	public static function log( $str, $data = null ) {
		if ( ! AFSA_Config::is_debug() ) {
			return;
		}

		$d_str = ' ';
		if ( $data ) {
			$d_str .= is_array( $data ) ?
					json_encode( $data, JSON_PRETTY_PRINT ) :
					$data;
		}
		error_log( $str . $d_str );
	}

	public static function js_escape( $string ) {
		return str_replace( "\n", '\n', str_replace( '"', '\"', addcslashes( str_replace( "\r", '', (string) $string ), "\0..\37'\\" ) ) );
	}

	public static function normalize_string( $str ) {

		if ( ! is_string( $str ) || empty( $str ) ) {
			return '';
		}

		$ret = trim( str_replace( array( '"', "'" ), ' ', $str ) );

		if ( function_exists( 'mb_strtolower' ) ) {
			$ret = mb_strtolower( $ret );
		}

		return $ret;
	}

	public static function dump( $data, $force = false ) {
		if ( ! $force && ( ! AFSA_Config::is_debug() || AFSA_Config::is_ajax() ) ) {
			return;
		}

		print '<pre>' . json_encode( $data, JSON_PRETTY_PRINT ) . '</pre>';
	}

	public static function build_url( $u, $extra_args ) {
		return add_query_arg( $extra_args, $u );
	}

	public static function redirect( $u ) {
		wp_redirect( $u );
		exit();
	}

	public static function get_all_headers() {
		try {

			if ( function_exists( 'getallheaders' ) ) {
				return getallheaders();
			}

			$ret = array();
			foreach ( $_SERVER as $k => $v ) {
				if ( substr( $k, 0, 5 ) == 'HTTP_' ) {
					$ret[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $k, 5 ) ) ) ) ) ] = sanitize_text_field( $v );
				}
			}
			return $ret;
		} catch ( Exception $e ) {

		}

		return array();
	}

}
