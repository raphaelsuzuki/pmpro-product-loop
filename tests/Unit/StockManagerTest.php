<?php

use PHPUnit\Framework\TestCase;
use PMProProductLoop\Stock\Stock_Manager;

final class StockManagerTest extends TestCase {
	public function test_decrement_returns_false_when_stock_is_zero(): void {
		$db = new class extends wpdb {
			public function prepare( $query, ...$args ) {
				return $query;
			}

			public function query( $query ) {
				$this->rows_affected = 0;
				return 0;
			}
		};

		$stock = new Stock_Manager( $db );
		$this->assertFalse( $stock->decrement( 10 ) );
	}
}
