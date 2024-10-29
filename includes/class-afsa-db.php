<?php

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

class AFSA_DB {

	static $instance;
	private $db_schema_version = '1.0.0';

	static function get() {
		return static::$instance ?: static::$instance = new static();
	}

	public function __construct() {
		global $wpdb;

		$this->order_table = $wpdb->prefix . 'afsa_processed_order';

		$this->blog_id = get_current_blog_id();
		$this->db      = $wpdb;

		if ( AFSA_Config::is_debug() ) {
			$this->db->show_errors();
		}
	}

	// ORDERS

	public function get_order_id( $order_id ) {

		return $this->db->get_var(
			'SELECT id_order FROM ' . $this->order_table
						. ' WHERE id_order="' . $order_id . '"'
						. ' AND  id_shop="' . $this->blog_id . '"'
		);
	}

	public function save_processed_order( $order_id ) {
		$this->db->query(
			$this->db->prepare(
				'INSERT IGNORE INTO `' . $this->order_table . '`'
						. ' (id_order, id_shop, date_add)'
						. ' VALUES( %d , %d , NOW() )',
				$order_id,
				$this->blog_id
			)
		);
	}

	public function was_order_processed( $order_id ) {
		return $this->get_Order_ID( $order_id );
	}

	public function save_order_if_not_exists( $id_order ) {
		if ( ! $this->was_order_processed( $id_order ) ) {
			$this->save_order( $id_order );
		}
	}

	public function get_last_orders() {
		return $this->db->get_results(
			'SELECT * FROM `' . $this->order_table . '`'
						. ' WHERE  id_shop = \'' . $this->blog_id . '\''
						. ' AND DATE_ADD(date_add, INTERVAL 30 minute) < NOW()',
			ARRAY_A
		);
	}

	public function clean_processed_order_table() {
		$this->db->query(
			'DELETE FROM `'
				. $this->order_table . '`'
				. ' WHERE DATE_ADD(date_add, INTERVAL 7 day) < NOW()'
		);
	}

	// INSTALL / UNINSTALL

	public function create_tables() {

		$charset_collate = $this->db->get_charset_collate();

		dbDelta(
			"CREATE TABLE $this->order_table ("
				. ' id_order int(11) NOT NULL,'
				. ' id_shop int(11) NOT NULL,'
				. ' date_add datetime DEFAULT NULL,'
				. ' PRIMARY KEY  (id_order)'
				. ") $charset_collate;"
		);

		add_option( 'afsa_db_schema_version', $this->db_schema_version );
	}

	public function drop_tables() {
		$this->db->query( 'DROP TABLE IF EXISTS ' . $this->order_table );
		delete_option( 'afsa_db_schema_version' );
	}

	// API Context related

	public function get_products_by_ref( array $skus ) {

		if ( ! AFSA_Config::woocommerce_enabled() ) {
			return null;
		}

		$ids = array_map(
			function( $n ) {
					return "'" . sanitize_text_field( $n ) . "'";
			},
			$skus
		);

		$ids_fields = implode( ',', $ids );

		$tablename = $this->db->prefix . 'postmeta';

		$rows = $this->db->get_results(
			"SELECT post_id FROM $tablename WHERE meta_key='_sku' AND meta_value in ($ids_fields) "
		);

		$products = array();
		foreach ( $rows as $row ) {
			$products[] = wc_get_product( $row->post_id );
		}

		return $products;
	}

}
