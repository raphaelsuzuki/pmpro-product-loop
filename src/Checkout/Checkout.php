<?php

namespace PMProProductLoop\Checkout;

use PMProProductLoop\Core\Core;
use PMProProductLoop\Intent\Intent_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Checkout {

	/** @var Core */
	private $core;

	/** @var callable */
	private $resolver_factory;

	public function __construct( Core $core, callable $resolver_factory ) {
		$this->core             = $core;
		$this->resolver_factory = $resolver_factory;
	}

	public function register_hooks(): void {
		add_action( 'template_redirect', array( $this, 'handle_intent_request' ) );
	}

	public function handle_intent_request(): void {
		if ( ! isset( $_GET['intent'] ) ) {
			return;
		}

		$hash      = sanitize_text_field( wp_unslash( $_GET['intent'] ) );
		$product_id = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0;
		$nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( '' === $hash || $product_id <= 0 ) {
			$this->event( 'checkout_invalid_input', $hash, 0 );
			$this->redirect_with_notice( $product_id, 'Invalid subscription intent.' );
		}

		if ( ! wp_verify_nonce( $nonce, 'pmpro_pl_intent_' . $hash ) ) {
			$this->event( 'checkout_nonce_failed', $hash, 0 );
			$this->redirect_with_notice( $product_id, 'Security validation failed. Please try again.' );
		}

		$resolver = call_user_func( $this->resolver_factory, $product_id );
		if ( ! $resolver instanceof Intent_Resolver ) {
			$this->event( 'checkout_resolver_missing', $hash, 0 );
			$this->redirect_with_notice( $product_id, 'Intent resolver is unavailable.' );
		}

		$intent = $resolver->resolve( $hash );
		if ( false === $intent ) {
			$this->event( 'checkout_intent_rejected', $hash, 0 );
			$this->redirect_with_notice( $product_id, 'Intent validation failed.' );
		}

		$result = $this->core->process( $intent );
		if ( 'ok' !== ( $result['status'] ?? 'error' ) ) {
			$this->event( 'checkout_core_failed', $hash, 0 );
			$message = isset( $result['message'] ) ? (string) $result['message'] : 'Unable to process subscription.';
			$this->redirect_with_notice( $product_id, $message );
		}

		$level_id = isset( $result['data']['level_id'] ) ? (int) $result['data']['level_id'] : 0;
		$this->event( 'checkout_intent_processed', $hash, $level_id );
	}

	private function redirect_with_notice( int $product_id, string $message ): void {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, 'error' );
		}

		$url = $this->product_url( $product_id );
		wp_redirect( $url );
		exit;
	}

	private function product_url( int $product_id ): string {
		if ( $product_id > 0 ) {
			$permalink = get_permalink( $product_id );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}

		return home_url( '/' );
	}

	private function event( string $type, string $hash, int $level_id ): void {
		do_action(
			'pmpro_pl_event',
			array(
				'type'     => $type,
				'hash'     => $hash,
				'level_id' => $level_id,
			)
		);
	}
}
