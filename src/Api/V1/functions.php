<?php
/**
 * Composer autoload.files helpers for Mint LMS.
 *
 * @package MintLMS
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * WordPress DB API handle (single access point — prefer over scattered `global $wpdb`).
 *
 * Core still owns `$wpdb`; plugins should not invent a second connection.
 */
function mintlms_wpdb(): ?\wpdb {
	global $wpdb;

	return $wpdb instanceof \wpdb ? $wpdb : null;
}
