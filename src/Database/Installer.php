<?php

namespace PMProProductLoop\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database table creation and incremental migrations.
 *
 * Called directly by register_activation_hook (install) and by Bootstrap on
 * every plugin load (maybe_upgrade) so schema is always up to date.
 */
class Installer {

	const DB_VERSION        = '1.1';
	const DB_VERSION_OPTION = 'pmpro_pl_db_version';

	/**
	 * Run on plugin activation: create tables and stamp the DB version.
	 */
	public static function install(): void {
		if ( self::create_tables() ) {
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}
	}

	/**
	 * Run on every plugin load: apply any outstanding migrations if the stored
	 * version is behind the current code version.
	 */
	public static function maybe_upgrade(): void {
		$stored = get_option( self::DB_VERSION_OPTION, '0' );

		if ( version_compare( $stored, self::DB_VERSION, '>=' ) ) {
			return;
		}

		if ( self::create_tables() ) {
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}
	}

	/**
	 * Build the DDL and run it through dbDelta.
	 *
	 * dbDelta formatting rules that must be followed exactly:
	 *   - Each column/key definition on its own line.
	 *   - Two spaces between PRIMARY KEY and the key definition.
	 *   - No trailing comma on the last column before the KEY lines.
	 *
	 * @return bool True if schema creation succeeded, false otherwise.
	 */
	private static function create_tables(): bool {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = $wpdb->prefix . 'pmpro_pl_subscriptions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id bigint(20) UNSIGNED NOT NULL,
  hash char(32) NOT NULL,
  product_id bigint(20) UNSIGNED NOT NULL,
  level_id bigint(20) UNSIGNED NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY user_hash (user_id,hash),
  KEY product_id (product_id)
) {$charset_collate};";

		dbDelta( $sql );

		return empty( $wpdb->last_error );
	}
}