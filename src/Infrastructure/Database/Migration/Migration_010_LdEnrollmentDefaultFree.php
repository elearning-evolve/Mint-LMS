<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Setup\PageSettings;

/**
 * LD 4.9+ default enrollment is Free (LEARNDASH_DEFAULT_COURSE_PRICE_TYPE).
 * Does not rewrite existing course posts.
 */
final class Migration_010_LdEnrollmentDefaultFree implements MigrationInterface {

	public function version(): string {
		return '010';
	}

	public function up( \wpdb $wpdb ): void {
		unset( $wpdb );

		$current = get_option( PageSettings::OPTION_DEFAULT_ENROLLMENT, 'free' );
		if ( 'open' === $current || '' === $current || false === $current ) {
			update_option( PageSettings::OPTION_DEFAULT_ENROLLMENT, 'free', false );
		}
	}
}
