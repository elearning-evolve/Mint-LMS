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
						"SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND status = 'active' AND enrolled_at >= %%s",
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
					WHERE e.course_id IN (%s) AND e.status = 'active' AND e.completed_at IS NOT NULL
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
					WHERE e.course_id IN (%s) AND e.status = 'active'
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
	 * @param list<int> $courseIds
	 * @return list<array{user_id: int, course_id: int, course_title: string, enrolled_at: string, enrollment_id: int}>
	 */
	public function getRecentActiveEnrollments( array $courseIds, int $limit = 5 ): array {
		$courseIds = array_values(
			array_filter(
				array_map( 'intval', $courseIds ),
				static fn( int $id ): bool => $id > 0
			)
		);
		$limit     = max( 1, min( 20, $limit ) );

		if ( array() === $courseIds ) {
			return array();
		}

		$cacheKey = WpObjectCache::dashboardCacheKey(
			'recent_active_enrollments_' . md5( (string) wp_json_encode( array( $courseIds, $limit ) ) )
		);
		$cached   = wp_cache_get( $cacheKey, WpObjectCache::GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$wpdb             = $this->wpdb;
		$postsTable       = $wpdb->posts;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );
		$courseType       = PostTypes::COURSE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; object-cached at method level.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				sprintf(
					"SELECT e.id AS enrollment_id, e.user_id, e.course_id, c.post_title AS course_title, e.enrolled_at
					FROM %%i e
					INNER JOIN %%i c ON c.ID = e.course_id AND c.post_type = %%s
					WHERE e.course_id IN (%s) AND e.status = 'active'
					ORDER BY e.enrolled_at DESC
					LIMIT %%d",
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge(
					array( $enrollmentsTable, $postsTable, $courseType ),
					$courseIds,
					array( $limit )
				)
			)
		);

		$result = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$result[] = array(
					'enrollment_id' => (int) $row->enrollment_id,
					'user_id'       => (int) $row->user_id,
					'course_id'     => (int) $row->course_id,
					'course_title'  => (string) $row->course_title,
					'enrolled_at'   => (string) $row->enrolled_at,
				);
			}
		}

		wp_cache_set( $cacheKey, $result, WpObjectCache::GROUP, WpObjectCache::DASHBOARD_TTL );

		return $result;
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

	/**
	 * @return array<string, mixed>
	 */
	public function getReportsDashboard( ?int $authorId ): array {
		$cacheKey = WpObjectCache::dashboardCacheKey(
			'reports_dashboard_' . ( null === $authorId ? 'all' : (string) $authorId )
		);
		$cached   = wp_cache_get( $cacheKey, WpObjectCache::GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$summary   = $this->getReportsSummary( $authorId );
		$courseIds = $this->resolveReportCourseIds( $authorId );
		$now       = time();

		$ranges = array(
			'7d'  => array(
				'label'     => __( '7 days', 'mint-lms' ),
				'since'     => gmdate( 'Y-m-d H:i:s', $now - ( 7 * DAY_IN_SECONDS ) ),
				'trend'     => __( 'this week', 'mint-lms' ),
				'chartNote' => __( 'Per day, last 7 days', 'mint-lms' ),
				'buckets'   => $this->buildDayBuckets( $now, 7 ),
			),
			'30d' => array(
				'label'     => __( '30 days', 'mint-lms' ),
				'since'     => gmdate( 'Y-m-d H:i:s', $now - ( 30 * DAY_IN_SECONDS ) ),
				'trend'     => __( 'this month', 'mint-lms' ),
				'chartNote' => __( 'Per week, last 8 weeks', 'mint-lms' ),
				'buckets'   => $this->buildWeekBuckets( $now, 8 ),
			),
			'90d' => array(
				'label'     => __( '90 days', 'mint-lms' ),
				'since'     => gmdate( 'Y-m-d H:i:s', $now - ( 90 * DAY_IN_SECONDS ) ),
				'trend'     => __( 'in 90 days', 'mint-lms' ),
				'chartNote' => __( 'Per month, last 3 months', 'mint-lms' ),
				'buckets'   => $this->buildMonthBuckets( $now, 3 ),
			),
			'all' => array(
				'label'     => __( 'All time', 'mint-lms' ),
				'since'     => null,
				'trend'     => __( 'Since day one', 'mint-lms' ),
				'chartNote' => __( 'Per quarter, since you started', 'mint-lms' ),
				'buckets'   => $this->buildQuarterBuckets( $now, 4 ),
			),
		);

		$byRange = array();
		foreach ( $ranges as $key => $meta ) {
			$deltas = $this->countReportDeltas( $authorId, $courseIds, $meta['since'] );
			$bars   = $this->countEnrollmentBars( $courseIds, $meta['buckets'] );

			$byRange[ $key ] = array(
				'label'     => $meta['label'],
				'chartNote' => $meta['chartNote'],
				'bars'      => $bars,
				'trends'    => array(
					'courses'           => null === $meta['since']
						? (string) $meta['trend']
						: sprintf( '+%d %s', $deltas['courses'], $meta['trend'] ),
					'students'          => null === $meta['since']
						? (string) $meta['trend']
						: sprintf( '+%d %s', $deltas['students'], $meta['trend'] ),
					'enrollments'       => null === $meta['since']
						? (string) $meta['trend']
						: sprintf( '+%d %s', $deltas['enrollments'], $meta['trend'] ),
					'completions'       => null === $meta['since']
						? (string) $meta['trend']
						: sprintf( '+%d %s', $deltas['completions'], $meta['trend'] ),
					'lessons_completed' => null === $meta['since']
						? (string) $meta['trend']
						: sprintf( '+%d %s', $deltas['lessons'], $meta['trend'] ),
				),
			);
		}

		$result = array(
			'totals'      => array(
				'courses'           => (int) ( $summary['total_courses'] ?? 0 ),
				'students'          => (int) ( $summary['total_students'] ?? 0 ),
				'enrollments'       => (int) ( $summary['total_enrollments'] ?? 0 ),
				'completions'       => (int) ( $summary['total_completions'] ?? 0 ),
				'lessons_completed' => (int) ( $summary['lessons_completed'] ?? 0 ),
			),
			'ranges'      => $byRange,
			'top_courses' => $this->buildTopCourses( $courseIds, 4 ),
			'activity'    => $this->buildReportActivity( $courseIds, 6 ),
		);

		wp_cache_set( $cacheKey, $result, WpObjectCache::GROUP, WpObjectCache::DASHBOARD_TTL );

		return $result;
	}

	/**
	 * @return list<int>
	 */
	private function resolveReportCourseIds( ?int $authorId ): array {
		$wpdb       = $this->wpdb;
		$postsTable = $wpdb->posts;
		$courseType = PostTypes::COURSE;
		$archived   = PostTypes::STATUS_ARCHIVED;

		if ( null !== $authorId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM %i
					WHERE post_type = %s AND post_author = %d AND post_status IN ('draft','publish',%s)",
					$postsTable,
					$courseType,
					$authorId,
					$archived
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM %i
					WHERE post_type = %s AND post_status IN ('draft','publish',%s)",
					$postsTable,
					$courseType,
					$archived
				)
			);
		}

		return array_values( array_filter( array_map( 'intval', is_array( $rows ) ? $rows : array() ) ) );
	}

	/**
	 * @param list<int> $courseIds
	 * @return array{courses: int, students: int, enrollments: int, completions: int, lessons: int}
	 */
	private function countReportDeltas( ?int $authorId, array $courseIds, ?string $since ): array {
		$empty = array(
			'courses'     => 0,
			'students'    => 0,
			'enrollments' => 0,
			'completions' => 0,
			'lessons'     => 0,
		);

		if ( null === $since ) {
			return $empty;
		}

		$wpdb             = $this->wpdb;
		$postsTable       = $wpdb->posts;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );
		$progressTable    = Schema::validateTable( Schema::progressTable( $wpdb->prefix ), $wpdb->prefix );
		$courseType       = PostTypes::COURSE;
		$archived         = PostTypes::STATUS_ARCHIVED;

		if ( null !== $authorId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$empty['courses'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i
					WHERE post_type = %s AND post_author = %d AND post_status IN ('draft','publish',%s)
						AND post_date_gmt >= %s",
					$postsTable,
					$courseType,
					$authorId,
					$archived,
					$since
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$empty['courses'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i
					WHERE post_type = %s AND post_status IN ('draft','publish',%s)
						AND post_date_gmt >= %s",
					$postsTable,
					$courseType,
					$archived,
					$since
				)
			);
		}

		if ( array() === $courseIds ) {
			return $empty;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$empty['enrollments'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				sprintf(
					'SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND enrolled_at >= %%s',
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge( array( $enrollmentsTable ), $courseIds, array( $since ) )
			)
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$empty['students'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				sprintf(
					'SELECT COUNT(DISTINCT user_id) FROM %%i WHERE course_id IN (%s) AND enrolled_at >= %%s',
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge( array( $enrollmentsTable ), $courseIds, array( $since ) )
			)
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$empty['completions'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				sprintf(
					'SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND completed_at IS NOT NULL AND completed_at >= %%s',
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge( array( $enrollmentsTable ), $courseIds, array( $since ) )
			)
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$empty['lessons'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				sprintf(
					'SELECT COUNT(*) FROM %%i WHERE course_id IN (%s) AND completed_at >= %%s',
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge( array( $progressTable ), $courseIds, array( $since ) )
			)
		);

		return $empty;
	}

	/**
	 * @return list<array{key: string, label: string, start: string, end: string}>
	 */
	private function buildDayBuckets( int $now, int $days ): array {
		$buckets = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$ts        = $now - ( $i * DAY_IN_SECONDS );
			$start     = gmdate( 'Y-m-d 00:00:00', $ts );
			$end       = gmdate( 'Y-m-d 23:59:59', $ts );
			$buckets[] = array(
				'key'   => gmdate( 'Y-m-d', $ts ),
				'label' => wp_date( 'D', $ts ),
				'start' => $start,
				'end'   => $end,
			);
		}

		return $buckets;
	}

	/**
	 * @return list<array{key: string, label: string, start: string, end: string}>
	 */
	private function buildWeekBuckets( int $now, int $weeks ): array {
		$buckets = array();
		for ( $i = $weeks - 1; $i >= 0; $i-- ) {
			$endTs     = $now - ( $i * WEEK_IN_SECONDS );
			$startTs   = $endTs - WEEK_IN_SECONDS + 1;
			$buckets[] = array(
				'key'   => 'W' . ( $weeks - $i ),
				'label' => 'W' . ( $weeks - $i ),
				'start' => gmdate( 'Y-m-d H:i:s', $startTs ),
				'end'   => gmdate( 'Y-m-d H:i:s', $endTs ),
			);
		}

		return $buckets;
	}

	/**
	 * @return list<array{key: string, label: string, start: string, end: string}>
	 */
	private function buildMonthBuckets( int $now, int $months ): array {
		$buckets = array();
		for ( $i = $months - 1; $i >= 0; $i-- ) {
			$ts        = strtotime( '-' . $i . ' months', $now );
			$start     = gmdate( 'Y-m-01 00:00:00', $ts );
			$end       = gmdate( 'Y-m-t 23:59:59', $ts );
			$buckets[] = array(
				'key'   => gmdate( 'Y-m', $ts ),
				'label' => wp_date( 'M', $ts ),
				'start' => $start,
				'end'   => $end,
			);
		}

		return $buckets;
	}

	/**
	 * @return list<array{key: string, label: string, start: string, end: string}>
	 */
	private function buildQuarterBuckets( int $now, int $quarters ): array {
		$buckets = array();
		$month   = (int) gmdate( 'n', $now );
		$year    = (int) gmdate( 'Y', $now );
		$qIndex  = (int) ceil( $month / 3 );

		for ( $i = $quarters - 1; $i >= 0; $i-- ) {
			$q = $qIndex - $i;
			$y = $year;
			while ( $q <= 0 ) {
				$q += 4;
				--$y;
			}
			$startMonth = ( ( $q - 1 ) * 3 ) + 1;
			$endMonth   = $startMonth + 2;
			$start      = sprintf( '%04d-%02d-01 00:00:00', $y, $startMonth );
			$end        = gmdate( 'Y-m-t 23:59:59', strtotime( sprintf( '%04d-%02d-01', $y, $endMonth ) ) );
			$buckets[]  = array(
				'key'   => 'Q' . $q,
				'label' => 'Q' . $q,
				'start' => $start,
				'end'   => $end,
			);
		}

		return $buckets;
	}

	/**
	 * @param list<int> $courseIds
	 * @param list<array{key: string, label: string, start: string, end: string}> $buckets
	 * @return list<array{label: string, value: int}>
	 */
	private function countEnrollmentBars( array $courseIds, array $buckets ): array {
		$bars = array();
		foreach ( $buckets as $bucket ) {
			$bars[] = array(
				'label' => (string) $bucket['label'],
				'value' => 0,
			);
		}

		if ( array() === $courseIds || array() === $buckets ) {
			return $bars;
		}

		$wpdb             = $this->wpdb;
		$enrollmentsTable = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );
		$rangeStart       = $buckets[0]['start'];
		$rangeEnd         = $buckets[ count( $buckets ) - 1 ]['end'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				sprintf(
					'SELECT enrolled_at FROM %%i
					WHERE course_id IN (%s) AND enrolled_at >= %%s AND enrolled_at <= %%s',
					implode( ',', array_fill( 0, count( $courseIds ), '%d' ) )
				),
				array_merge( array( $enrollmentsTable ), $courseIds, array( $rangeStart, $rangeEnd ) )
			)
		);

		if ( ! is_array( $rows ) ) {
			return $bars;
		}

		foreach ( $rows as $row ) {
			$ts = strtotime( (string) $row->enrolled_at );
			if ( false === $ts ) {
				continue;
			}
			foreach ( $buckets as $index => $bucket ) {
				$start = strtotime( (string) $bucket['start'] );
				$end   = strtotime( (string) $bucket['end'] );
				if ( false !== $start && false !== $end && $ts >= $start && $ts <= $end ) {
					++$bars[ $index ]['value'];
					break;
				}
			}
		}

		return $bars;
	}

	/**
	 * @param list<int> $courseIds
	 * @return list<array{id: int, title: string, letter: string, students: int, pct: int, bg: string, ink: string}>
	 */
	private function buildTopCourses( array $courseIds, int $limit = 4 ): array {
		if ( array() === $courseIds ) {
			return array();
		}

		$stats = $this->getCourseStatsBatch( $courseIds );
		$rows  = array();

		foreach ( $courseIds as $courseId ) {
			$post = get_post( $courseId );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$rowStats = $stats[ $courseId ] ?? array();
			$rows[]   = array(
				'id'       => $courseId,
				'title'    => (string) $post->post_title,
				'letter'   => \MintLMS\Infrastructure\Ui\MintUi::courseInitial( (string) $post->post_title ),
				'students' => (int) ( $rowStats['students'] ?? 0 ),
				'pct'      => (int) ( $rowStats['completion_pct'] ?? 0 ),
			);
		}

		usort(
			$rows,
			static function ( array $a, array $b ): int {
				return $b['students'] <=> $a['students'];
			}
		);

		$tints = array(
			array(
				'bg'  => '#F8E7EF',
				'ink' => '#A81E5C',
			),
			array(
				'bg'  => '#E8F5EC',
				'ink' => '#1E7A4C',
			),
			array(
				'bg'  => '#E6F1FF',
				'ink' => '#1A5AA8',
			),
			array(
				'bg'  => '#F1EDFB',
				'ink' => '#5B2BFF',
			),
		);

		$top = array_slice( $rows, 0, max( 1, $limit ) );
		foreach ( $top as $index => $row ) {
			$tint                 = $tints[ $index % count( $tints ) ];
			$top[ $index ]['bg']  = $tint['bg'];
			$top[ $index ]['ink'] = $tint['ink'];
		}

		return $top;
	}

	/**
	 * @param list<int> $courseIds
	 * @return list<array{user_id: int, name: string, initials: string, action: string, what: string, when: string, occurred_at: string, bg: string, ink: string}>
	 */
	private function buildReportActivity( array $courseIds, int $limit = 6 ): array {
		if ( array() === $courseIds ) {
			return array();
		}

		$raw = $this->getAuthorStats( 0, $courseIds )['activity'] ?? array();
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$hues = array(
			array(
				'bg'  => '#EDE9FF',
				'ink' => '#3F1FB8',
			),
			array(
				'bg'  => '#E3EEFF',
				'ink' => '#1A5AA8',
			),
			array(
				'bg'  => '#E3F4EA',
				'ink' => '#0A6B4C',
			),
			array(
				'bg'  => '#FFF1E0',
				'ink' => '#B0620A',
			),
			array(
				'bg'  => '#FBE6EE',
				'ink' => '#A81E5C',
			),
		);

		$items = array();
		$index = 0;
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) || count( $items ) >= $limit ) {
				break;
			}

			$userId = (int) ( $row['user_id'] ?? 0 );
			$user   = get_userdata( $userId );
			$name   = $user instanceof \WP_User ? (string) $user->display_name : __( 'A student', 'mint-lms' );
			$type   = (string) ( $row['type'] ?? '' );
			$course = (string) ( $row['course_title'] ?? '' );
			$whenAt = (string) ( $row['occurred_at'] ?? '' );
			$hue    = $hues[ $index % count( $hues ) ];

			if ( 'completed' === $type ) {
				$action = __( 'finished', 'mint-lms' );
			} elseif ( 'enrolled' === $type ) {
				$action = __( 'joined', 'mint-lms' );
			} else {
				$action = __( 'made progress in', 'mint-lms' );
			}

			$items[] = array(
				'user_id'     => $userId,
				'name'        => $name,
				'initials'    => \MintLMS\Infrastructure\Ui\MintUi::initials( $name ),
				'action'      => $action,
				'what'        => $course,
				'when'        => '' !== $whenAt
					? \MintLMS\Infrastructure\Ui\MintUi::relativeTimeShort( new \DateTimeImmutable( $whenAt ) )
					: '',
				'occurred_at' => $whenAt,
				'bg'          => $hue['bg'],
				'ink'         => $hue['ink'],
			);
			++$index;
		}

		return $items;
	}
}
