<?php

namespace PMProProductLoop\Stock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Stock_Manager {

	/** @var \wpdb */
	private $wpdb;

	public function __construct( \wpdb $db = null ) {
		global $wpdb;
		$this->wpdb = $db ?: $wpdb;
	}

	/**
	 * Atomically decrement stock by one.
	 *
	 * Returns false when stock is exhausted or update fails.
	 */
	public function decrement( int $product_id ): bool {
		if ( $product_id <= 0 ) {
			return false;
		}

		$table = $this->wpdb->postmeta;
		$sql   = $this->wpdb->prepare(
			"UPDATE {$table}
			 SET meta_value = meta_value - 1
			 WHERE post_id = %d
			 AND meta_key = '_stock'
			 AND meta_value > 0",
			$product_id
		);

		$result = $this->wpdb->query( $sql );
		if ( false === $result ) {
			return false;
		}

		return 1 === (int) $this->wpdb->rows_affected;
	}

	/**
	 * Increment stock by one (used for cancel/rollback paths).
	 */
	public function increment( int $product_id ): bool {
		if ( $product_id <= 0 ) {
			return false;
		}

		$table = $this->wpdb->postmeta;
		$sql   = $this->wpdb->prepare(
			"UPDATE {$table}
			 SET meta_value = meta_value + 1
			 WHERE post_id = %d
			 AND meta_key = '_stock'",
			$product_id
		);

		$result = $this->wpdb->query( $sql );
		if ( false === $result ) {
			return false;
		}

		return 1 === (int) $this->wpdb->rows_affected;
	}
}
