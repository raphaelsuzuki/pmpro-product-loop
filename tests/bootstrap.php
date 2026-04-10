<?php

define( 'ABSPATH', __DIR__ . '/' );

if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {
		public $postmeta      = 'wp_postmeta';
		public $rows_affected = 0;

		public function prepare( $query, ...$args ) {
			if ( empty( $args ) ) {
				return $query;
			}

			return vsprintf( $query, $args );
		}

		public function query( $query ) {
			$this->rows_affected = 0;
			return 0;
		}
	}
}

require_once dirname( __DIR__ ) . '/src/Intent/Subscription_Intent.php';
require_once dirname( __DIR__ ) . '/src/Intent/Intent_Resolver.php';
require_once dirname( __DIR__ ) . '/src/Stock/Stock_Manager.php';
