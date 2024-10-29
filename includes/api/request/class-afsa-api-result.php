<?php


require_once AFSA_INCLUDES_DIR . '/class-afsa-db.php';

class AFSA_Api_Request_Result {

	private $request;
	private $data;
	private $visitor_ids;
	private $product_skus;
	private $db;

	public function __construct( $request, $data = array() ) {
		$this->request      = $request;
		$this->data         = $data;
		$this->visitor_ids  = array();
		$this->product_skus = array();
		$this->db           = null;

		AFSA_Tools::log( 'CTX', $request->context );
	}

	public function set_data( $data ) {
		$this->data = $data;
	}

	private function get_db() {
		return $this->db ?
				$this->db :
				$this->db = AFSA_DB::get();
	}

	public function render() {
		$data    = $this->data;
		$actions = array();

		if ( ! empty( $data['error'] ) ) {
			$this->parse_error( $data['error'] );
		}

		if ( empty( $data ) || empty( $data['performed_actions'] ) ) {
			return array(
				'who'   => 'WP Result',
				'error' => 'no action requested',
				'data'  => $data,
			);
		}

		foreach ( $data['performed_actions'] as $uid => $a ) {
			$actions[ $uid ] = $this->parse_action( $a, $uid );
		}

		$result = array( 'performed_actions' => $actions );

		// not rendering context infos if in demo mode
		return AFSA_Config::is_demo() ?
				$result :
				$this->render_enhanced_infos( $result );
	}

	public function parse_error( $e ) {
		switch ( $e ) {
			// Invalid token
			case 'access_denied':
				$this->request->logout();
				break;
		}
	}

	public function parse_action( &$a, $uid ) {

		try {
			// updating account info
			$terms = explode( ':', $uid );
			if ( $terms[0] == 'account_infos' ) {
				AFSA_Account_Manager::on_ajax_infos_received( $a );
			}
		} catch ( Exception $ex ) {

		}

		if ( empty( $a['metas'] ) ) {
			return $a;
		}

		$m = &$a['metas'];

		if ( ! empty( $m['custom'] ) ) {
			foreach ( $m['custom'] as $k => $v ) {
				switch ( $k ) {
					case 'user_id':
						$this->register_visitors( $v );
						break;
					case 'product_sku':
						$this->register_products( $v );
						break;
				}
			}
		}

		return $a;
	}

	public function register_visitors( array $ids = array() ) {
		$this->visitor_ids = array_merge( $this->visitor_ids, $ids );
	}

	public function register_products( array $ids = array() ) {
		$this->product_skus = array_merge( $this->product_skus, $ids );
	}

	public function render_Enhanced_Infos( $result ) {
		$visitors = &$this->visitor_ids;
		$products = &$this->product_skus;
		$ret      = $result;

		$metas = array( 'context' => array() );
		if ( ! empty( $visitors ) ) {
			$infos = $this->render_visitors_infos();
			if ( ! empty( $infos ) ) {
				$metas['context']['visitors'] = $infos;
			}
		}

		if ( ! empty( $products ) ) {
			$infos = $this->render_products_infos();
			if ( ! empty( $infos ) ) {
				$metas['context']['products'] = $infos;
			}
		}

		if ( ! empty( $metas['context'] ) ) {
			$ret['metas'] = $metas;
		}

		return $ret;
	}

	public function render_visitors_infos() {
		$context = $this->request->context;

		if ( empty( $this->visitor_ids ) ) {
			return null;
		}

		$known_ids = empty( $context['visitors'] ) ?
				array() :
				$context['visitors'];

		$result_ids = array_unique( $this->visitor_ids );

		$ids = array_diff( $result_ids, $known_ids );
		if ( empty( $ids ) ) {
			AFSA_Tools::log(
				'CTX all known',
				array(
					'R' => $result_ids,
					'K' => $known_ids,
				)
			);

			return null;
		}

		AFSA_Tools::log(
			'CTX unknown',
			array(
				'D' => $ids,
				'R' => $result_ids,
				'K' => $known_ids,
			)
		);

		$ret = array();

		foreach ( $ids as $id ) {
			$data = get_user_meta( $id );
			if ( ! $data ) {
				continue;
			}

			$user_data = array( 'id' => $id );

			$search = array(
				'first_name'    => 'firstname',
				'last_name'     => 'lastname',
				'billing_email' => 'email',
				'billing_phone' => 'phone',
			);

			foreach ( $search as $src => $dest ) {
				$user_data[ $dest ] = $data[ $src ];
			}

			$ret['id'] = $user_data;
		}

		return $ret;
	}

	public function render_products_infos() {
		$context = $this->request->context;

		if ( empty( $this->product_skus ) ) {
			return null;
		}

		$known_skus = empty( $context['products'] ) ?
				array() :
				$context['products'];

		$result_skus = array_unique( $this->product_skus );

		$skus = array_diff( $result_skus, $known_skus );
		if ( empty( $skus ) ) {
			AFSA_Tools::log(
				'RCTX all known',
				array(
					'R' => $result_skus,
					'K' => $known_skus,
				)
			);
			return null;
		}

		AFSA_Tools::log(
			'RCTX unknown',
			array(
				'D' => $skus,
				'R' => $result_skus,
				'K' => $known_skus,
			)
		);

		$ret   = array();
		$items = $this->get_db()->get_products_by_ref( $skus );

		AFSA_Tools::log( 'RCTX items found', array( $skus, count( $items ) ) );

		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {

				try {
					$ret[ $item->get_sku() ] = AFSA_Product_Infos::get_ajax_context_info( $item );
				} catch ( Exception $e ) {

				}
			}
		}

		return $ret;
	}

}
