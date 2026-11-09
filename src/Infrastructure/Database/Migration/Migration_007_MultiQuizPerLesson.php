<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;

/**
 * Allow multiple quizzes per lesson on the legacy quizzes table.
 */
final class Migration_007_MultiQuizPerLesson implements MigrationInterface {

	public function version(): string {
		return '007';
	}

	public function up( \wpdb $wpdb ): void {
		$table = Schema::validateTable( Schema::quizzesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
		$index = $wpdb->get_row(
			$wpdb->prepare(
				"SHOW INDEX FROM %i WHERE Key_name = 'lesson_id'",
				$table
			)
		);
		if ( null !== $index ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i DROP INDEX lesson_id',
					$table
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
		$sortIndex = $wpdb->get_row(
			$wpdb->prepare(
				"SHOW INDEX FROM %i WHERE Key_name = 'lesson_sort'",
				$table
			)
		);
		if ( null === $sortIndex ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD KEY lesson_sort (lesson_id, sort_order)',
					$table
				)
			);
		}
	}
}
