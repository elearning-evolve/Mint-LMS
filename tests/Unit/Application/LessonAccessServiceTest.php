<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Lesson\LessonAccessService;
use MintLMS\Domain\Enrollment\Enrollment;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Shared\Clock;
use PHPUnit\Framework\TestCase;

final class LessonAccessServiceTest extends TestCase
{
    private LessonRepositoryInterface $lessonRepository;

    private EnrollmentRepositoryInterface $enrollmentRepository;

    private ProgressRepositoryInterface $progressRepository;

    private Clock $clock;

    private LessonAccessService $service;

    protected function setUp(): void
    {
        $this->lessonRepository = $this->createMock(LessonRepositoryInterface::class);
        $this->enrollmentRepository = $this->createMock(EnrollmentRepositoryInterface::class);
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-02 00:00:00'));

        $this->service = new LessonAccessService(
            $this->lessonRepository,
            $this->enrollmentRepository,
            $this->progressRepository,
            $this->clock,
        );
    }

    public function test_blocks_drip_locked_lesson(): void
    {
        $lesson = $this->sampleLesson(10, 5, 7);
        $this->lessonRepository->method('findById')->with(10)->willReturn($lesson);
        $this->progressRepository->method('isUserEnrolled')->with(3, 5)->willReturn(true);
        $this->enrollmentRepository
            ->method('findByUserAndCourse')
            ->with(3, 5)
            ->willReturn(new Enrollment(1, 3, 5, EnrollmentStatus::Active, new \DateTimeImmutable('2026-01-01 00:00:00'), null, null));

        try {
            $this->service->assertCanAccessLesson(3, 10);
            $this->fail('Expected ForbiddenException for drip lock.');
        } catch (ForbiddenException $exception) {
            $this->assertSame(ForbiddenException::REASON_DRIP, $exception->reason());
        }
    }

    public function test_blocks_unenrolled_access_with_enrollment_reason(): void
    {
        $lesson = $this->sampleLesson(10, 5, null);
        $this->lessonRepository->method('findById')->with(10)->willReturn($lesson);
        $this->progressRepository->method('isUserEnrolled')->with(3, 5)->willReturn(false);

        try {
            $this->service->assertCanAccessLesson(3, 10, false);
            $this->fail('Expected ForbiddenException for enrollment gate.');
        } catch (ForbiddenException $exception) {
            $this->assertSame(ForbiddenException::REASON_ENROLLMENT, $exception->reason());
        }
    }

    public function test_allows_preview_without_enrollment(): void
    {
        $lesson = $this->sampleLesson(10, 5, 7, true);
        $this->lessonRepository->method('findById')->with(10)->willReturn($lesson);

        $this->service->assertCanAccessLesson(3, 10);

        $this->assertTrue(true);
    }

    private function sampleLesson(int $id, int $courseId, ?int $dripDays = null, bool $isPreview = false): Lesson
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        return new Lesson(
            $id,
            1,
            $courseId,
            'Lesson',
            'lesson-' . $id,
            'Content',
            null,
            null,
            $isPreview,
            $dripDays,
            0,
            $now,
            $now,
        );
    }
}
