<?php

namespace PMProProductLoop\Intent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable value object representing a subscription intent for one user + product pair.
 *
 * All billing configuration is read from server-side product meta; no values
 * are accepted from or trusted to callers. This class has no database interaction.
 */
class Subscription_Intent {

	/**
	 * Custom product meta key for billing period (Day|Week|Month|Year).
	 */
	const META_PERIOD = '_pmpro_pl_period';

	/**
	 * Custom product meta key for billing cycle limit (0 = unlimited).
	 */
	const META_CYCLES = '_pmpro_pl_cycles';

	/** @var int */
	public $product_id;

	/** @var int */
	public $user_id;

	/** @var float|null */
	public $price = null;

	/** @var string|null */
	public $period = null;

	/** @var int|null */
	public $cycles = null;

	/** @var string|null Computed hash, null when intent is invalid. */
	public $hash = null;

	public function __construct( int $product_id, int $user_id ) {
		$this->product_id = $product_id;
		$this->user_id    = $user_id;

		$this->load_from_product_meta();

		if ( $this->is_valid() ) {
			$this->hash = $this->compute_hash();
		}
	}

	/**
	 * Read billing configuration from WooCommerce product meta.
	 *
	 * Never trusts caller input — all values originate from the server.
	 */
	private function load_from_product_meta(): void {
		$product = wc_get_product( $this->product_id );

		if ( ! $product ) {
			return;
		}

		$raw_price = $product->get_price();
		if ( $raw_price !== '' && $raw_price !== null && $raw_price !== false ) {
			$this->price = (float) $raw_price;
		}

		$period = get_post_meta( $this->product_id, self::META_PERIOD, true );
		if ( $period !== '' ) {
			$this->period = (string) $period;
		}

		$cycles_raw = get_post_meta( $this->product_id, self::META_CYCLES, true );
		if ( $cycles_raw !== '' ) {
			$this->cycles = (int) $cycles_raw;
		}
	}

	/**
	 * Compute a deterministic hash over the five intent fields.
	 *
	 * Used for idempotency checks and server-side intent verification.
	 * Not a cryptographic operation — MD5 is appropriate for fingerprinting.
	 */
	public function compute_hash(): string {
		return md5( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_md5
			json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				[
					$this->product_id,
					$this->user_id,
					$this->price,
					$this->period,
					$this->cycles,
				]
			)
		);
	}

	/**
	 * Returns false if the product does not exist or any required billing field is absent.
	 *
	 * A cycles value of 0 is valid (unlimited billing).
	 */
	public function is_valid(): bool {
		if ( $this->product_id <= 0 || $this->user_id <= 0 ) {
			return false;
		}

		if ( null === $this->price || $this->price <= 0.0 ) {
			return false;
		}

		if ( empty( $this->period ) ) {
			return false;
		}

		// $cycles === null means the meta key was absent; 0 = unlimited and is valid.
		if ( null === $this->cycles ) {
			return false;
		}

		return true;
	}
}
