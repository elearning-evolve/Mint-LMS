<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\EventDispatcherInterface;
use MintLMS\Application\Enrollment\EnrollmentService;
use MintLMS\Application\Event\DomainEventPublisher;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Enrollment\Enrollment;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Domain\Event\EnrollmentCancelled;
use MintLMS\Domain\Event\EnrollmentCreated;
use MintLMS\Domain\Shared\Clock;
use PHPUnit\Framework\TestCase;

final class EnrollmentServiceTest extends TestCase
{
    private EnrollmentRepositoryInterface $enrollmentRepository;

    private CourseRepositoryInterface $courseRepository;

    private AuthorizationInterface $authorization;

    private Clock $clock;

    private EventDispatcherInterface $dispatcher;

    private DomainEventPublisher $events;

    private EnrollmentService $service;

    protected function setUp(): void
    {
        $this->enrollmentRepository = $this->createMock(EnrollmentRepositoryInterface::class);
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->events = new DomainEventPublisher($this->dispatcher);
        $this->service = new EnrollmentService(
            $this->enrollmentRepository,
            $this->courseRepository,
            $this->authorization,
            $this->clock,
            $this->events,
        );

        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-15 10:00:00'));
    }

    public function test_self_enroll_creates_active_enrollment_for_open_published_course(): void
    {
        $course = $this->sampleCourse(EnrollmentType::Open, CourseStatus::Published);

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->enrollmentRepository->method('findByUserAndCourse')->with(5, 1)->willReturn(null);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EnrollmentCreated::class));

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Enrollment $enrollment): bool {
                return 0 === $enrollment->id
                    && 5 === $enrollment->userId
                    && 1 === $enrollment->courseId
                    && EnrollmentStatus::Active === $enrollment->status;
            }))
            ->willReturnCallback(static function (Enrollment $enrollment): Enrollment {
                return new Enrollment(
                    10,
                    $enrollment->userId,
                    $enrollment->courseId,
                    $enrollment->status,
                    $enrollment->enrolledAt,
                    $enrollment->expiresAt,
                    $enrollment->completedAt,
                );
            });

        $result = $this->service->enroll(5, 1, 5);

        $this->assertSame(10, $result->id);
        $this->assertSame('active', $result->status);
    }

    public function test_self_enroll_throws_for_manual_course(): void
    {
        $course = $this->sampleCourse(EnrollmentType::Manual, CourseStatus::Published);

        $this->courseRepository->method('findById')->with(1)->willReturn($course);

        $this->expectException(ForbiddenException::class);

        $this->service->enroll(5, 1, 5);
    }

    public function test_self_enroll_throws_for_paid_course(): void
    {
        $course = $this->sampleCourse(EnrollmentType::Paid, CourseStatus::Published);

        $this->courseRepository->method('findById')->with(1)->willReturn($course);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('This course requires purchase.');

        $this->service->enroll(5, 1, 5);
    }

    public function test_manual_enroll_requires_enroll_students_capability(): void
    {
        $course = $this->sampleCourse(EnrollmentType::Manual, CourseStatus::Published);

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEnrollStudents')->with(2)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->enroll(5, 1, 2);
    }

    public function test_manual_enroll_succeeds_for_teacher_with_capability(): void
    {
        $course = $this->sampleCourse(EnrollmentType::Manual, CourseStatus::Published);

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEnrollStudents')->with(2)->willReturn(true);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);
        $this->enrollmentRepository->method('findByUserAndCourse')->with(5, 1)->willReturn(null);

        $this->dispatcher->expects($this->once())->method('dispatch');

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (Enrollment $enrollment): Enrollment {
                return new Enrollment(
                    11,
                    $enrollment->userId,
                    $enrollment->courseId,
                    $enrollment->status,
                    $enrollment->enrolledAt,
                    null,
                    null,
                );
            });

        $result = $this->service->manualEnroll(5, 1, 2);

        $this->assertSame(11, $result->id);
        $this->assertSame(5, $result->userId);
    }

    public function test_cancel_sets_enrollment_to_cancelled(): void
    {
        $course = $this->sampleCourse();
        $enrollment = $this->sampleEnrollment();

        $this->enrollmentRepository->method('findById')->with(10)->willReturn($enrollment);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EnrollmentCancelled::class));

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Enrollment $saved): bool {
                return EnrollmentStatus::Cancelled === $saved->status;
            }))
            ->willReturnArgument(0);

        $this->service->cancel(10, 5);
    }

    public function test_re_enroll_reactivates_cancelled_enrollment(): void
    {
        $course = $this->sampleCourse(EnrollmentType::Open, CourseStatus::Published);
        $cancelled = new Enrollment(
            10,
            5,
            1,
            EnrollmentStatus::Cancelled,
            new \DateTimeImmutable('2026-01-01 10:00:00'),
            null,
            null,
        );

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->enrollmentRepository->method('findByUserAndCourse')->with(5, 1)->willReturn($cancelled);

        $this->events->expects($this->once())->method('publish');

        $this->enrollmentRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Enrollment $saved): bool {
                return 10 === $saved->id && EnrollmentStatus::Active === $saved->status;
            }))
            ->willReturnArgument(0);

        $result = $this->service->enroll(5, 1, 5);

        $this->assertSame(10, $result->id);
        $this->assertSame('active', $result->status);
    }

    public function test_active_enrollment_returns_existing_without_saving(): void
    {
        $course = $this->sampleCourse(EnrollmentType::Open, CourseStatus::Published);
        $active = $this->sampleEnrollment();

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->enrollmentRepository->method('findByUserAndCourse')->with(5, 1)->willReturn($active);

        $this->enrollmentRepository->expects($this->never())->method('save');

        $result = $this->service->enroll(5, 1, 5);

        $this->assertSame(10, $result->id);
    }

    public function test_is_enrolled_returns_true_only_for_active_status(): void
    {
        $active = $this->sampleEnrollment();

        $this->enrollmentRepository->method('findByUserAndCourse')->with(5, 1)->willReturn($active);

        $this->assertTrue($this->service->isEnrolled(5, 1));
    }

    public function test_is_enrolled_returns_false_for_cancelled(): void
    {
        $cancelled = new Enrollment(
            10,
            5,
            1,
            EnrollmentStatus::Cancelled,
            new \DateTimeImmutable('2026-01-01 10:00:00'),
            null,
            null,
        );

        $this->enrollmentRepository->method('findByUserAndCourse')->with(5, 1)->willReturn($cancelled);

        $this->assertFalse($this->service->isEnrolled(5, 1));
    }

    public function test_enroll_throws_not_found_for_missing_course(): void
    {
        $this->courseRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->enroll(5, 99, 5);
    }

    public function test_completed_enrollment_cannot_re_enroll(): void
    {
        $course = $this->sampleCourse(EnrollmentType::Open, CourseStatus::Published);
        $completed = new Enrollment(
            10,
            5,
            1,
            EnrollmentStatus::Completed,
            new \DateTimeImmutable('2026-01-01 10:00:00'),
            null,
            new \DateTimeImmutable('2026-02-01 10:00:00'),
        );

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->enrollmentRepository->method('findByUserAndCourse')->with(5, 1)->willReturn($completed);

        $this->expectException(ValidationException::class);

        $this->service->enroll(5, 1, 5);
    }

    private function sampleCourse(
        EnrollmentType $enrollmentType = EnrollmentType::Open,
        CourseStatus $status = CourseStatus::Draft,
    ): Course {
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');

        return new Course(
            1,
            'Sample Course',
            'sample-course',
            'Description',
            null,
            $status,
            $enrollmentType,
            2,
            $now,
            $now,
        );
    }

    private function sampleEnrollment(): Enrollment
    {
        return new Enrollment(
            10,
            5,
            1,
            EnrollmentStatus::Active,
            new \DateTimeImmutable('2026-01-01 10:00:00'),
            null,
            null,
        );
    }
}
