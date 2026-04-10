<?php

namespace PMProProductLoop\Product;

use PMProProductLoop\Intent\Subscription_Intent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Product_Page {

	public function register_hooks(): void {
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_checkout_link' ), 35 );
	}

	public function render_checkout_link(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$product_id = (int) $product->get_id();
		$stock      = (int) $product->get_stock_quantity();
		if ( $stock <= 0 ) {
			echo '<p><button class="button alt" disabled="disabled">Out of stock</button></p>';
			return;
		}

		$user_id = (int) get_current_user_id();
		if ( $user_id <= 0 ) {
			echo '<p><a class="button alt" href="' . esc_url( wp_login_url( get_permalink( $product_id ) ) ) . '">Log in to continue</a></p>';
			return;
		}

		$intent = new Subscription_Intent( $product_id, $user_id );
		if ( ! $intent->is_valid() ) {
			return;
		}

		$url = add_query_arg(
			array(
				'intent'     => $intent->hash,
				'product_id' => $product_id,
			),
			home_url( '/pmpro-checkout/' )
		);

		$url = wp_nonce_url( $url, 'pmpro_pl_intent_' . $intent->hash );

		echo '<p><a class="button alt" href="' . esc_url( $url ) . '">Continue to Membership Checkout</a></p>';
	}
}
