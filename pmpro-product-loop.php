<?php
/**
 * Plugin Name: PMPro Product Loop
 * Description: Minimal bridge between WooCommerce products and PMPro Magic Levels subscriptions.
 * Version: 1.1.0
 * Author: PMPro Product Loop
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: pmpro-product-loop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PMPRO_PL_VERSION', '1.1.0' );
define( 'PMPRO_PL_PATH', plugin_dir_path( __FILE__ ) );
define( 'PMPRO_PL_URL', plugin_dir_url( __FILE__ ) );

$composer_autoload = PMPRO_PL_PATH . 'vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
} else {
	spl_autoload_register(
		static function ( $class ) {
			$prefix = 'PMProProductLoop\\';
			if ( 0 !== strpos( $class, $prefix ) ) {
				return;
			}

			$relative = substr( $class, strlen( $prefix ) );
			$relative = str_replace( '\\', '/', $relative );
			$file     = PMPRO_PL_PATH . 'src/' . $relative . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

register_activation_hook(
	__FILE__,
	[ 'PMProProductLoop\Database\Installer', 'install' ]
);

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( '\PMProProductLoop\Plugin\Bootstrap' ) ) {
			\PMProProductLoop\Plugin\Bootstrap::boot();
		}
	}
);
