<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Remap legacy enrollment "open" (self-enroll) → "free".
 * New "open" means public browse (LD-style).
 */
final class Migration_008_CourseAccessSettings implements MigrationInterface {

	public function version(): string {
		return '008';
	}

	public function up( \wpdb $wpdb ): void {
		$courses = Schema::coursesTable( $wpdb->prefix );

		if ( $this->tableExists( $wpdb, $courses ) ) {
			$courses = Schema::validateTable( $courses, $wpdb->prefix );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET enrollment_type = 'free' WHERE enrollment_type = 'open'",
					$courses
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET meta_value = %s WHERE meta_key = %s AND meta_value = %s',
				$wpdb->postmeta,
				'free',
				PostTypes::META_ENROLLMENT_TYPE,
				'open'
			)
		);

		$option = get_option( \MintLMS\Infrastructure\Setup\PageSettings::OPTION_DEFAULT_ENROLLMENT, '' );
		if ( 'open' === $option || '' === $option || false === $option ) {
			update_option( \MintLMS\Infrastructure\Setup\PageSettings::OPTION_DEFAULT_ENROLLMENT, 'free', false );
		}
	}

	private function tableExists( \wpdb $wpdb, string $table ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table
			)
		);

		return is_string( $found ) && $found === $table;
	}
}
