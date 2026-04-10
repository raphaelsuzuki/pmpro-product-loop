<?php

use PHPUnit\Framework\TestCase;
use PMProProductLoop\Intent\Intent_Resolver;

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 55;
	}
}

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

final class IntentResolverTest extends TestCase {
	public function test_resolve_rejects_hash_mismatch(): void {
		$resolver = new Intent_Resolver( 10 );

		$this->assertFalse( $resolver->resolve( str_repeat( 'a', 32 ) ) );
	}
}
