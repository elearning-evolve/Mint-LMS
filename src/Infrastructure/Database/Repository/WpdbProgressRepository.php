<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Progress\CompleteLessonResult;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Progress\ProgressSummary;
use MintLMS\Domain\Shared\UserId;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbProgressRepository implements ProgressRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findLessonCourseId( int $lessonId ): ?int {
		$post = get_post( $lessonId );

		if ( ! $post instanceof \WP_Post || \MintLMS\Infrastructure\PostType\PostTypes::LESSON !== $post->post_type ) {
			return null;
		}

		$courseId = (int) get_post_meta( $lessonId, \MintLMS\Infrastructure\PostType\PostTypes::META_COURSE_ID, true );

		return $courseId > 0 ? $courseId : null;
	}

	public function isUserEnrolled( int $userId, int $courseId ): bool {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE user_id = %d AND course_id = %d AND status = %s',
				$table,
				$userId,
				$courseId,
				'active'
			)
		);

		return null !== $found;
	}

	public function isLessonComplete( int $userId, int $lessonId ): bool {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::progressTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE user_id = %d AND lesson_id = %d',
				$table,
				$userId,
				$lessonId
			)
		);

		return null !== $found;
	}

	public function completeLesson(
		int $userId,
		int $lessonId,
		int $courseId,
		\DateTimeImmutable $completedAt,
	): CompleteLessonResult {
		$this->beginTransaction();

		try {
			$wasNewlyCompleted = false;

			if ( ! $this->isLessonComplete( $userId, $lessonId ) ) {
				$this->insertProgress( $userId, $lessonId, $courseId, $completedAt );
				$wasNewlyCompleted = true;
			}

			$summary             = $this->recalculateSummaryInternal( $userId, $courseId, $completedAt, $lessonId );
			$courseJustCompleted = $this->markEnrollmentCompletedIfNeeded( $userId, $courseId, $summary, $completedAt );

			$this->commitTransaction();

			/**
			 * Fires after progress changes so dashboard aggregates can refresh.
			 */
			do_action( 'mintlms_dashboard_cache_invalidate' );

			return new CompleteLessonResult( $wasNewlyCompleted, $summary, $courseJustCompleted );
		} catch ( \Throwable $exception ) {
			$this->rollbackTransaction();
			throw $exception;
		}
	}

	public function uncompleteLesson(
		int $userId,
		int $lessonId,
		\DateTimeImmutable $updatedAt,
	): void {
		$wpdb     = $this->wpdb;
		$courseId = $this->findLessonCourseId( $lessonId );

		if ( null === $courseId ) {
			return;
		}

		$this->beginTransaction();

		try {
			$progressTable = Schema::validateTable( Schema::progressTable( $wpdb->prefix ), $wpdb->prefix );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$progressTable,
				array(
					'user_id'   => $userId,
					'lesson_id' => $lessonId,
				),
				array( '%d', '%d' )
			);

			$this->recalculateSummaryInternal( $userId, $courseId, $updatedAt, null );
			$this->clearEnrollmentCompleted( $userId, $courseId );

			$this->commitTransaction();

			/**
			 * Fires after progress changes so dashboard aggregates can refresh.
			 */
			do_action( 'mintlms_dashboard_cache_invalidate' );
		} catch ( \Throwable $exception ) {
			$this->rollbackTransaction();
			throw $exception;
		}
	}

	public function getSummary( int $userId, int $courseId ): ?ProgressSummary {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::progressSummaryTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT user_id, course_id, lessons_done, lessons_total, pct_complete, last_lesson_id, updated_at
				FROM %i
				WHERE user_id = %d AND course_id = %d',
				$table,
				$userId,
				$courseId
			)
		);

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToSummary( $row );
	}

	public function recalculateSummary(
		int $userId,
		int $courseId,
		\DateTimeImmutable $updatedAt,
		?int $lastLessonId = null,
	): ProgressSummary {
		$this->beginTransaction();

		try {
			$summary = $this->recalculateSummaryInternal( $userId, $courseId, $updatedAt, $lastLessonId );
			$this->commitTransaction();

			return $summary;
		} catch ( \Throwable $exception ) {
			$this->rollbackTransaction();
			throw $exception;
		}
	}

	public function getCompletedLessonIds( int $userId, int $courseId ): array {
		$wpdb          = $this->wpdb;
		$progressTable = Schema::validateTable( Schema::progressTable( $wpdb->prefix ), $wpdb->prefix );
		$postsTable    = $wpdb->posts;
		$postmetaTable = $wpdb->postmeta;
		$lessonType    = \MintLMS\Infrastructure\PostType\PostTypes::LESSON;
		$courseMeta    = \MintLMS\Infrastructure\PostType\PostTypes::META_COURSE_ID;
		$sortMeta      = \MintLMS\Infrastructure\PostType\PostTypes::META_SORT_ORDER;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.lesson_id
				FROM %i p
				INNER JOIN %i l ON l.ID = p.lesson_id AND l.post_type = %s AND l.post_status = 'publish'
				INNER JOIN %i cm ON cm.post_id = l.ID AND cm.meta_key = %s AND cm.meta_value = %s
				LEFT JOIN %i sm ON sm.post_id = l.ID AND sm.meta_key = %s
				WHERE p.user_id = %d AND p.course_id = %d
				ORDER BY CAST(COALESCE(sm.meta_value, '0') AS UNSIGNED) ASC, l.ID ASC",
				$progressTable,
				$postsTable,
				$lessonType,
				$postmetaTable,
				$courseMeta,
				(string) $courseId,
				$postmetaTable,
				$sortMeta,
				$userId,
				$courseId
			)
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( 'intval', $rows );
	}

	public function countLessonsInCourse( int $courseId ): int {
		$query = new \WP_Query(
			array(
				'post_type'              => \MintLMS\Infrastructure\PostType\PostTypes::LESSON,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => \MintLMS\Infrastructure\PostType\PostTypes::META_COURSE_ID,
						'value' => $courseId,
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	public function onLessonDeleted( int $lessonId, \DateTimeImmutable $updatedAt ): void {
		$wpdb     = $this->wpdb;
		$courseId = $this->findLessonCourseId( $lessonId );

		if ( null === $courseId ) {
			return;
		}

		$progressTable = Schema::validateTable( Schema::progressTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$userIds = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT user_id FROM %i WHERE lesson_id = %d',
				$progressTable,
				$lessonId
			)
		);

		$this->beginTransaction();

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$progressTable,
				array( 'lesson_id' => $lessonId ),
				array( '%d' )
			);

			if ( is_array( $userIds ) ) {
				foreach ( $userIds as $userId ) {
					$this->recalculateSummaryInternal( (int) $userId, $courseId, $updatedAt, null );
					$this->clearEnrollmentCompleted( (int) $userId, $courseId );
				}
			}

			$this->commitTransaction();
		} catch ( \Throwable $exception ) {
			$this->rollbackTransaction();
			throw $exception;
		}
	}

	private function insertProgress(
		int $userId,
		int $lessonId,
		int $courseId,
		\DateTimeImmutable $completedAt,
	): void {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::progressTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'      => $userId,
				'lesson_id'    => $lessonId,
				'course_id'    => $courseId,
				'status'       => 'completed',
				'completed_at' => $completedAt->format( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to record lesson progress.' );
		}
	}

	private function recalculateSummaryInternal(
		int $userId,
		int $courseId,
		\DateTimeImmutable $updatedAt,
		?int $lastLessonId,
	): ProgressSummary {
		$wpdb         = $this->wpdb;
		$lessonsTotal = $this->countLessonsInCourse( $courseId );
		$lessonsDone  = $this->countCompletedLessons( $userId, $courseId );
		$pctComplete  = $this->calculatePercent( $lessonsDone, $lessonsTotal );

		if ( null === $lastLessonId ) {
			$lastLessonId = $this->findLastCompletedLessonId( $userId, $courseId );
		}

		$table = Schema::validateTable( Schema::progressSummaryTable( $wpdb->prefix ), $wpdb->prefix );
		$now   = $updatedAt->format( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$replaced = $wpdb->replace(
			$table,
			array(
				'user_id'        => $userId,
				'course_id'      => $courseId,
				'lessons_done'   => $lessonsDone,
				'lessons_total'  => $lessonsTotal,
				'pct_complete'   => $pctComplete,
				'last_lesson_id' => $lastLessonId,
				'updated_at'     => $now,
			),
			array( '%d', '%d', '%d', '%d', '%f', '%d', '%s' )
		);

		if ( false === $replaced ) {
			throw new \RuntimeException( 'Failed to update progress summary.' );
		}

		return new ProgressSummary(
			UserId::fromInt( $userId ),
			$courseId,
			$lessonsDone,
			$lessonsTotal,
			$pctComplete,
			$lastLessonId,
			$updatedAt,
		);
	}

	private function countCompletedLessons( int $userId, int $courseId ): int {
		return count( $this->getCompletedLessonIds( $userId, $courseId ) );
	}

	private function findLastCompletedLessonId( int $userId, int $courseId ): ?int {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::progressTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$lessonId = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT lesson_id FROM %i
				WHERE user_id = %d AND course_id = %d
				ORDER BY completed_at DESC, id DESC
				LIMIT 1',
				$table,
				$userId,
				$courseId
			)
		);

		if ( null === $lessonId ) {
			return null;
		}

		return (int) $lessonId;
	}

	private function markEnrollmentCompletedIfNeeded(
		int $userId,
		int $courseId,
		ProgressSummary $summary,
		\DateTimeImmutable $completedAt,
	): bool {
		$wpdb = $this->wpdb;
		if ( ! $summary->isCourseComplete() ) {
			return false;
		}

		$table = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT completed_at FROM %i WHERE user_id = %d AND course_id = %d',
				$table,
				$userId,
				$courseId
			)
		);

		if ( null !== $existing && '' !== $existing ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array( 'completed_at' => $completedAt->format( 'Y-m-d H:i:s' ) ),
			array(
				'user_id'   => $userId,
				'course_id' => $courseId,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		return true;
	}

	private function clearEnrollmentCompleted( int $userId, int $courseId ): void {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array( 'completed_at' => null ),
			array(
				'user_id'   => $userId,
				'course_id' => $courseId,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);
	}

	private function calculatePercent( int $lessonsDone, int $lessonsTotal ): float {
		if ( $lessonsTotal <= 0 ) {
			return 0.0;
		}

		return round( ( $lessonsDone / $lessonsTotal ) * 100, 2 );
	}

	private function mapRowToSummary( object $row ): ProgressSummary {
		$lastLessonId = null;

		if ( null !== $row->last_lesson_id && '' !== $row->last_lesson_id ) {
			$lastLessonId = (int) $row->last_lesson_id;
		}

		return new ProgressSummary(
			UserId::fromInt( (int) $row->user_id ),
			(int) $row->course_id,
			(int) $row->lessons_done,
			(int) $row->lessons_total,
			(float) $row->pct_complete,
			$lastLessonId,
			new \DateTimeImmutable( (string) $row->updated_at ),
		);
	}

	private function beginTransaction(): void {
		$wpdb = $this->wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );
	}

	private function commitTransaction(): void {
		$wpdb = $this->wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'COMMIT' );
	}

	private function rollbackTransaction(): void {
		$wpdb = $this->wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'ROLLBACK' );
	}
}
