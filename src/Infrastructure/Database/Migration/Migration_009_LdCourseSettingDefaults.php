<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Setup\PageSettings;

/**
 * Restore LearnDash-style default joining method (Open) after 008 forced Free.
 * Does not rewrite existing course enrollment types.
 */
final class Migration_009_LdCourseSettingDefaults implements MigrationInterface {

	public function version(): string {
		return '009';
	}

	public function up( \wpdb $wpdb ): void {
		unset( $wpdb );

		$current = get_option( PageSettings::OPTION_DEFAULT_ENROLLMENT, 'open' );
		if ( 'free' === $current || '' === $current || false === $current ) {
			update_option( PageSettings::OPTION_DEFAULT_ENROLLMENT, 'open', false );
		}
	}
}
