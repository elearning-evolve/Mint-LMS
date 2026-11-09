<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Enrollment\Enrollment;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbEnrollmentRepository implements EnrollmentRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findById( int $id ): ?Enrollment {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, user_id, course_id, status, enrolled_at, expires_at, completed_at
				FROM %i
				WHERE id = %d',
				$table,
				$id
			)
		);

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToEnrollment( $row );
	}

	public function findByUserAndCourse( int $userId, int $courseId ): ?Enrollment {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, user_id, course_id, status, enrolled_at, expires_at, completed_at
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

		return $this->mapRowToEnrollment( $row );
	}

	public function save( Enrollment $enrollment ): Enrollment {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );

		$data = array(
			'user_id'      => $enrollment->userId,
			'course_id'    => $enrollment->courseId,
			'status'       => $enrollment->status->value,
			'enrolled_at'  => $enrollment->enrolledAt->format( 'Y-m-d H:i:s' ),
			'expires_at'   => null !== $enrollment->expiresAt ? $enrollment->expiresAt->format( 'Y-m-d H:i:s' ) : null,
			'completed_at' => null !== $enrollment->completedAt ? $enrollment->completedAt->format( 'Y-m-d H:i:s' ) : null,
		);

		if ( 0 === $enrollment->id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table,
				$data,
				array( '%d', '%d', '%s', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert enrollment.' );
			}

			$created = new Enrollment(
				(int) $wpdb->insert_id,
				$enrollment->userId,
				$enrollment->courseId,
				$enrollment->status,
				$enrollment->enrolledAt,
				$enrollment->expiresAt,
				$enrollment->completedAt,
			);
			/**
			 * Fires after enrollment rows change so dashboard aggregates can refresh.
			 */
			do_action( 'mintlms_dashboard_cache_invalidate' );

			return $created;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table,
			$data,
			array( 'id' => $enrollment->id ),
			array( '%d', '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update enrollment.' );
		}

		/**
		 * Fires after enrollment rows change so dashboard aggregates can refresh.
		 */
		do_action( 'mintlms_dashboard_cache_invalidate' );

		return $enrollment;
	}

	public function listByCourse(
		int $courseId,
		int $page,
		int $perPage,
		?EnrollmentStatus $status = null,
	): array {
		$wpdb   = $this->wpdb;
		$table  = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );
		$offset = ( $page - 1 ) * $perPage;

		$where  = array( 'course_id = %d' );
		$params = array( $courseId );

		if ( null !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status->value;
		}

		$whereSql = implode( ' AND ', $where );

		$countSql = 'SELECT COUNT(*) FROM %i WHERE ' . $whereSql;
		$listSql  = 'SELECT id, user_id, course_id, status, enrolled_at, expires_at, completed_at
			FROM %i
			WHERE ' . $whereSql . '
			ORDER BY enrolled_at DESC
			LIMIT %d OFFSET %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built from validated table (%i) + whitelist WHERE fragments.
		$total = (int) $wpdb->get_var( $wpdb->prepare( $countSql, $table, ...$params ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built from validated table (%i) + whitelist WHERE fragments.
			$wpdb->prepare( $listSql, $table, ...array_merge( $params, array( $perPage, $offset ) ) )
		);

		$enrollments = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$enrollments[] = $this->mapRowToEnrollment( $row );
			}
		}

		return array(
			'enrollments' => $enrollments,
			'total'       => $total,
		);
	}

	public function listByUser(
		int $userId,
		int $page,
		int $perPage,
		?EnrollmentStatus $status = null,
	): array {
		$wpdb   = $this->wpdb;
		$table  = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );
		$offset = ( $page - 1 ) * $perPage;

		$where  = array( 'user_id = %d' );
		$params = array( $userId );

		if ( null !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status->value;
		}

		$whereSql = implode( ' AND ', $where );

		$countSql = 'SELECT COUNT(*) FROM %i WHERE ' . $whereSql;
		$listSql  = 'SELECT id, user_id, course_id, status, enrolled_at, expires_at, completed_at
			FROM %i
			WHERE ' . $whereSql . '
			ORDER BY enrolled_at DESC
			LIMIT %d OFFSET %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built from validated table (%i) + whitelist WHERE fragments.
		$total = (int) $wpdb->get_var( $wpdb->prepare( $countSql, $table, ...$params ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built from validated table (%i) + whitelist WHERE fragments.
			$wpdb->prepare( $listSql, $table, ...array_merge( $params, array( $perPage, $offset ) ) )
		);

		$enrollments = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$enrollments[] = $this->mapRowToEnrollment( $row );
			}
		}

		return array(
			'enrollments' => $enrollments,
			'total'       => $total,
		);
	}

	public function getProgressPercentagesForCourse( int $courseId, array $userIds ): array {
		$wpdb = $this->wpdb;
		if ( array() === $userIds ) {
			return array();
		}

		$table = Schema::validateTable( Schema::progressSummaryTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				sprintf(
					'SELECT user_id, pct_complete
					FROM %%i
					WHERE course_id = %%d AND user_id IN (%s)',
					implode( ',', array_fill( 0, count( $userIds ), '%d' ) )
				),
				array_merge( array( $table, $courseId ), $userIds )
			)
		);
		$map  = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$map[ (int) $row->user_id ] = (float) $row->pct_complete;
			}
		}

		return $map;
	}

	public function countActiveByCourse( int $courseId ): int {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::enrollmentsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE course_id = %d AND status = %s',
				$table,
				$courseId,
				EnrollmentStatus::Active->value
			)
		);
	}

	private function mapRowToEnrollment( object $row ): Enrollment {
		$expiresAt   = null;
		$completedAt = null;

		if ( null !== $row->expires_at && '' !== $row->expires_at ) {
			$expiresAt = new \DateTimeImmutable( (string) $row->expires_at );
		}

		if ( null !== $row->completed_at && '' !== $row->completed_at ) {
			$completedAt = new \DateTimeImmutable( (string) $row->completed_at );
		}

		return new Enrollment(
			(int) $row->id,
			(int) $row->user_id,
			(int) $row->course_id,
			EnrollmentStatus::from( (string) $row->status ),
			new \DateTimeImmutable( (string) $row->enrolled_at ),
			$expiresAt,
			$completedAt,
		);
	}
}
