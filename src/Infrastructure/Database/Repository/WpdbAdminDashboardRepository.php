<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AdminDashboardRepositoryInterface;
use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\PostType\PostTypes;

final class WpdbAdminDashboardRepository implements AdminDashboardRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	/**
	 * @param list<int> $courseIds
	 * @return array<string, mixed>
	 */
	public function getAuthorStats( int $authorId, array $courseIds ): array {
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN() lists for author course ids., PluginCheck.Security.DirectDB.UnescapedDBParameter
		$prefix           = $this->wpdb->prefix;
		$postsTable       = $this->wpdb->posts;
		$postmetaTable    = $this->wpdb->postmeta;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $prefix ), $prefix );
		$progressTable    = Schema::validateTable( Schema::progressTable( $prefix ), $prefix );
		$sectionsTable    = Schema::validateTable( Schema::sectionsTable( $prefix ), $prefix );
		$courseType  = PostTypes::COURSE;
		$lessonType  = PostTypes::LESSON;
		$sectionMeta = PostTypes::META_SECTION_ID;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$publishedCount = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$postsTable}
				WHERE post_type = %s AND post_author = %d AND post_status = 'publish'",
				$courseType,
				$authorId
			)
		);

		$draftCount = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$postsTable}
				WHERE post_type = %s AND post_author = %d AND post_status = 'draft'",
				$courseType,
				$authorId
			)
		);

		$studentsTotal = 0;
		$finishedCount = 0;
		$startedCount  = 0;

		if ( array() !== $courseIds ) {
			$placeholders = implode( ',', array_fill( 0, count( $courseIds ), '%d' ) );

			$studentsTotal = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders}) AND status = 'active'",
					...$courseIds
				)
			);

			$finishedCount = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders}) AND completed_at IS NOT NULL",
					...$courseIds
				)
			);

			$startedCount = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders}) AND status = 'active'",
					...$courseIds
				)
			);
		}

		$finishedPct = $startedCount > 0 ? round( ( $finishedCount / $startedCount ) * 100 ) : null;

		$weekAgo = gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS );

		$lessonsDone7d = 0;

		if ( array() !== $courseIds ) {
			$lessonsDone7d = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$progressTable} p
					INNER JOIN {$postsTable} c ON c.ID = p.course_id AND c.post_type = %s
					WHERE c.post_author = %d AND p.completed_at >= %s",
					$courseType,
					$authorId,
					$weekAgo
				)
			);
		}

		$studentsDelta = 0;
		if ( array() !== $courseIds ) {
			$placeholders = implode( ',', array_fill( 0, count( $courseIds ), '%d' ) );

			$studentsDelta = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders}) AND enrolled_at >= %s",
					...array_merge( $courseIds, array( $weekAgo ) )
				)
			);
		}

		$courseStats = array();

		if ( array() !== $courseIds ) {
			$courseStats = $this->getCourseStatsBatch( $courseIds );

			foreach ( $courseIds as $courseId ) {
				if ( ! isset( $courseStats[ $courseId ] ) ) {
					continue;
				}

				$emptySections = (int) $this->wpdb->get_var(
					$this->wpdb->prepare(
						"SELECT COUNT(*) FROM {$sectionsTable} s
						WHERE s.course_id = %d
						AND NOT EXISTS (
							SELECT 1 FROM {$postmetaTable} pm
							INNER JOIN {$postsTable} l ON l.ID = pm.post_id AND l.post_type = %s AND l.post_status NOT IN ('trash','auto-draft')
							WHERE pm.meta_key = %s AND pm.meta_value = CAST(s.id AS CHAR)
						)",
						$courseId,
						$lessonType,
						$sectionMeta
					)
				);

				$courseStats[ $courseId ]['empty_sections'] = $emptySections;
			}
		}

		$activity = array();

		if ( array() !== $courseIds ) {
			$placeholders = implode( ',', array_fill( 0, count( $courseIds ), '%d' ) );

			$completedRows = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT e.user_id, c.post_title AS course_title, e.completed_at AS occurred_at, 'completed' AS type
					FROM {$enrollmentsTable} e
					INNER JOIN {$postsTable} c ON c.ID = e.course_id AND c.post_type = %s
					WHERE e.course_id IN ({$placeholders}) AND e.completed_at IS NOT NULL
					ORDER BY e.completed_at DESC LIMIT 5",
					$courseType,
					...$courseIds
				)
			);

			$enrolledRows = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT e.user_id, c.post_title AS course_title, e.enrolled_at AS occurred_at, 'enrolled' AS type
					FROM {$enrollmentsTable} e
					INNER JOIN {$postsTable} c ON c.ID = e.course_id AND c.post_type = %s
					WHERE e.course_id IN ({$placeholders})
					ORDER BY e.enrolled_at DESC LIMIT 5",
					$courseType,
					...$courseIds
				)
			);

			$merged = array();

			if ( is_array( $completedRows ) ) {
				foreach ( $completedRows as $row ) {
					$merged[] = array(
						'user_id'      => (int) $row->user_id,
						'course_title' => (string) $row->course_title,
						'occurred_at'  => (string) $row->occurred_at,
						'type'         => 'completed',
					);
				}
			}

			if ( is_array( $enrolledRows ) ) {
				foreach ( $enrolledRows as $row ) {
					$merged[] = array(
						'user_id'      => (int) $row->user_id,
						'course_title' => (string) $row->course_title,
						'occurred_at'  => (string) $row->occurred_at,
						'type'         => 'enrolled',
					);
				}
			}

			usort(
				$merged,
				static fn( array $a, array $b ): int => strcmp( $b['occurred_at'], $a['occurred_at'] )
			);

			$activity = array_slice( $merged, 0, 5 );
		}

		$weeklyCompletions = 0;
		if ( array() !== $courseIds ) {
			$placeholders = implode( ',', array_fill( 0, count( $courseIds ), '%d' ) );

			$weeklyCompletions = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders}) AND completed_at >= %s",
					...array_merge( $courseIds, array( $weekAgo ) )
				)
			);
		}

		$signalLine = '';

		if ( $studentsTotal > 0 ) {
			if ( $weeklyCompletions > 0 ) {
				$signalLine = sprintf(
					/* translators: 1: student count, 2: weekly completion count */
					_n(
						'%1$d student is learning with you. %2$d finished a course this week.',
						'%1$d students are learning with you. %2$d finished a course this week.',
						$studentsTotal,
						'mint-lms'
					),
					$studentsTotal,
					$weeklyCompletions
				);
			} else {
				$signalLine = sprintf(
					// translators: %d: student count
					_n(
						'%d student is learning with you.',
						'%d students are learning with you.',
						$studentsTotal,
						'mint-lms'
					),
					$studentsTotal
				);
			}
		} else {
			$signalLine = __( 'Create a course to start welcoming students.', 'mint-lms' );
		}
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'students_total'  => $studentsTotal,
			'finished_pct'    => $finishedPct,
			'finished_count'  => $finishedCount,
			'started_count'   => $startedCount,
			'published_count' => $publishedCount,
			'draft_count'     => $draftCount,
			'lessons_done_7d' => $lessonsDone7d,
			'students_delta'  => $studentsDelta,
			'finished_delta'  => 0,
			'courses'         => $courseStats,
			'activity'        => $activity,
			'signal_line'     => $signalLine,
		);
	}

	/**
	 * @param list<int> $courseIds
	 * @return array<int, array{students: int, completion_pct: int|null, lesson_count: int}>
	 */
	public function getCourseStatsBatch( array $courseIds ): array {
		if ( array() === $courseIds ) {
			return array();
		}

		$prefix           = $this->wpdb->prefix;
		$postsTable       = $this->wpdb->posts;
		$postmetaTable    = $this->wpdb->postmeta;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $prefix ), $prefix );
		$summaryTable     = Schema::validateTable( Schema::progressSummaryTable( $prefix ), $prefix );
		$placeholders     = implode( ',', array_fill( 0, count( $courseIds ), '%d' ) );
		$lessonType       = PostTypes::LESSON;
		$courseMeta       = PostTypes::META_COURSE_ID;
		$stats            = array();

		foreach ( $courseIds as $courseId ) {
			$stats[ $courseId ] = array(
				'students'       => 0,
				'completion_pct' => null,
				'lesson_count'   => 0,
			);
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$lessonRows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT CAST(pm.meta_value AS UNSIGNED) AS course_id, COUNT(*) AS lesson_count
				FROM {$postsTable} p
				INNER JOIN {$postmetaTable} pm ON pm.post_id = p.ID AND pm.meta_key = %s
				WHERE p.post_type = %s
					AND p.post_status NOT IN ('trash','auto-draft')
					AND CAST(pm.meta_value AS UNSIGNED) IN ({$placeholders})
				GROUP BY CAST(pm.meta_value AS UNSIGNED)",
				$courseMeta,
				$lessonType,
				...$courseIds
			)
		);

		if ( is_array( $lessonRows ) ) {
			foreach ( $lessonRows as $row ) {
				$courseId = (int) $row->course_id;
				if ( isset( $stats[ $courseId ] ) ) {
					$stats[ $courseId ]['lesson_count'] = (int) $row->lesson_count;
				}
			}
		}

		$studentRows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT course_id, COUNT(*) AS student_count FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders}) AND status = 'active' GROUP BY course_id",
				...$courseIds
			)
		);

		if ( is_array( $studentRows ) ) {
			foreach ( $studentRows as $row ) {
				$courseId = (int) $row->course_id;
				if ( isset( $stats[ $courseId ] ) ) {
					$stats[ $courseId ]['students'] = (int) $row->student_count;
				}
			}
		}

		$completionRows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT course_id, AVG(pct_complete) AS avg_pct FROM {$summaryTable} WHERE course_id IN ({$placeholders}) GROUP BY course_id",
				...$courseIds
			)
		);

		if ( is_array( $completionRows ) ) {
			foreach ( $completionRows as $row ) {
				$courseId = (int) $row->course_id;
				if ( isset( $stats[ $courseId ] ) && null !== $row->avg_pct ) {
					$stats[ $courseId ]['completion_pct'] = (int) round( (float) $row->avg_pct );
				}
			}
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return $stats;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getReportsSummary( ?int $authorId ): array {
		$postsTable       = $this->wpdb->posts;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$progressTable    = Schema::validateTable( Schema::progressTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$courseType       = PostTypes::COURSE;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$archived = PostTypes::STATUS_ARCHIVED;

		if ( null !== $authorId ) {
			$totalCourses = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$postsTable}
					WHERE post_type = %s AND post_author = %d AND post_status IN ('draft','publish','{$archived}')",
					$courseType,
					$authorId
				)
			);

			$courseIds = array_map(
				'intval',
				$this->wpdb->get_col(
					$this->wpdb->prepare(
						"SELECT ID FROM {$postsTable}
						WHERE post_type = %s AND post_author = %d AND post_status IN ('draft','publish','{$archived}')",
						$courseType,
						$authorId
					)
				) ?: array()
			);
		} else {
			$totalCourses = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$postsTable}
					WHERE post_type = %s AND post_status IN ('draft','publish','{$archived}')",
					$courseType
				)
			);

			$courseIds = array_map(
				'intval',
				$this->wpdb->get_col(
					$this->wpdb->prepare(
						"SELECT ID FROM {$postsTable}
						WHERE post_type = %s AND post_status IN ('draft','publish','{$archived}')",
						$courseType
					)
				) ?: array()
			);
		}

		$studentsTotal    = 0;
		$enrollmentsTotal = 0;
		$completionsTotal = 0;
		$activity         = array();

		if ( array() !== $courseIds ) {
			$placeholders = implode( ',', array_fill( 0, count( $courseIds ), '%d' ) );

			$studentsTotal = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders}) AND status = 'active'",
					...$courseIds
				)
			);

			$enrollmentsTotal = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders})",
					...$courseIds
				)
			);

			$completionsTotal = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$enrollmentsTable} WHERE course_id IN ({$placeholders}) AND completed_at IS NOT NULL",
					...$courseIds
				)
			);

			$activity = $this->getAuthorStats( $authorId ?? 0, $courseIds )['activity'] ?? array();
		}

		$lessonsCompleted = 0;

		if ( null !== $authorId ) {
			$lessonsCompleted = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$progressTable} p
					INNER JOIN {$postsTable} c ON c.ID = p.course_id AND c.post_type = %s
					WHERE c.post_author = %d",
					$courseType,
					$authorId
				)
			);
		} else {
			$lessonsCompleted = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$progressTable}" );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'total_courses'     => $totalCourses,
			'total_students'    => $studentsTotal,
			'total_enrollments' => $enrollmentsTotal,
			'total_completions' => $completionsTotal,
			'lessons_completed' => $lessonsCompleted,
			'activity'          => $activity,
		);
	}
}
