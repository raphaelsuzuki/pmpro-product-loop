<?php

namespace PMProProductLoop\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sole data access layer for wp_pmpro_pl_subscriptions.
 */
class Subscription_Repository {

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $table;

	public function __construct( \wpdb $db = null ) {
		global $wpdb;

		$this->wpdb  = $db ?: $wpdb;
		$this->table = $this->wpdb->prefix . 'pmpro_pl_subscriptions';
	}

	/**
	 * Lookup a subscription by the unique key (user_id, hash).
	 *
	 * @return array|null
	 */
	public function find( int $user_id, string $hash ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE user_id = %d AND hash = %s LIMIT 1",
				$user_id,
				$hash
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return $row;
	}

	/**
	 * Insert a subscription row.
	 *
	 * Returns false on duplicate key or any insert error.
	 */
	public function insert( array $data ): bool {
		$inserted = $this->wpdb->insert(
			$this->table,
			array(
				'user_id'    => (int) $data['user_id'],
				'hash'       => (string) $data['hash'],
				'product_id' => (int) $data['product_id'],
				'level_id'   => (int) $data['level_id'],
				'status'     => (string) $data['status'],
				'created_at' => (string) $data['created_at'],
			),
			array( '%d', '%s', '%d', '%d', '%s', '%s' )
		);

		return false !== $inserted;
	}

	/**
	 * Insert row or return existing row if UNIQUE(user_id, hash) collides.
	 */
	public function find_or_create( array $data ): array {
		if ( $this->insert( $data ) ) {
			$created = $this->find( (int) $data['user_id'], (string) $data['hash'] );
			return is_array( $created ) ? $created : $data;
		}

		$existing = $this->find( (int) $data['user_id'], (string) $data['hash'] );
		return is_array( $existing ) ? $existing : $data;
	}

	public function update_status( int $id, string $status ): bool {
		$updated = $this->wpdb->update(
			$this->table,
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		return $updated > 0;
	}

	/**
	 * Lookup an active subscription by membership level and user.
	 *
	 * @return array|null
	 */
	public function find_active_by_user_and_level( int $user_id, int $level_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE user_id = %d AND level_id = %d AND status = 'active' LIMIT 1",
				$user_id,
				$level_id
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return $row;
	}
}
