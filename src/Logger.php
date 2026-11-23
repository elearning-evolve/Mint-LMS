<?php
declare(strict_types=1);

namespace MintLMS;

defined( 'ABSPATH' ) || exit;

/**
 * Shared plugin logger.
 *
 * Prefers WooCommerce's logger when available (WooCommerce → Status → Logs,
 * source "mint-lms"). Otherwise writes only when WP_DEBUG is enabled so
 * production stays quiet and Plugin Check / WPCS do not see raw error_log
 * at call sites.
 */
final class Logger {

	public const SOURCE = 'mint-lms';

	/**
	 * @param array<string, mixed> $context Extra WC logger context (merged with source).
	 */
	public static function log( string $level, string $message, array $context = array() ): void {
		$level   = strtolower( trim( $level ) );
		$message = trim( $message );

		if ( '' === $message ) {
			return;
		}

		$context = array_merge( array( 'source' => self::SOURCE ), $context );

		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->log( $level, $message, $context );

			return;
		}

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated fallback when WooCommerce logger is unavailable.
		error_log( sprintf( '[%s][%s] %s', self::SOURCE, $level, $message ) );
	}

	/**
	 * @param array<string, mixed> $context Extra WC logger context.
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( 'error', $message, $context );
	}

	/**
	 * @param array<string, mixed> $context Extra WC logger context.
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( 'warning', $message, $context );
	}

	/**
	 * @param array<string, mixed> $context Extra WC logger context.
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( 'info', $message, $context );
	}

	/**
	 * @param array<string, mixed> $context Extra WC logger context.
	 */
	public static function debug( string $message, array $context = array() ): void {
		self::log( 'debug', $message, $context );
	}
}
