<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;

/**
 * CPT hybrid schema: question_data, quiz↔question junction, id map.
 * Content (course/lesson/quiz/question) moves to wp_posts; sections stay custom.
 */
final class Migration_006_CptHybrid implements MigrationInterface {

	public function version(): string {
		return '006';
	}

	public function up( \wpdb $wpdb ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		$idMap = Schema::validateTable( Schema::idMapTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$idMap} (
                entity_type varchar(20) NOT NULL,
                legacy_id bigint(20) unsigned NOT NULL,
                post_id bigint(20) unsigned NOT NULL,
                PRIMARY KEY  (entity_type, legacy_id),
                UNIQUE KEY post_id (post_id),
                KEY entity_post (entity_type, post_id)
            ) {$charset};"
		);

		$questionData = Schema::validateTable( Schema::questionDataTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$questionData} (
                post_id bigint(20) unsigned NOT NULL,
                question_type varchar(20) NOT NULL DEFAULT 'mcq',
                options_json longtext NULL,
                correct_answer text NOT NULL,
                points int(10) unsigned NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (post_id)
            ) {$charset};"
		);

		// Preserve legacy owned-question rows, then create junction table under the canonical name.
		$questions = Schema::validateTable( Schema::quizQuestionsTable( $prefix ), $prefix );
		$legacy    = $prefix . 'mintlms_quiz_questions_legacy';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$questions
			)
		);
		if ( $questions === $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
			$cols       = $wpdb->get_col(
				$wpdb->prepare(
					'DESC %i',
					$questions
				),
				0
			);
			$isJunction = is_array( $cols ) && in_array( 'question_id', $cols, true );

			if ( ! $isJunction ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
				$legacyExists = $wpdb->get_var(
					$wpdb->prepare(
						'SHOW TABLES LIKE %s',
						$legacy
					)
				);
				if ( $legacy !== $legacyExists ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Custom table migration; Schema::validateTable().
					$wpdb->query(
						$wpdb->prepare(
							'RENAME TABLE %i TO %i',
							$questions,
							$legacy
						)
					);
				}
			}
		}

		dbDelta(
			"CREATE TABLE {$questions} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                quiz_id bigint(20) unsigned NOT NULL,
                question_id bigint(20) unsigned NOT NULL,
                sort_order int(10) unsigned NOT NULL DEFAULT 0,
                point_override int(10) unsigned NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY uniq_quiz_question (quiz_id, question_id),
                KEY quiz_id (quiz_id),
                KEY question_id (question_id)
            ) {$charset};"
		);
	}
}
