<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database;

final class Schema {

	public static function coursesTable( string $prefix ): string {
		return $prefix . 'mintlms_courses';
	}

	public static function sectionsTable( string $prefix ): string {
		return $prefix . 'mintlms_sections';
	}

	public static function lessonsTable( string $prefix ): string {
		return $prefix . 'mintlms_lessons';
	}

	public static function enrollmentsTable( string $prefix ): string {
		return $prefix . 'mintlms_enrollments';
	}

	public static function progressTable( string $prefix ): string {
		return $prefix . 'mintlms_progress';
	}

	public static function progressSummaryTable( string $prefix ): string {
		return $prefix . 'mintlms_progress_summary';
	}

	public static function quizzesTable( string $prefix ): string {
		return $prefix . 'mintlms_quizzes';
	}

	public static function quizQuestionsTable( string $prefix ): string {
		return $prefix . 'mintlms_quiz_questions';
	}

	public static function quizAttemptsTable( string $prefix ): string {
		return $prefix . 'mintlms_quiz_attempts';
	}

	public static function questionDataTable( string $prefix ): string {
		return $prefix . 'mintlms_question_data';
	}

	public static function idMapTable( string $prefix ): string {
		return $prefix . 'mintlms_id_map';
	}

	public static function quizQuestionsLegacyTable( string $prefix ): string {
		return $prefix . 'mintlms_quiz_questions_legacy';
	}

	/**
	 * Validate a table name against the plugin schema whitelist.
	 */
	public static function validateTable( string $table, string $prefix ): string {
		if ( ! in_array( $table, self::allTables( $prefix ), true ) ) {
			throw new \InvalidArgumentException( 'Invalid Mint LMS table name.' );
		}

		return $table;
	}

	/**
	 * Active schema tables (content CPTs live in wp_posts; these are relational/runtime).
	 *
	 * @return list<string>
	 */
	public static function allTables( string $prefix ): array {
		return array(
			self::sectionsTable( $prefix ),
			self::enrollmentsTable( $prefix ),
			self::progressTable( $prefix ),
			self::progressSummaryTable( $prefix ),
			self::quizQuestionsTable( $prefix ),
			self::quizAttemptsTable( $prefix ),
			self::questionDataTable( $prefix ),
			self::idMapTable( $prefix ),
			// Legacy content tables kept during/after migration for uninstall cleanup.
			self::coursesTable( $prefix ),
			self::lessonsTable( $prefix ),
			self::quizzesTable( $prefix ),
			self::quizQuestionsLegacyTable( $prefix ),
		);
	}
}
