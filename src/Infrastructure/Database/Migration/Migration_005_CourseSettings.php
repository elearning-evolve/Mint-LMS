<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;

final class Migration_005_CourseSettings implements MigrationInterface {

	public function version(): string {
		return '005';
	}

	public function up( \wpdb $wpdb ): void {
		$table = Schema::validateTable( Schema::coursesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
		$columns = $wpdb->get_col(
			$wpdb->prepare(
				'DESC %i',
				$table
			),
			0
		);

		if ( ! is_array( $columns ) || ! in_array( 'settings_json', $columns, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD COLUMN settings_json longtext NULL AFTER enrollment_type',
					$table
				)
			);
		}
	}
}
