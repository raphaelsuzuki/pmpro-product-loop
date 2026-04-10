<?php

namespace PMProProductLoop\Intent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves and validates an incoming intent hash against server-side product data.
 */
class Intent_Resolver {

	/** @var int */
	private $product_id;

	public function __construct( int $product_id ) {
		$this->product_id = $product_id;
	}

	/**
	 * Resolve and verify the incoming hash.
	 *
	 * Design note: the checkout URL should carry product_id alongside hash so the
	 * server knows which product meta to load when rebuilding the intent. The
	 * product_id is only a lookup hint; all business values are re-fetched and
	 * hash-verified server-side before acceptance.
	 *
	 * @param string $hash Hash from query string.
	 *
	 * @return Subscription_Intent|false
	 */
	public function resolve( string $hash ) {
		$hash = trim( $hash );
		if ( '' === $hash || 32 !== strlen( $hash ) ) {
			return false;
		}

		$current_user_id = (int) get_current_user_id();
		if ( $current_user_id <= 0 || $this->product_id <= 0 ) {
			return false;
		}

		$intent = new Subscription_Intent( $this->product_id, $current_user_id );
		if ( ! $intent->is_valid() ) {
			return false;
		}

		if ( $intent->user_id !== $current_user_id ) {
			return false;
		}

		if ( ! hash_equals( $intent->compute_hash(), $hash ) ) {
			return false;
		}

		$intent->hash = $hash;

		return $intent;
	}
}
