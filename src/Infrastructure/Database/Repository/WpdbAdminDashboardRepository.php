<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AdminDashboardRepositoryInterface;
use MintLMS\Infrastructure\Cache\WpObjectCache;
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
		$courseIds = array_values( array_map( 'intval', $courseIds ) );
		sort( $courseIds );

		$cacheKey = WpObjectCache::dashboardCacheKey(
			'author_stats_' . $authorId . '_' . md5( (string) wp_json_encode( $courseIds ) )
		);
		$cached   = wp_cache_get( $cacheKey, WpObjectCache::GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$wpdb             = $this->wpdb;
		$prefix           = $wpdb->prefix;
		$postsTable       = $wpdb->posts;
		$postmetaTable    = $wpdb->postmeta;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $prefix ), $prefix );
		$progressTable    = Schema::validateTable( Schema::progressTable( $prefix ), $prefix );
		$sectionsTable    = Schema::validateTable( Schema::sectionsTable( $prefix ), $prefix );
		$courseType       = PostTypes::COURSE;
		$lessonType       = PostTypes::LESSON;
		$sectionMeta      = PostTypes::META_SECTION_ID;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
		$publishedCount = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				WHERE post_type = %s AND post_author = %d AND post_status = 'publish'",
				$postsTable,
				$courseType,
				$authorId
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
		$draftCount = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				WHERE post_type = %s AND post_author = %d AND post_status = 'draft'",
				$postsTable,
				$courseType,
				$authorId
			)
		);

		$studentsTotal = 0;
		$finishedCount = 0;
		$startedCount  = 0;

		if ( array() !== $courseIds ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$studentsTotal = (int) $wpdb->get_var(
				$wpdb->prepare(
					sprintf(
						"SELECT COUNT(DISTINCT user_id) FROM %%i WHERE course_id IN (%s) AND status = 'active'",
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable ), $courseIds )
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$finishedCount = (int) $wpdb->get_var(
				$wpdb->prepare(
					sprintf(
						'SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND completed_at IS NOT NULL',
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable ), $courseIds )
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$startedCount = (int) $wpdb->get_var(
				$wpdb->prepare(
					sprintf(
						"SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND status = 'active'",
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable ), $courseIds )
				)
			);
		}

		$finishedPct = $startedCount > 0 ? round( ( $finishedCount / $startedCount ) * 100 ) : null;

		$weekAgo = gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS );

		$lessonsDone7d = 0;

		if ( array() !== $courseIds ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$lessonsDone7d = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i p
					INNER JOIN %i c ON c.ID = p.course_id AND c.post_type = %s
					WHERE c.post_author = %d AND p.completed_at >= %s',
					$progressTable,
					$postsTable,
					$courseType,
					$authorId,
					$weekAgo
				)
			);
		}

		$studentsDelta = 0;
		if ( array() !== $courseIds ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$studentsDelta = (int) $wpdb->get_var(
				$wpdb->prepare(
					sprintf(
						'SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND enrolled_at >= %%s',
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable ), $courseIds, array( $weekAgo ) )
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

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
				$emptySections = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM %i s
						WHERE s.course_id = %d
						AND NOT EXISTS (
							SELECT 1 FROM %i pm
							INNER JOIN %i l ON l.ID = pm.post_id AND l.post_type = %s AND l.post_status NOT IN ('trash','auto-draft')
							WHERE pm.meta_key = %s AND pm.meta_value = CAST(s.id AS CHAR)
						)",
						$sectionsTable,
						$courseId,
						$postmetaTable,
						$postsTable,
						$lessonType,
						$sectionMeta
					)
				);

				$courseStats[ $courseId ]['empty_sections'] = $emptySections;
			}
		}

		$activity = array();

		if ( array() !== $courseIds ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$completedRows = $wpdb->get_results(
				$wpdb->prepare(
					sprintf(
						"SELECT e.user_id, c.post_title AS course_title, e.completed_at AS occurred_at, 'completed' AS type
					FROM %%i e
					INNER JOIN %%i c ON c.ID = e.course_id AND c.post_type = %%s
					WHERE e.course_id IN (%s) AND e.completed_at IS NOT NULL
					ORDER BY e.completed_at DESC LIMIT 5",
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable, $postsTable, $courseType ), $courseIds )
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$enrolledRows = $wpdb->get_results(
				$wpdb->prepare(
					sprintf(
						"SELECT e.user_id, c.post_title AS course_title, e.enrolled_at AS occurred_at, 'enrolled' AS type
					FROM %%i e
					INNER JOIN %%i c ON c.ID = e.course_id AND c.post_type = %%s
					WHERE e.course_id IN (%s)
					ORDER BY e.enrolled_at DESC LIMIT 5",
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable, $postsTable, $courseType ), $courseIds )
				)
			);
			$merged       = array();

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

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$weeklyCompletions = (int) $wpdb->get_var(
				$wpdb->prepare(
					sprintf(
						'SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND completed_at >= %%s',
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable ), $courseIds, array( $weekAgo ) )
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

		$result = array(
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

		wp_cache_set( $cacheKey, $result, WpObjectCache::GROUP, WpObjectCache::DASHBOARD_TTL );

		return $result;
	}

	/**
	 * @param list<int> $courseIds
	 * @return array<int, array{students: int, completion_pct: int|null, lesson_count: int}>
	 */
	public function getCourseStatsBatch( array $courseIds ): array {
		$wpdb = $this->wpdb;
		if ( array() === $courseIds ) {
			return array();
		}

		$courseIds = array_values( array_map( 'intval', $courseIds ) );
		sort( $courseIds );

		$cacheKey = WpObjectCache::dashboardCacheKey(
			'course_stats_' . md5( (string) wp_json_encode( $courseIds ) )
		);
		$cached   = wp_cache_get( $cacheKey, WpObjectCache::GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$prefix           = $wpdb->prefix;
		$postsTable       = $wpdb->posts;
		$postmetaTable    = $wpdb->postmeta;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $prefix ), $prefix );
		$summaryTable     = Schema::validateTable( Schema::progressSummaryTable( $prefix ), $prefix );
		$lessonType       = PostTypes::LESSON;
		$courseMeta       = PostTypes::META_COURSE_ID;
		$stats            = array();

		foreach ( $courseIds as $courseId ) {
			$stats[ $courseId ] = array(
				'students'       => 0,
				'finished'       => 0,
				'completion_pct' => null,
				'lesson_count'   => 0,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
		$lessonRows = $wpdb->get_results(
			$wpdb->prepare(
				sprintf(
					"SELECT CAST(pm.meta_value AS UNSIGNED) AS course_id, COUNT(*) AS lesson_count
				FROM %%i p
				INNER JOIN %%i pm ON pm.post_id = p.ID AND pm.meta_key = %%s
				WHERE p.post_type = %%s
					AND p.post_status NOT IN ('trash','auto-draft')
					AND CAST(pm.meta_value AS UNSIGNED) IN (%s)
				GROUP BY CAST(pm.meta_value AS UNSIGNED)",
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge( array( $postsTable, $postmetaTable, $courseMeta, $lessonType ), $courseIds )
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
		$studentRows = $wpdb->get_results(
			$wpdb->prepare(
				sprintf(
					"SELECT course_id, COUNT(*) AS student_count FROM %%i WHERE course_id IN (%s) AND status = 'active' GROUP BY course_id",
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge( array( $enrollmentsTable ), $courseIds )
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
		$completionRows = $wpdb->get_results(
			$wpdb->prepare(
				sprintf(
					'SELECT course_id, AVG(pct_complete) AS avg_pct,
						SUM(CASE WHEN pct_complete >= 100 THEN 1 ELSE 0 END) AS finished_count
					FROM %%i WHERE course_id IN (%s) GROUP BY course_id',
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge( array( $summaryTable ), $courseIds )
			)
		);
		if ( is_array( $completionRows ) ) {
			foreach ( $completionRows as $row ) {
				$courseId = (int) $row->course_id;
				if ( isset( $stats[ $courseId ] ) ) {
					if ( null !== $row->avg_pct ) {
						$stats[ $courseId ]['completion_pct'] = (int) round( (float) $row->avg_pct );
					}
					$stats[ $courseId ]['finished'] = (int) ( $row->finished_count ?? 0 );
				}
			}
		}

		wp_cache_set( $cacheKey, $stats, WpObjectCache::GROUP, WpObjectCache::DASHBOARD_TTL );

		return $stats;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getReportsSummary( ?int $authorId ): array {
		$cacheKey = WpObjectCache::dashboardCacheKey( 'reports_' . ( null === $authorId ? 'all' : (string) $authorId ) );
		$cached   = wp_cache_get( $cacheKey, WpObjectCache::GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$wpdb             = $this->wpdb;
		$postsTable       = $wpdb->posts;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );
		$progressTable    = Schema::validateTable( Schema::progressTable( $wpdb->prefix ), $wpdb->prefix );
		$courseType       = PostTypes::COURSE;

		$archived = PostTypes::STATUS_ARCHIVED;

		if ( null !== $authorId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$totalCourses = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i
					WHERE post_type = %s AND post_author = %d AND post_status IN ('draft','publish',%s)",
					$postsTable,
					$courseType,
					$authorId,
					$archived
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$courseIdRows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM %i
					WHERE post_type = %s AND post_author = %d AND post_status IN ('draft','publish',%s)",
					$postsTable,
					$courseType,
					$authorId,
					$archived
				)
			);
			$courseIds    = array_map( 'intval', is_array( $courseIdRows ) ? $courseIdRows : array() );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$totalCourses = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i
					WHERE post_type = %s AND post_status IN ('draft','publish',%s)",
					$postsTable,
					$courseType,
					$archived
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$courseIdRows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM %i
					WHERE post_type = %s AND post_status IN ('draft','publish',%s)",
					$postsTable,
					$courseType,
					$archived
				)
			);
			$courseIds    = array_map( 'intval', is_array( $courseIdRows ) ? $courseIdRows : array() );
		}

		$studentsTotal    = 0;
		$enrollmentsTotal = 0;
		$completionsTotal = 0;
		$activity         = array();

		if ( array() !== $courseIds ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$studentsTotal = (int) $wpdb->get_var(
				$wpdb->prepare(
					sprintf(
						"SELECT COUNT(DISTINCT user_id) FROM %%i WHERE course_id IN (%s) AND status = 'active'",
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable ), $courseIds )
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$enrollmentsTotal = (int) $wpdb->get_var(
				$wpdb->prepare(
					sprintf(
						'SELECT COUNT(*) FROM %%i WHERE course_id IN (%s)',
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable ), $courseIds )
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$completionsTotal = (int) $wpdb->get_var(
				$wpdb->prepare(
					sprintf(
						'SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND completed_at IS NOT NULL',
						implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
					),
					array_merge( array( $enrollmentsTable ), $courseIds )
				)
			);
			$activity         = $this->getAuthorStats( $authorId ?? 0, $courseIds )['activity'] ?? array();
		}

		$lessonsCompleted = 0;

		if ( null !== $authorId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$lessonsCompleted = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i p
					INNER JOIN %i c ON c.ID = p.course_id AND c.post_type = %s
					WHERE c.post_author = %d',
					$progressTable,
					$postsTable,
					$courseType,
					$authorId
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom LMS table; object-cached at method level.
			$lessonsCompleted = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i',
					$progressTable
				)
			);
		}

		$result = array(
			'total_courses'     => $totalCourses,
			'total_students'    => $studentsTotal,
			'total_enrollments' => $enrollmentsTotal,
			'total_completions' => $completionsTotal,
			'lessons_completed' => $lessonsCompleted,
			'activity'          => $activity,
		);

		wp_cache_set( $cacheKey, $result, WpObjectCache::GROUP, WpObjectCache::DASHBOARD_TTL );

		return $result;
	}
}
