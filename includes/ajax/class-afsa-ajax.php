<?php

require_once AFSA_INCLUDES_DIR . '/api/request/class-afsa-api-request.php';

class AFSA_Ajax {

	public static function stats_server() {

		check_ajax_referer( 'AFSA' );

		ob_start();

		$request = new AFSA_Api_Request();
		$json    = json_encode( $request->run() );

		ob_end_clean();

		header( 'Content-type: application/json' );
		echo $json;

		wp_die();

	}

}
