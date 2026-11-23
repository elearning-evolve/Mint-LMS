<?php
declare(strict_types=1);

namespace MintLMS\Application\Lesson;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Domain\Course\CourseAccessRules;
use MintLMS\Domain\Course\CourseLessonSequence;
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

		// Front-end / progress: only published lessons (WP post_status), matching student outline.
		if ( function_exists( 'get_post_status' ) ) {
			$status = get_post_status( $lessonId );
			if ( false === $status || ( is_string( $status ) && 'publish' !== $status ) ) {
				throw new NotFoundException( 'Lesson not found.' );
			}
		}

		$course       = null !== $this->courseRepository ? $this->courseRepository->findById( $lesson->courseId ) : null;
		$publicBrowse = null !== $course && $course->enrollmentType->allowsPublicBrowse();
		$isEnrolled   = $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $lesson->courseId );

		if ( ! $isEnrolled ) {
			if ( $allowPreviewWithoutEnrollment && ( $lesson->isPreview || $publicBrowse ) ) {
				return;
			}

			$exception = new ForbiddenException( 'You must be enrolled to access this lesson.' );
			$exception->forReason( ForbiddenException::REASON_ENROLLMENT );
			throw $exception;
		}

		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $lesson->courseId );
		$now        = $this->clock->now();

		if ( null !== $course ) {
			if ( CourseAccessRules::isBeforeAccessStart( $course->settings, $now ) ) {
				$exception = new ForbiddenException( 'This course has not opened yet.' );
				$exception->forReason( ForbiddenException::REASON_UNAVAILABLE );
				throw $exception;
			}

			if ( CourseAccessRules::isAfterAccessEnd( $course->settings, $now ) ) {
				$exception = new ForbiddenException( 'This course is no longer available.' );
				$exception->forReason( ForbiddenException::REASON_UNAVAILABLE );
				throw $exception;
			}
		}

		if (
			null !== $enrollment
			&& ( EnrollmentStatus::Active === $enrollment->status || EnrollmentStatus::Completed === $enrollment->status )
			&& CourseAccessRules::isEnrollmentExpired( $enrollment->expiresAt, $now )
		) {
			$exception = new ForbiddenException( 'Your access to this course has expired.' );
			$exception->forReason( ForbiddenException::REASON_EXPIRED );
			throw $exception;
		}

		if ( $this->isDripLocked( $userId, $lessonId ) ) {
			$exception = new ForbiddenException( 'This lesson is not available yet.' );
			$exception->forReason( ForbiddenException::REASON_DRIP );
			throw $exception;
		}

		if ( null !== $course && $this->isProgressionLocked( $userId, $lesson->courseId, $lessonId, $course->settings ) ) {
			$exception = new ForbiddenException( 'Finish the previous lesson first.' );
			$exception->forReason( ForbiddenException::REASON_PROGRESSION );
			throw $exception;
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

		return CourseLessonSequence::orderedLessonIds(
			$this->sectionRepository->loadStructureRows( $courseId, true )
		);
	}
}
