<?php

namespace PMProProductLoop\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bootstrap {
	/**
	 * @var string[]
	 */
	private static $dependency_errors = array();

	public static function boot(): void {
		self::$dependency_errors = self::missing_dependencies();

		if ( ! empty( self::$dependency_errors ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'render_dependency_notice' ) );
			return;
		}

		// Intentionally empty for Task 1 scaffold. Services are registered in later tasks.
	}

	/**
	 * @return string[]
	 */
	private static function missing_dependencies(): array {
		$errors = array();

		if ( ! class_exists( 'WooCommerce' ) ) {
			$errors[] = __( 'WooCommerce is required.', 'pmpro-product-loop' );
		}

		if ( ! function_exists( 'pmpro_magic_levels_process' ) ) {
			$errors[] = __( 'Paid Memberships Pro Magic Levels is required.', 'pmpro-product-loop' );
		}

		return $errors;
	}

	public static function render_dependency_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>';
		echo esc_html__( 'PMPro Product Loop:', 'pmpro-product-loop' );
		echo '</strong> ';
		echo esc_html__( 'Plugin initialization halted because required dependencies are missing.', 'pmpro-product-loop' );
		echo '</p><ul style="margin-left:1.4em;list-style:disc;">';
		foreach ( self::$dependency_errors as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}
		echo '</ul></div>';
	}
}
