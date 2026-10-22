<?php
/**
 * Plugin Name:       Mint LMS
 * Plugin URI:        https://wordpress.org/plugins/mint-lms/
 * Description:       A clean, fast WordPress LMS for teachers and students.
 * Version:           1.0.330
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            Mint LMS
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mint-lms
 *
 * @package MintLMS
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'MINTLMS_VERSION' ) ) {
	return;
}

define( 'MINTLMS_VERSION', '1.0.330' );
define( 'MINTLMS_FILE', __FILE__ );
define( 'MINTLMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MINTLMS_URL', plugin_dir_url( __FILE__ ) );

require_once MINTLMS_PATH . 'vendor/autoload.php';

MintLMS\Bootstrap::init();
