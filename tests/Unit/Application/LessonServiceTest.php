<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Lesson\Dto\CreateLessonDto;
use MintLMS\Application\Lesson\Dto\ReorderLessonsDto;
use MintLMS\Application\Lesson\Dto\UpdateLessonDto;
use MintLMS\Application\Lesson\LessonService;
use MintLMS\Application\Contract\ProgressLifecycleInterface;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Section\Section;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Domain\Shared\Clock;
use PHPUnit\Framework\TestCase;

final class LessonServiceTest extends TestCase
{
    private LessonRepositoryInterface $lessonRepository;

    private SectionRepositoryInterface $sectionRepository;

    private CourseRepositoryInterface $courseRepository;

    private AuthorizationInterface $authorization;

    private Clock $clock;

    private ProgressLifecycleInterface $progressLifecycle;

    private LessonService $service;

    protected function setUp(): void
    {
        $this->lessonRepository = $this->createMock(LessonRepositoryInterface::class);
        $this->sectionRepository = $this->createMock(SectionRepositoryInterface::class);
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $this->progressLifecycle = $this->createMock(ProgressLifecycleInterface::class);
        $this->service = new LessonService(
            $this->lessonRepository,
            $this->sectionRepository,
            $this->courseRepository,
            $this->authorization,
            $this->clock,
            $this->progressLifecycle,
        );

        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-15 10:00:00'));
    }

    public function test_create_persists_lesson_for_authorized_user(): void
    {
        $section = $this->sampleSection();
        $course = $this->sampleCourse();

        $this->sectionRepository->method('findById')->with(10)->willReturn($section);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);
        $this->lessonRepository->method('findBySlugAndCourseId')->willReturn(null);
        $this->lessonRepository->method('nextSortOrder')->with(10, 1)->willReturn(0);

        $this->lessonRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Lesson $lesson): bool {
                return 0 === $lesson->id
                    && 10 === $lesson->sectionId
                    && 1 === $lesson->courseId
                    && 'Welcome' === $lesson->title
                    && 'welcome' === $lesson->slug;
            }))
            ->willReturnCallback(static function (Lesson $lesson): Lesson {
                return new Lesson(
                    20,
                    $lesson->sectionId,
                    $lesson->courseId,
                    $lesson->title,
                    $lesson->slug,
                    $lesson->content,
                    $lesson->videoUrl,
                    $lesson->attachmentId,
                    $lesson->isPreview,
                    $lesson->availableAfterDays,
                    $lesson->sortOrder,
                    $lesson->createdAt,
                    $lesson->updatedAt,
                );
            });

        $dto = new CreateLessonDto('Welcome', null, 'Hello world', null, null, false);
        $result = $this->service->create(10, $dto, 2);

        $this->assertSame(20, $result->id);
        $this->assertSame('welcome', $result->slug);
    }

    public function test_create_for_course_allows_ungrouped_lesson(): void
    {
        $course = $this->sampleCourse();

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);
        $this->lessonRepository->method('findBySlugAndCourseId')->willReturn(null);
        $this->lessonRepository->method('nextSortOrder')->with(0, 1)->willReturn(0);

        $this->lessonRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Lesson $lesson): bool {
                return 0 === $lesson->sectionId
                    && 1 === $lesson->courseId
                    && 'Solo Lesson' === $lesson->title;
            }))
            ->willReturnCallback(static function (Lesson $lesson): Lesson {
                return new Lesson(
                    55,
                    $lesson->sectionId,
                    $lesson->courseId,
                    $lesson->title,
                    $lesson->slug,
                    $lesson->content,
                    $lesson->videoUrl,
                    $lesson->attachmentId,
                    $lesson->isPreview,
                    $lesson->availableAfterDays,
                    $lesson->sortOrder,
                    $lesson->createdAt,
                    $lesson->updatedAt,
                );
            });

        $dto = new CreateLessonDto('Solo Lesson', null, '', null, null, false);
        $result = $this->service->createForCourse(1, $dto, 2, null);

        $this->assertSame(55, $result->id);
        $this->assertSame(0, $result->sectionId);
        $this->assertSame(1, $result->courseId);
    }

    public function test_attach_to_course_without_section_keeps_ungrouped(): void
    {
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');
        $lesson = new Lesson(20, 0, 0, 'Library', 'library', '', null, null, false, null, 0, $now, $now);
        $course = $this->sampleCourse();

        $this->lessonRepository->method('findById')->with(20)->willReturn($lesson);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->willReturn(true);
        $this->lessonRepository->method('findBySlugAndCourseId')->willReturn(null);
        $this->lessonRepository->method('nextSortOrder')->with(0, 1)->willReturn(0);

        $this->sectionRepository->expects($this->never())->method('save');

        $this->lessonRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Lesson $saved): bool {
                return 0 === $saved->sectionId && 1 === $saved->courseId;
            }))
            ->willReturnArgument(0);

        $result = $this->service->attachToCourse(20, 1, null, 2);

        $this->assertSame(0, $result->sectionId);
        $this->assertSame(1, $result->courseId);
    }

    public function test_create_throws_forbidden_when_unauthorized(): void
    {
        $section = $this->sampleSection();
        $course = $this->sampleCourse();

        $this->sectionRepository->method('findById')->with(10)->willReturn($section);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(3, 2)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $dto = new CreateLessonDto('Lesson', null, '', null, null, false);
        $this->service->create(10, $dto, 3);
    }

    public function test_get_throws_forbidden_for_other_users_course(): void
    {
        $lesson = $this->sampleLesson(authorCourseId: 1);
        $course = $this->sampleCourse(authorId: 8);

        $this->lessonRepository->method('findById')->with(20)->willReturn($lesson);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canViewCourse')->with(3, 8)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->get(20, 3);
    }

    public function test_update_changes_fields_for_course_owner(): void
    {
        $lesson = $this->sampleLesson();
        $course = $this->sampleCourse();

        $this->lessonRepository->method('findById')->with(20)->willReturn($lesson);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);
        $this->lessonRepository->method('findBySlugAndCourseId')->willReturn(null);

        $this->lessonRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Lesson $saved): bool {
                return 'Updated Lesson' === $saved->title && 'updated-lesson' === $saved->slug;
            }))
            ->willReturnArgument(0);

        $dto = new UpdateLessonDto('Updated Lesson', 'updated-lesson');
        $result = $this->service->update(20, $dto, 2);

        $this->assertSame('Updated Lesson', $result->title);
    }

    public function test_delete_removes_lesson_for_authorized_user(): void
    {
        $lesson = $this->sampleLesson();
        $course = $this->sampleCourse();

        $this->lessonRepository->method('findById')->with(20)->willReturn($lesson);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);

        $this->progressLifecycle->expects($this->once())->method('onLessonDeleted')->with(20);
        $this->lessonRepository->expects($this->once())->method('delete')->with(20);

        $this->service->delete(20, 2);
    }

    public function test_reorder_updates_lesson_order(): void
    {
        $section = $this->sampleSection();
        $course = $this->sampleCourse();
        $lessons = [
            $this->sampleLesson(20),
            $this->sampleLesson(21, sortOrder: 1),
        ];

        $this->sectionRepository->method('findById')->with(10)->willReturn($section);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);
        $this->lessonRepository->method('findByCourseIdAndSectionId')->with(1, 10)->willReturn($lessons);

        $this->lessonRepository
            ->expects($this->once())
            ->method('reorder')
            ->with(10, [21, 20]);

        $this->service->reorder(10, new ReorderLessonsDto([21, 20]), 2);
    }

    public function test_create_rejects_empty_title(): void
    {
        $section = $this->sampleSection();
        $course = $this->sampleCourse();

        $this->sectionRepository->method('findById')->with(10)->willReturn($section);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->willReturn(true);

        $this->expectException(ValidationException::class);

        $dto = new CreateLessonDto('   ', null, '', null, null, false);
        $this->service->create(10, $dto, 2);
    }

    public function test_get_throws_not_found_when_missing(): void
    {
        $this->lessonRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->get(99, 2);
    }

    private function sampleCourse(int $authorId = 2): Course
    {
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');

        return new Course(
            1,
            'Sample Course',
            'sample-course',
            'Description',
            null,
            CourseStatus::Draft,
            EnrollmentType::Open,
            $authorId,
            $now,
            $now,
        );
    }

    private function sampleSection(): Section
    {
        return new Section(
            10,
            1,
            'Section One',
            0,
            new \DateTimeImmutable('2026-01-15 10:00:00'),
        );
    }

    private function sampleLesson(int $id = 20, int $sortOrder = 0, int $authorCourseId = 1): Lesson
    {
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');

        return new Lesson(
            $id,
            10,
            $authorCourseId,
            'Lesson One',
            'lesson-one',
            'Content',
            null,
            null,
            false,
            null,
            $sortOrder,
            $now,
            $now,
        );
    }
}
