<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts AI API keys at rest (options table). Prefer MINTLMS_AI_API_KEY in wp-config when possible.
 */
final class AiSecretStore {

	private const CIPHER = 'AES-256-CBC';

	public static function encrypt( string $plain ): string {
		$plain = trim( $plain );
		if ( '' === $plain ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			// Fallback: still never expose via REST; not ideal — prefer openssl on host.
			return 'b64:' . base64_encode( $plain );
		}

		$key = hash( 'sha256', (string) wp_salt( 'auth' ), true );
		$iv  = random_bytes( 16 );
		$raw = openssl_encrypt( $plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $raw ) {
			return '';
		}

		return 'enc:' . base64_encode( $iv . $raw );
	}

	public static function decrypt( string $stored ): string {
		$stored = trim( $stored );
		if ( '' === $stored ) {
			return '';
		}

		if ( str_starts_with( $stored, 'b64:' ) ) {
			$decoded = base64_decode( substr( $stored, 4 ), true );
			return is_string( $decoded ) ? $decoded : '';
		}

		if ( ! str_starts_with( $stored, 'enc:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$blob = base64_decode( substr( $stored, 4 ), true );
		if ( ! is_string( $blob ) || strlen( $blob ) < 17 ) {
			return '';
		}

		$iv  = substr( $blob, 0, 16 );
		$raw = substr( $blob, 16 );
		$key = hash( 'sha256', (string) wp_salt( 'auth' ), true );
		$out = openssl_decrypt( $raw, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		return is_string( $out ) ? $out : '';
	}

	public static function hint( string $plain ): string {
		$plain = trim( $plain );
		if ( strlen( $plain ) < 4 ) {
			return '';
		}

		return substr( $plain, -4 );
	}
}
