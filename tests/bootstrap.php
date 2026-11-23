<?php
declare(strict_types=1);

/**
 * PHPUnit bootstrap — stub minimal WordPress APIs for unit/architecture tests.
 *
 * @package MintLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../vendor/autoload.php';

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param mixed $default Default value.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return $default;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.WP.I18n.MissingTranslatorsComment
		unset( $domain );
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.WP.I18n.MissingTranslatorsComment
		unset( $domain );
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return (string) $url;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return is_string( $str ) ? trim( $str ) : '';
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return strip_tags( (string) $string );
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		unset( $post_id, $meta_key, $meta_value );
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		unset( $post_id, $key );
		return $single ? '' : array();
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		unset( $post_id, $meta_key, $meta_value, $prev_value );
		return true;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		unset( $post, $output, $filter );
		return null;
	}
}

if ( ! function_exists( 'get_userdata' ) ) {
	function get_userdata( $user_id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		unset( $user_id );
		return false;
	}
}

if ( ! function_exists( 'home_url' ) ) {
	/**
	 * @param string $path Optional path relative to home.
	 */
	function home_url( $path = '' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		$path = is_string( $path ) ? $path : '';

		return 'http://example.com' . ( '' === $path || str_starts_with( $path, '/' ) ? $path : '/' . $path );
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	/**
	 * @param int|\WP_Post|null $post Post ID or object.
	 * @return string|false
	 */
	function get_permalink( $post = 0 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		unset( $post );

		return false;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Minimal query-arg helper for unit tests (not a full WP port).
	 *
	 * @param string|array<string, mixed> $key   Key or map of args.
	 * @param string                      $value Value when $key is a string.
	 * @param string|null                 $url   Base URL.
	 */
	function add_query_arg( $key, $value = '', $url = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( null === $url ) {
			$url = home_url( '/' );
		}

		$args = is_array( $key ) ? $key : array( (string) $key => $value );
		$parts = wp_parse_url( (string) $url );
		if ( ! is_array( $parts ) ) {
			$parts = array();
		}

		$query = array();
		if ( ! empty( $parts['query'] ) && is_string( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
		}

		foreach ( $args as $arg_key => $arg_value ) {
			$query[ (string) $arg_key ] = $arg_value;
		}

		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
		$host   = $parts['host'] ?? '';
		$port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		$path   = $parts['path'] ?? '';
		$built  = $scheme . $host . $port . $path;
		$qs     = http_build_query( $query );

		return '' === $qs ? $built : $built . '?' . $qs;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * @param string $url       URL.
	 * @param int    $component Component.
	 * @return mixed
	 */
	function wp_parse_url( $url, $component = -1 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return parse_url( (string) $url, $component );
	}
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
