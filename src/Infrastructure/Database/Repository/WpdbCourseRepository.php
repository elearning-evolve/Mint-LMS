<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseSettings;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbCourseRepository implements CourseRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findById( int $id ): ?Course {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::coursesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT *
				FROM %i
				WHERE id = %d',
				$table,
				$id
			)
		);
		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToCourse( $row );
	}

	public function findBySlug( string $slug ): ?Course {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::coursesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT *
				FROM %i
				WHERE slug = %s',
				$table,
				$slug
			)
		);
		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToCourse( $row );
	}

	public function save( Course $course ): Course {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::coursesTable( $wpdb->prefix ), $wpdb->prefix );
		$now   = $course->updatedAt->format( 'Y-m-d H:i:s' );

		if ( 0 === $course->id ) {
			$created      = $course->createdAt->format( 'Y-m-d H:i:s' );
			$settingsJson = wp_json_encode( $course->settings->toArray() );
			$settingsJson = is_string( $settingsJson ) ? $settingsJson : '{}';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table,
				array(
					'title'             => $course->title,
					'slug'              => $course->slug,
					'description'       => $course->description,
					'featured_image_id' => $course->featuredImageId,
					'status'            => $course->status->value,
					'enrollment_type'   => $course->enrollmentType->value,
					'settings_json'     => $settingsJson,
					'author_id'         => $course->authorId,
					'created_at'        => $created,
					'updated_at'        => $now,
				),
				array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert course.' );
			}

			return new Course(
				(int) $wpdb->insert_id,
				$course->title,
				$course->slug,
				$course->description,
				$course->featuredImageId,
				$course->status,
				$course->enrollmentType,
				$course->authorId,
				$course->createdAt,
				$course->updatedAt,
				$course->settings,
			);
		}

		$settingsJson = wp_json_encode( $course->settings->toArray() );
		$settingsJson = is_string( $settingsJson ) ? $settingsJson : '{}';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table,
			array(
				'title'             => $course->title,
				'slug'              => $course->slug,
				'description'       => $course->description,
				'featured_image_id' => $course->featuredImageId,
				'status'            => $course->status->value,
				'enrollment_type'   => $course->enrollmentType->value,
				'settings_json'     => $settingsJson,
				'updated_at'        => $now,
			),
			array( 'id' => $course->id ),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update course.' );
		}

		return $course;
	}

	public function delete( int $id ): bool {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::coursesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $deleted && $deleted > 0;
	}

	public function list( int $page, int $perPage, ?int $authorId = null, ?CourseStatus $status = null, ?string $search = null ): array {
		$wpdb   = $this->wpdb;
		$table  = Schema::validateTable( Schema::coursesTable( $wpdb->prefix ), $wpdb->prefix );
		$offset = ( $page - 1 ) * $perPage;

		$where  = array( '1=1' );
		$params = array();

		if ( null !== $authorId ) {
			$where[]  = 'author_id = %d';
			$params[] = $authorId;
		}

		if ( null !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status->value;
		} else {
			$where[]  = 'status != %s';
			$params[] = CourseStatus::Trashed->value;
		}

		if ( null !== $search && '' !== trim( $search ) ) {
			$where[]  = 'title LIKE %s';
			$params[] = '%' . $wpdb->esc_like( trim( $search ) ) . '%';
		}

		$whereSql = implode( ' AND ', $where );

		$countSql = 'SELECT COUNT(*) FROM %i WHERE ' . $whereSql;
		$listSql  = 'SELECT *
			FROM %i
			WHERE ' . $whereSql . '
			ORDER BY created_at DESC
			LIMIT %d OFFSET %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built from validated table (%i) + whitelist WHERE fragments.
		$total = (int) $wpdb->get_var( $wpdb->prepare( $countSql, $table, ...$params ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built from validated table (%i) + whitelist WHERE fragments.
			$wpdb->prepare( $listSql, $table, ...array_merge( $params, array( $perPage, $offset ) ) )
		);

		$courses = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$courses[] = $this->mapRowToCourse( $row );
			}
		}

		return array(
			'courses' => $courses,
			'total'   => $total,
		);
	}

	private function mapRowToCourse( object $row ): Course {
		$featuredImageId = null;

		if ( null !== $row->featured_image_id && '' !== $row->featured_image_id && (int) $row->featured_image_id > 0 ) {
			$featuredImageId = (int) $row->featured_image_id;
		}

		$settings = CourseSettings::defaults();

		if ( isset( $row->settings_json ) && is_string( $row->settings_json ) && '' !== $row->settings_json ) {
			$decoded  = json_decode( $row->settings_json, true );
			$settings = CourseSettings::fromArray( is_array( $decoded ) ? $decoded : null );
		}

		return new Course(
			(int) $row->id,
			(string) $row->title,
			(string) $row->slug,
			(string) ( $row->description ?? '' ),
			$featuredImageId,
			CourseStatus::from( (string) $row->status ),
			EnrollmentType::from( (string) $row->enrollment_type ),
			(int) $row->author_id,
			new \DateTimeImmutable( (string) $row->created_at ),
			new \DateTimeImmutable( (string) $row->updated_at ),
			$settings,
		);
	}
}
