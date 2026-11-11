<?php
declare(strict_types=1);

namespace MintLMS\Application\Lesson;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Domain\Course\CourseAccessRules;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Drip\DripAccessEvaluator;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Domain\Shared\Clock;

final class LessonAccessService {

	public function __construct(
		private LessonRepositoryInterface $lessonRepository,
		private EnrollmentRepositoryInterface $enrollmentRepository,
		private ProgressRepositoryInterface $progressRepository,
		private Clock $clock,
		private ?CourseRepositoryInterface $courseRepository = null,
		private ?SectionRepositoryInterface $sectionRepository = null,
	) {
	}

	public function assertCanAccessLesson( int $userId, int $lessonId, bool $allowPreviewWithoutEnrollment = true ): void {
		$lesson = $this->lessonRepository->findById( $lessonId );

		if ( null === $lesson ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		$course       = null !== $this->courseRepository ? $this->courseRepository->findById( $lesson->courseId ) : null;
		$publicBrowse = null !== $course && $course->enrollmentType->allowsPublicBrowse();
		$isEnrolled   = $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $lesson->courseId );

		if ( ! $isEnrolled ) {
			if ( $allowPreviewWithoutEnrollment && ( $lesson->isPreview || $publicBrowse ) ) {
				return;
			}

			throw new ForbiddenException( 'You must be enrolled to access this lesson.' );
		}

		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $lesson->courseId );
		$now        = $this->clock->now();

		if ( null !== $course ) {
			if ( CourseAccessRules::isBeforeAccessStart( $course->settings, $now ) ) {
				throw new ForbiddenException( 'This course has not opened yet.' );
			}

			if ( CourseAccessRules::isAfterAccessEnd( $course->settings, $now ) ) {
				throw new ForbiddenException( 'This course is no longer available.' );
			}
		}

		if (
			null !== $enrollment
			&& EnrollmentStatus::Active === $enrollment->status
			&& CourseAccessRules::isEnrollmentExpired( $enrollment->expiresAt, $now )
		) {
			throw new ForbiddenException( 'Your access to this course has expired.' );
		}

		if ( $this->isDripLocked( $userId, $lessonId ) ) {
			throw new ForbiddenException( 'This lesson is not available yet.' );
		}

		if ( null !== $course && $this->isProgressionLocked( $userId, $lesson->courseId, $lessonId, $course->settings ) ) {
			throw new ForbiddenException( 'Finish the previous lesson first.' );
		}
	}

	public function isDripLocked( int $userId, int $lessonId ): bool {
		$lesson = $this->lessonRepository->findById( $lessonId );

		if ( null === $lesson || $userId <= 0 ) {
			return false;
		}

		if ( ! $this->progressRepository->isUserEnrolled( $userId, $lesson->courseId ) ) {
			return false;
		}

		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $lesson->courseId );

		return DripAccessEvaluator::isLocked(
			$lesson->availableAfterDays,
			$enrollment?->enrolledAt,
			$this->clock->now(),
			true,
		);
	}

	/**
	 * @param \MintLMS\Domain\Course\CourseSettings $settings
	 */
	public function isProgressionLocked( int $userId, int $courseId, int $lessonId, $settings ): bool {
		if ( ! $settings->isLinear() || null === $this->sectionRepository || $userId <= 0 ) {
			return false;
		}

		$ordered   = $this->orderedLessonIds( $courseId );
		$completed = $this->progressRepository->getCompletedLessonIds( $userId, $courseId );

		return CourseAccessRules::isLessonBlockedByProgression( $settings, $lessonId, $ordered, $completed );
	}

	/**
	 * @return list<int>
	 */
	public function orderedLessonIds( int $courseId ): array {
		if ( null === $this->sectionRepository ) {
			return array();
		}

		$rows = $this->sectionRepository->loadStructureRows( $courseId, true );

		/** @var array<int, list<\MintLMS\Domain\Lesson\Lesson>> $bySection */
		$bySection = array();
		$sectionMeta = array();

		foreach ( $rows as $row ) {
			$section = $row['section'];
			$lesson  = $row['lesson'] ?? null;
			$sectionMeta[ $section->id ] = $section;
			if ( ! isset( $bySection[ $section->id ] ) ) {
				$bySection[ $section->id ] = array();
			}
			if ( null !== $lesson ) {
				$bySection[ $section->id ][] = $lesson;
			}
		}

		$sectionIds = array_keys( $bySection );
		usort(
			$sectionIds,
			static function ( int $a, int $b ) use ( $sectionMeta ): int {
				if ( 0 === $a && 0 !== $b ) {
					return -1;
				}
				if ( 0 !== $a && 0 === $b ) {
					return 1;
				}
				$sa = $sectionMeta[ $a ]->sortOrder ?? 0;
				$sb = $sectionMeta[ $b ]->sortOrder ?? 0;

				return $sa <=> $sb;
			}
		);

		$ids = array();
		foreach ( $sectionIds as $sectionId ) {
			$lessons = $bySection[ $sectionId ];
			usort(
				$lessons,
				static fn( $a, $b ): int => $a->sortOrder <=> $b->sortOrder
			);
			foreach ( $lessons as $lesson ) {
				$ids[] = (int) $lesson->id;
			}
		}

		return $ids;
	}
}
