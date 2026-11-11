<?php
declare(strict_types=1);

namespace MintLMS\Application\Progress;

use MintLMS\Application\Contract\ProgressLifecycleInterface;
use MintLMS\Application\Event\DomainEventPublisher;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Progress\Dto\ProgressDto;
use MintLMS\Application\Lesson\LessonAccessService;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Event\CourseCompleted;
use MintLMS\Domain\Event\LessonCompleted;
use MintLMS\Domain\Progress\CompleteLessonResult;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Shared\Clock;
use MintLMS\Domain\Shared\UserId;

final class ProgressService implements ProgressLifecycleInterface {

	public function __construct(
		private ProgressRepositoryInterface $repository,
		private DomainEventPublisher $events,
		private Clock $clock,
		private ?QuizService $quizService = null,
		private ?LessonAccessService $lessonAccessService = null,
		private ?CourseRepositoryInterface $courseRepository = null,
	) {
	}

	public function completeLesson( int $userId, int $lessonId, ?int $expectedCourseId = null ): CompleteLessonResult {
		$courseId = $this->resolveLessonCourse( $lessonId, $expectedCourseId );
		$this->assertEnrolled( $userId, $courseId );

		if ( null !== $this->courseRepository ) {
			$course = $this->courseRepository->findById( $courseId );

			if ( null !== $course && ! $course->settings->studentComplete ) {
				throw new ForbiddenException( 'Students cannot mark lessons complete in this course.' );
			}
		}

		if ( null !== $this->lessonAccessService ) {
			$this->lessonAccessService->assertCanAccessLesson( $userId, $lessonId );
		}

		if ( null !== $this->quizService && ! $this->quizService->hasPassedLessonQuiz( $userId, $lessonId ) ) {
			throw new ForbiddenException( 'You must pass the lesson quiz before marking it complete.' );
		}

		$now    = $this->clock->now();
		$result = $this->repository->completeLesson( $userId, $lessonId, $courseId, $now );

		if ( $result->wasNewlyCompleted ) {
			$this->events->publish(
				new LessonCompleted(
					UserId::fromInt( $userId ),
					$lessonId,
					$courseId,
					$now,
				)
			);
		}

		if ( $result->courseJustCompleted ) {
			$this->events->publish(
				new CourseCompleted(
					UserId::fromInt( $userId ),
					$courseId,
					$now,
				)
			);
		}

		return $result;
	}

	public function uncompleteLesson( int $userId, int $lessonId, ?int $expectedCourseId = null ): void {
		$courseId = $this->resolveLessonCourse( $lessonId, $expectedCourseId );
		$this->assertEnrolled( $userId, $courseId );

		if ( ! $this->repository->isLessonComplete( $userId, $lessonId ) ) {
			return;
		}

		$this->repository->uncompleteLesson( $userId, $lessonId, $this->clock->now() );
	}

	public function getProgressForUser( int $userId, int $courseId ): ProgressDto {
		$this->assertEnrolled( $userId, $courseId );

		$summary = $this->repository->getSummary( $userId, $courseId );
		$total   = $this->repository->countLessonsInCourse( $courseId );

		if ( null === $summary || $summary->lessonsTotal !== $total ) {
			$summary = $this->repository->recalculateSummary(
				$userId,
				$courseId,
				$this->clock->now(),
			);
		}

		return ProgressDto::fromSummary( $summary );
	}

	public function isLessonComplete( int $userId, int $lessonId ): bool {
		$courseId = $this->resolveLessonCourse( $lessonId, null );

		if ( ! $this->repository->isUserEnrolled( $userId, $courseId ) ) {
			return false;
		}

		return $this->repository->isLessonComplete( $userId, $lessonId );
	}

	public function onLessonDeleted( int $lessonId ): void {
		$this->repository->onLessonDeleted( $lessonId, $this->clock->now() );
	}

	private function resolveLessonCourse( int $lessonId, ?int $expectedCourseId ): int {
		$courseId = $this->repository->findLessonCourseId( $lessonId );

		if ( null === $courseId ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		if ( null !== $expectedCourseId && $expectedCourseId !== $courseId ) {
			throw new ForbiddenException( 'Lesson does not belong to this course.' );
		}

		return $courseId;
	}

	private function assertEnrolled( int $userId, int $courseId ): void {
		if ( ! $this->repository->isUserEnrolled( $userId, $courseId ) ) {
			throw new ForbiddenException( 'You must be enrolled in this course to track progress.' );
		}
	}
}
