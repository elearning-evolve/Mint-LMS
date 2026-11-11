<?php
declare(strict_types=1);

namespace MintLMS\Application\Enrollment;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Enrollment\Dto\EnrolledCourseDto;
use MintLMS\Application\Enrollment\Dto\EnrolledCourseListDto;
use MintLMS\Application\Enrollment\Dto\EnrollmentDto;
use MintLMS\Application\Enrollment\Dto\StudentDto;
use MintLMS\Application\Enrollment\Dto\StudentListDto;
use MintLMS\Application\Event\DomainEventPublisher;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Domain\Course\CourseAccessRules;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Enrollment\Enrollment;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Domain\Event\EnrollmentCancelled;
use MintLMS\Domain\Event\EnrollmentCreated;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Shared\Clock;
use MintLMS\Domain\Shared\UserId;

final class EnrollmentService {

	private const MAX_PER_PAGE = 100;

	private const DEFAULT_PER_PAGE = 20;

	public function __construct(
		private EnrollmentRepositoryInterface $enrollmentRepository,
		private CourseRepositoryInterface $courseRepository,
		private AuthorizationInterface $authorization,
		private Clock $clock,
		private DomainEventPublisher $events,
		private ?ProgressRepositoryInterface $progressRepository = null,
	) {
	}

	public function enroll( int $userId, int $courseId, int $actorUserId ): EnrollmentDto {
		$course = $this->findCourseOrFail( $courseId );
		$isSelf = $userId === $actorUserId;
		$this->assertCanEnroll( $userId, $courseId, $actorUserId, $course->enrollmentType, $course->status, $course->authorId );

		if ( $userId <= 0 ) {
			throw new ValidationException( 'Validation failed.', array( 'userId' => 'User ID is required.' ) );
		}

		$now = $this->clock->now();

		if ( $isSelf ) {
			$this->assertSelfEnrollmentGates( $course, $userId, $now );
		}

		$existing = $this->enrollmentRepository->findByUserAndCourse( $userId, $courseId );

		if ( null !== $existing && EnrollmentStatus::Active === $existing->status ) {
			return EnrollmentDto::fromEnrollment( $existing );
		}

		$expiresAt = CourseAccessRules::resolveExpiresAt( $course->settings, $now );

		if ( null !== $existing && EnrollmentStatus::Cancelled === $existing->status ) {
			$reactivated = new Enrollment(
				$existing->id,
				$existing->userId,
				$existing->courseId,
				EnrollmentStatus::Active,
				$now,
				$expiresAt,
				null,
			);

			$saved = $this->enrollmentRepository->save( $reactivated );
			$this->events->publish(
				new EnrollmentCreated( UserId::fromInt( $userId ), $courseId, $saved->id, $now )
			);

			return EnrollmentDto::fromEnrollment( $saved );
		}

		if ( null !== $existing && EnrollmentStatus::Completed === $existing->status ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'enrollment' => 'User has already completed this course.' )
			);
		}

		$enrollment = new Enrollment(
			0,
			$userId,
			$courseId,
			EnrollmentStatus::Active,
			$now,
			$expiresAt,
			null,
		);

		$saved = $this->enrollmentRepository->save( $enrollment );
		$this->events->publish(
			new EnrollmentCreated( UserId::fromInt( $userId ), $courseId, $saved->id, $now )
		);

		return EnrollmentDto::fromEnrollment( $saved );
	}

	public function manualEnroll( int $userId, int $courseId, int $actorUserId ): EnrollmentDto {
		return $this->enroll( $userId, $courseId, $actorUserId );
	}

	public function cancel( int $enrollmentId, int $actorUserId ): void {
		$enrollment = $this->findEnrollmentOrFail( $enrollmentId );
		$course     = $this->findCourseOrFail( $enrollment->courseId );

		if ( ! $this->canCancelEnrollment( $actorUserId, $enrollment->userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		if ( EnrollmentStatus::Cancelled === $enrollment->status ) {
			return;
		}

		$now       = $this->clock->now();
		$cancelled = new Enrollment(
			$enrollment->id,
			$enrollment->userId,
			$enrollment->courseId,
			EnrollmentStatus::Cancelled,
			$enrollment->enrolledAt,
			$enrollment->expiresAt,
			$enrollment->completedAt,
		);

		$this->enrollmentRepository->save( $cancelled );
		$this->events->publish(
			new EnrollmentCancelled(
				UserId::fromInt( $enrollment->userId ),
				$enrollment->courseId,
				$enrollment->id,
				$now
			)
		);
	}

	public function cancelByUserAndCourse( int $userId, int $courseId, int $actorUserId ): bool {
		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $courseId );

		if ( null === $enrollment || EnrollmentStatus::Active !== $enrollment->status ) {
			return false;
		}

		$this->cancel( $enrollment->id, $actorUserId );

		return true;
	}

	public function listStudentsForCourse( int $courseId, int $page, int $perPage, int $actorUserId ): StudentListDto {
		$course = $this->findCourseOrFail( $courseId );

		if ( ! $this->authorization->canEnrollStudents( $actorUserId )
			&& ! $this->authorization->canEditCourse( $actorUserId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$page    = max( 1, $page );
		$perPage = max( 1, min( self::MAX_PER_PAGE, $perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE ) );

		$result = $this->enrollmentRepository->listByCourse( $courseId, $page, $perPage, EnrollmentStatus::Active );

		$userIds = array_map(
			static fn( Enrollment $enrollment ): int => $enrollment->userId,
			$result['enrollments']
		);

		$progressMap = $this->enrollmentRepository->getProgressPercentagesForCourse( $courseId, $userIds );

		$students = array_map(
			static function ( Enrollment $enrollment ) use ( $progressMap ): StudentDto {
				$user        = get_userdata( $enrollment->userId );
				$displayName = '';
				$email       = '';

				if ( $user instanceof \WP_User ) {
					$displayName = (string) $user->display_name;
					$email       = (string) $user->user_email;
				}

				if ( '' === $displayName ) {
					$displayName = sprintf(
						/* translators: %d: user ID */
						__( 'User #%d', 'mint-lms' ),
						$enrollment->userId
					);
				}

				return new StudentDto(
					$enrollment->id,
					$enrollment->userId,
					$enrollment->status->value,
					$enrollment->enrolledAt->format( 'c' ),
					$progressMap[ $enrollment->userId ] ?? 0.0,
					$displayName,
					$email,
				);
			},
			$result['enrollments']
		);

		return new StudentListDto( $students, $result['total'], $page, $perPage );
	}

	public function listCoursesForUser( int $userId, int $page, int $perPage ): EnrolledCourseListDto {
		if ( $userId <= 0 ) {
			throw new ForbiddenException();
		}

		$page    = max( 1, $page );
		$perPage = max( 1, min( self::MAX_PER_PAGE, $perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE ) );

		$result = $this->enrollmentRepository->listByUser( $userId, $page, $perPage, EnrollmentStatus::Active );

		$courses = array();

		foreach ( $result['enrollments'] as $enrollment ) {
			$course = $this->courseRepository->findById( $enrollment->courseId );

			if ( null === $course ) {
				continue;
			}

			$progressMap = $this->enrollmentRepository->getProgressPercentagesForCourse(
				$enrollment->courseId,
				array( $enrollment->userId )
			);

			$courses[] = new EnrolledCourseDto(
				$enrollment->id,
				$course->id,
				$course->title,
				$course->slug,
				$enrollment->status->value,
				$enrollment->enrolledAt->format( 'c' ),
				$progressMap[ $enrollment->userId ] ?? 0.0,
			);
		}

		return new EnrolledCourseListDto( $courses, $result['total'], $page, $perPage );
	}

	public function isEnrolled( int $userId, int $courseId ): bool {
		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $courseId );

		return null !== $enrollment && EnrollmentStatus::Active === $enrollment->status;
	}

	/**
	 * @param EnrollmentType   $enrollmentType
	 * @param CourseStatus     $courseStatus
	 */
	private function assertCanEnroll(
		int $userId,
		int $courseId,
		int $actorUserId,
		EnrollmentType $enrollmentType,
		CourseStatus $courseStatus,
		int $authorId,
	): void {
		if ( $userId === $actorUserId ) {
			if ( $actorUserId <= 0 ) {
				throw new ForbiddenException();
			}

			if ( ! $enrollmentType->allowsSelfEnroll() ) {
				throw new ForbiddenException(
					EnrollmentType::Paid === $enrollmentType
						? 'This course requires purchase.'
						: 'This course requires manual enrollment.'
				);
			}

			if ( CourseStatus::Published !== $courseStatus ) {
				throw new ForbiddenException( 'This course is not available for enrollment.' );
			}

			return;
		}

		if ( ! $this->authorization->canEnrollStudents( $actorUserId ) ) {
			throw new ForbiddenException();
		}

		if ( ! $this->authorization->canEditCourse( $actorUserId, $authorId ) ) {
			throw new ForbiddenException();
		}
	}

	private function assertSelfEnrollmentGates( \MintLMS\Domain\Course\Course $course, int $userId, \DateTimeImmutable $now ): void {
		if ( CourseAccessRules::isAfterAccessEnd( $course->settings, $now ) ) {
			throw new ForbiddenException( 'This course is no longer accepting students.' );
		}

		if ( CourseAccessRules::isSeatLimitReached( $course->settings, $this->enrollmentRepository->countActiveByCourse( $course->id ) ) ) {
			throw new ForbiddenException( 'This course is full.' );
		}

		if ( ! CourseAccessRules::prerequisitesMet(
			$course->settings,
			function ( int $prereqCourseId ) use ( $userId ): bool {
				if ( null === $this->progressRepository ) {
					return false;
				}

				$summary = $this->progressRepository->getSummary( $userId, $prereqCourseId );

				return null !== $summary && $summary->isCourseComplete();
			}
		) ) {
			throw new ForbiddenException( 'Finish the required courses first.' );
		}
	}

	private function canCancelEnrollment( int $actorUserId, int $enrolledUserId, int $authorId ): bool {
		if ( $actorUserId === $enrolledUserId ) {
			return $actorUserId > 0;
		}

		if ( ! $this->authorization->canEnrollStudents( $actorUserId ) ) {
			return false;
		}

		return $this->authorization->canEditCourse( $actorUserId, $authorId );
	}

	private function findCourseOrFail( int $courseId ): \MintLMS\Domain\Course\Course {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		return $course;
	}

	private function findEnrollmentOrFail( int $enrollmentId ): Enrollment {
		$enrollment = $this->enrollmentRepository->findById( $enrollmentId );

		if ( null === $enrollment ) {
			throw new NotFoundException( 'Enrollment not found.' );
		}

		return $enrollment;
	}
}
