<?php
/**
 * Plugin Name:       Mint LMS - A simpler LMS, fully manageable through your AI tool
 * Plugin URI:        https://wordpress.org/plugins/mint-lms/
 * Description:       A clean, fast WordPress LMS for teachers and students.
 * Version:           1.1.25
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            Evolve
 * Author URI:        https://elearningevolve.com/
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

define( 'MINTLMS_VERSION', '1.1.25' );
define( 'MINTLMS_FILE', __FILE__ );
define( 'MINTLMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MINTLMS_URL', plugin_dir_url( __FILE__ ) );

$mintlms_autoload = MINTLMS_PATH . 'vendor/autoload.php';
if ( ! is_readable( $mintlms_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>';
			echo esc_html__(
				'Mint LMS cannot load: vendor/autoload.php is missing. Run composer install (development) or install a release zip built with composer install --no-dev (see dev/build-release.sh).',
				'mint-lms'
			);
			echo '</p></div>';
		}
	);
	return;
}
require_once $mintlms_autoload;

MintLMS\Bootstrap::init();
