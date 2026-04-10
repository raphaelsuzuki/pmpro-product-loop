<?php

use PHPUnit\Framework\TestCase;
use PMProProductLoop\Intent\Subscription_Intent;

if ( ! function_exists( 'wc_get_product' ) ) {
	function wc_get_product( $product_id ) {
		if ( 10 !== (int) $product_id ) {
			return false;
		}

		return new class {
			public function get_price() {
				return '29.99';
			}
		};
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $product_id, $key, $single ) {
		$map = array(
			'_pmpro_pl_period' => 'Month',
			'_pmpro_pl_cycles' => '12',
		);

		return $map[ $key ] ?? '';
	}
}

final class SubscriptionIntentTest extends TestCase {
	public function test_hash_computation_is_deterministic(): void {
		$intent = new Subscription_Intent( 10, 55 );

		$this->assertTrue( $intent->is_valid() );
		$expected = md5( json_encode( array( 10, 55, 29.99, 'Month', 12 ) ) );

		$this->assertSame( $expected, $intent->compute_hash() );
		$this->assertSame( $expected, $intent->hash );
	}
}
