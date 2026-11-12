<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Event\DomainEventPublisher;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Progress\ProgressService;
use MintLMS\Domain\Event\CourseCompleted;
use MintLMS\Domain\Event\DomainEvent;
use MintLMS\Domain\Event\LessonCompleted;
use MintLMS\Domain\Progress\CompleteLessonResult;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Progress\ProgressSummary;
use MintLMS\Domain\Shared\Clock;
use MintLMS\Domain\Shared\UserId;
use PHPUnit\Framework\TestCase;

final class ProgressServiceTest extends TestCase
{
    private ProgressRepositoryInterface $repository;

    private DomainEventPublisher $events;

    private Clock $clock;

    private ProgressService $service;

    /** @var list<DomainEvent> */
    private array $publishedEvents = [];

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProgressRepositoryInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $dispatcher = $this->createMock(\MintLMS\Application\Contract\EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (DomainEvent $event): void {
            $this->publishedEvents[] = $event;
        });
        $this->events = new DomainEventPublisher($dispatcher);
        $this->service = new ProgressService($this->repository, $this->events, $this->clock);

        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-15 10:00:00'));
        $this->publishedEvents = [];
    }

    public function test_completing_lesson_twice_does_not_double_count(): void
    {
        $userId = 5;
        $lessonId = 10;
        $courseId = 1;
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');
        $summary = $this->sampleSummary($userId, $courseId, 1, 2, 50.0);

        $this->repository->method('findLessonCourseId')->with($lessonId)->willReturn($courseId);
        $this->repository->method('isUserEnrolled')->with($userId, $courseId)->willReturn(true);

        $this->repository
            ->expects($this->exactly(2))
            ->method('completeLesson')
            ->with($userId, $lessonId, $courseId, $now)
            ->willReturnOnConsecutiveCalls(
                new CompleteLessonResult(true, $summary, false),
                new CompleteLessonResult(false, $summary, false),
            );

        $first = $this->service->completeLesson($userId, $lessonId);
        $second = $this->service->completeLesson($userId, $lessonId);

        $this->assertTrue($first->wasNewlyCompleted);
        $this->assertFalse($second->wasNewlyCompleted);
        $this->assertSame(1, $summary->lessonsDone);
        $this->assertCount(1, $this->publishedEvents);
        $this->assertInstanceOf(LessonCompleted::class, $this->publishedEvents[0]);
    }

    public function test_completing_last_lesson_marks_course_complete(): void
    {
        $userId = 3;
        $lessonId = 20;
        $courseId = 2;
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');
        $summary = $this->sampleSummary($userId, $courseId, 2, 2, 100.0);

        $this->repository->method('findLessonCourseId')->with($lessonId)->willReturn($courseId);
        $this->repository->method('isUserEnrolled')->with($userId, $courseId)->willReturn(true);
        $this->repository
            ->method('completeLesson')
            ->willReturn(new CompleteLessonResult(true, $summary, true));

        $result = $this->service->completeLesson($userId, $lessonId);

        $this->assertTrue($result->courseJustCompleted);
        $this->assertTrue($result->summary->isCourseComplete());
        $this->assertCount(2, $this->publishedEvents);
        $this->assertInstanceOf(LessonCompleted::class, $this->publishedEvents[0]);
        $this->assertInstanceOf(CourseCompleted::class, $this->publishedEvents[1]);
    }

    public function test_progress_percent_correct_when_lessons_added_mid_course(): void
    {
        $userId = 7;
        $courseId = 4;
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');
        $staleSummary = $this->sampleSummary($userId, $courseId, 2, 2, 100.0);
        $freshSummary = $this->sampleSummary($userId, $courseId, 2, 3, 66.67);

        $this->repository->method('isUserEnrolled')->with($userId, $courseId)->willReturn(true);
        $this->repository->method('getSummary')->with($userId, $courseId)->willReturn($staleSummary);
        $this->repository->method('countLessonsInCourse')->with($courseId)->willReturn(3);
        $this->repository
            ->expects($this->once())
            ->method('recalculateSummary')
            ->with($userId, $courseId, $now)
            ->willReturn($freshSummary);

        $progress = $this->service->getProgressForUser($userId, $courseId);

        $this->assertSame(2, $progress->lessonsDone);
        $this->assertSame(3, $progress->lessonsTotal);
        $this->assertSame(66.67, $progress->pctComplete);
        $this->assertFalse($progress->isCourseComplete);
    }

    public function test_deleting_lesson_recalculates_progress(): void
    {
        $lessonId = 15;
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');

        $this->repository
            ->expects($this->once())
            ->method('onLessonDeleted')
            ->with($lessonId, $now);

        $this->service->onLessonDeleted($lessonId);
    }

    public function test_non_enrolled_user_cannot_complete(): void
    {
        $userId = 9;
        $lessonId = 11;
        $courseId = 5;

        $this->repository->method('findLessonCourseId')->with($lessonId)->willReturn($courseId);
        $this->repository->method('isUserEnrolled')->with($userId, $courseId)->willReturn(false);
        $this->repository->expects($this->never())->method('completeLesson');

        $this->expectException(ForbiddenException::class);

        $this->service->completeLesson($userId, $lessonId);
    }

    public function test_lesson_id_from_another_course_rejected(): void
    {
        $userId = 4;
        $lessonId = 8;
        $actualCourseId = 10;
        $claimedCourseId = 99;

        $this->repository->method('findLessonCourseId')->with($lessonId)->willReturn($actualCourseId);
        $this->repository->expects($this->never())->method('isUserEnrolled');
        $this->repository->expects($this->never())->method('completeLesson');

        $this->expectException(ForbiddenException::class);

        $this->service->completeLesson($userId, $lessonId, $claimedCourseId);
    }

    public function test_complete_throws_not_found_for_missing_lesson(): void
    {
        $this->repository->method('findLessonCourseId')->with(404)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->completeLesson(1, 404);
    }

    public function test_get_progress_requires_enrollment(): void
    {
        $this->repository->method('isUserEnrolled')->with(2, 3)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->getProgressForUser(2, 3);
    }

    private function sampleSummary(
        int $userId,
        int $courseId,
        int $lessonsDone,
        int $lessonsTotal,
        float $pctComplete,
    ): ProgressSummary {
        return new ProgressSummary(
            UserId::fromInt($userId),
            $courseId,
            $lessonsDone,
            $lessonsTotal,
            $pctComplete,
            null,
            new \DateTimeImmutable('2026-01-15 10:00:00'),
        );
    }
}
