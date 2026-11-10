<?php
declare(strict_types=1);

namespace MintLMS\Application\Admin;

use MintLMS\Application\Admin\Dto\CreatorDashboardDto;
use MintLMS\Application\Admin\Dto\DashboardActivityItemDto;
use MintLMS\Application\Admin\Dto\DashboardCourseRowDto;
use MintLMS\Application\Admin\Dto\DashboardMetricsDto;
use MintLMS\Application\Admin\Dto\NeedsYouCourseDto;
use MintLMS\Application\Contract\AdminDashboardRepositoryInterface;
use MintLMS\Application\Contract\DashboardPresentationInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;

final class CreatorDashboardService {

	private const DASHBOARD_COURSE_LIMIT = 4;

	public function __construct(
		private CourseRepositoryInterface $courseRepository,
		private AdminDashboardRepositoryInterface $dashboardRepository,
		private DashboardPresentationInterface $presentation,
		private UserLookupInterface $userLookup,
	) {
	}

	public function getDashboard( int $authorId ): CreatorDashboardDto {
		// Pull enough CPT courses to cover the dashboard window; sort by last edit.
		$listed  = $this->courseRepository->list( 1, 200, $authorId );
		$courses = $listed['courses'];

		usort(
			$courses,
			static function ( Course $a, Course $b ): int {
				return $b->updatedAt <=> $a->updatedAt;
			}
		);

		$courseIds = array_map( static fn( Course $c ): int => $c->id, $courses );
		$stats     = $this->dashboardRepository->getAuthorStats( $authorId, $courseIds );

		$rows     = array();
		$index    = 0;
		$needsYou = null;

		foreach ( $courses as $course ) {
			$courseStats = $stats['courses'][ $course->id ] ?? array(
				'students'       => 0,
				'completion_pct' => null,
				'lesson_count'   => 0,
				'empty_sections' => 0,
			);

			$hue = $this->presentation->hueByIndex( $index );
			++$index;

			$isDraft = CourseStatus::Published !== $course->status;
			$pct     = $courseStats['completion_pct'];

			$rows[] = new DashboardCourseRowDto(
				$course->id,
				$course->title,
				$course->status->value,
				$isDraft ? '—' : (string) $courseStats['students'],
				$isDraft ? null : $pct,
				$this->presentation->editedLabel( $course->updatedAt ),
				$this->courseMetaLine( $courseStats, $course->status->value ),
				$hue['bg'],
				$hue['ink'],
				$this->presentation->courseInitial( $course->title ),
			);

			if ( null === $needsYou ) {
				$blocker = $this->blockerMessage( $courseStats );
				if ( null === $blocker ) {
					$blocker = $isDraft
						? __( 'Not published yet', 'mint-lms' )
						: __( 'Pick up where you left off', 'mint-lms' );
				}

				$needsYou = new NeedsYouCourseDto(
					$course->id,
					$course->title,
					$blocker,
					$this->presentation->editedLabel( $course->updatedAt ),
					$isDraft ? __( 'draft', 'mint-lms' ) : __( 'published', 'mint-lms' ),
				);
			}
		}

		$coursesTotal   = count( $rows );
		$displayCourses = array_slice( $rows, 0, self::DASHBOARD_COURSE_LIMIT );
		$activity       = $this->buildActivity( $stats['activity'] ?? array() );

		return new CreatorDashboardDto(
			array() === $rows,
			new DashboardMetricsDto(
				$stats['students_total'],
				$stats['finished_pct'],
				(int) ( $stats['finished_count'] ?? 0 ),
				(int) ( $stats['started_count'] ?? 0 ),
				$stats['published_count'],
				$stats['draft_count'],
				$stats['lessons_done_7d'],
				$stats['students_delta'],
				$stats['finished_delta'],
			),
			$displayCourses,
			$needsYou,
			$activity,
			$stats['signal_line'],
			$coursesTotal,
		);
	}

	/**
	 * @param array<string, mixed> $courseStats
	 */
	private function blockerMessage( array $courseStats ): ?string {
		if ( ( $courseStats['lesson_count'] ?? 0 ) === 0 ) {
			return __( 'No lessons yet', 'mint-lms' );
		}

		if ( ( $courseStats['empty_sections'] ?? 0 ) > 0 ) {
			$empty = (int) $courseStats['empty_sections'];
			if ( 1 === $empty ) {
				return __( 'A lesson group has no lessons yet', 'mint-lms' );
			}

			return sprintf(
				/* translators: %d: empty lesson group count */
				__( '%d lesson groups need lessons', 'mint-lms' ),
				$empty
			);
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $courseStats
	 */
	private function courseMetaLine( array $courseStats, string $status ): string {
		$parts = array();

		$lessonCount = (int) ( $courseStats['lesson_count'] ?? 0 );

		if ( $lessonCount > 0 ) {
			$parts[] = 1 === $lessonCount
				? __( '1 lesson', 'mint-lms' )
				: sprintf(
					/* translators: %d: lesson count */
					__( '%d lessons', 'mint-lms' ),
					$lessonCount
				);
		}

		$parts[] = 'published' === $status ? __( 'Published', 'mint-lms' ) : __( 'Draft', 'mint-lms' );

		return implode( ' · ', $parts );
	}

	/**
	 * @param list<array<string, mixed>> $raw
	 * @return list<DashboardActivityItemDto>
	 */
	private function buildActivity( array $raw ): array {
		$items = array();
		$index = 0;

		foreach ( $raw as $row ) {
			$name = $this->userLookup->getDisplayName( (int) $row['user_id'] ) ?? __( 'A student', 'mint-lms' );
			$hue  = $this->presentation->hueByIndex( $index );
			++$index;
			$course   = (string) ( $row['course_title'] ?? '' );
			$sentence = '';

			if ( 'completed' === ( $row['type'] ?? '' ) ) {
				$sentence = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s finished %2$s', 'mint-lms' ),
					$name,
					$course
				);
			} elseif ( 'enrolled' === ( $row['type'] ?? '' ) ) {
				$sentence = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s enrolled in %2$s', 'mint-lms' ),
					$name,
					$course
				);
			} else {
				$sentence = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s made progress in %2$s', 'mint-lms' ),
					$name,
					$course
				);
			}

			$items[] = new DashboardActivityItemDto(
				$this->presentation->initials( $name ),
				$sentence,
				$this->presentation->relativeTimeShort( new \DateTimeImmutable( (string) $row['occurred_at'] ) ),
				$hue['bg'],
				$hue['ink'],
			);
		}

		return $items;
	}
}
