<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../vendor/autoload.php';

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return $default;
	}
}
