<?php
/**
 * Composer autoload.files helpers for Mint LMS.
 *
 * Must stay WP-agnostic: do not use `defined( 'ABSPATH' ) || exit;` here.
 * PHPUnit/CLI load vendor/autoload.php before WordPress defines ABSPATH;
 * a bare exit (or early return + require_once) would break the process or
 * skip helper registration.
 *
 * @package MintLMS
 */

declare(strict_types=1);

/**
 * WordPress DB API handle (single access point — prefer over scattered `global $wpdb`).
 *
 * Core still owns `$wpdb`; plugins should not invent a second connection.
 */
function mintlms_wpdb(): ?\wpdb {
	global $wpdb;

	return $wpdb instanceof \wpdb ? $wpdb : null;
}
