<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Section\Dto\CreateSectionDto;
use MintLMS\Application\Section\Dto\ReorderSectionsDto;
use MintLMS\Application\Section\Dto\UpdateSectionDto;
use MintLMS\Application\Section\SectionService;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Section\Section;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Domain\Shared\Clock;
use PHPUnit\Framework\TestCase;

final class SectionServiceTest extends TestCase
{
    private SectionRepositoryInterface $sectionRepository;

    private LessonRepositoryInterface $lessonRepository;

    private CourseRepositoryInterface $courseRepository;

    private AuthorizationInterface $authorization;

    private Clock $clock;

    private SectionService $service;

    protected function setUp(): void
    {
        $this->sectionRepository = $this->createMock(SectionRepositoryInterface::class);
        $this->lessonRepository = $this->createMock(LessonRepositoryInterface::class);
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $this->service = new SectionService(
            $this->sectionRepository,
            $this->lessonRepository,
            $this->courseRepository,
            $this->authorization,
            $this->clock,
        );

        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-15 10:00:00'));
    }

    public function test_create_persists_section_for_authorized_user(): void
    {
        $course = $this->sampleCourse();

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(5, 2)->willReturn(true);
        $this->sectionRepository->method('nextSortOrder')->with(1)->willReturn(0);

        $this->sectionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Section $section): bool {
                return 0 === $section->id
                    && 1 === $section->courseId
                    && 'Getting Started' === $section->title
                    && 0 === $section->sortOrder;
            }))
            ->willReturnCallback(static function (Section $section): Section {
                return new Section(
                    10,
                    $section->courseId,
                    $section->title,
                    $section->sortOrder,
                    $section->createdAt,
                );
            });

        $result = $this->service->create(1, new CreateSectionDto('Getting Started'), 5);

        $this->assertSame(10, $result->id);
        $this->assertSame('Getting Started', $result->title);
    }

    public function test_create_throws_forbidden_when_unauthorized(): void
    {
        $course = $this->sampleCourse();

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(3, 2)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->create(1, new CreateSectionDto('Section'), 3);
    }

    public function test_update_changes_title_for_course_owner(): void
    {
        $section = $this->sampleSection();
        $course = $this->sampleCourse();

        $this->sectionRepository->method('findById')->with(10)->willReturn($section);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);

        $this->sectionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Section $saved): bool {
                return 'Updated Section' === $saved->title;
            }))
            ->willReturnArgument(0);

        $result = $this->service->update(10, new UpdateSectionDto('Updated Section'), 2);

        $this->assertSame('Updated Section', $result->title);
    }

    public function test_delete_removes_section_and_lessons(): void
    {
        $section = $this->sampleSection();
        $course = $this->sampleCourse();

        $this->sectionRepository->method('findById')->with(10)->willReturn($section);
        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);

        $this->lessonRepository->expects($this->once())->method('deleteBySectionId')->with(10);
        $this->sectionRepository->expects($this->once())->method('delete')->with(10);

        $this->service->delete(10, 2);
    }

    public function test_reorder_updates_section_order(): void
    {
        $course = $this->sampleCourse();
        $sections = [
            $this->sampleSection(10),
            $this->sampleSection(11, sortOrder: 1),
        ];

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);
        $this->sectionRepository->method('findByCourseId')->with(1)->willReturn($sections);

        $this->sectionRepository
            ->expects($this->once())
            ->method('reorder')
            ->with(1, [11, 10]);

        $this->service->reorder(1, new ReorderSectionsDto([11, 10]), 2);
    }

    public function test_reorder_rejects_unknown_section_ids(): void
    {
        $course = $this->sampleCourse();

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canEditCourse')->with(2, 2)->willReturn(true);
        $this->sectionRepository->method('findByCourseId')->with(1)->willReturn([$this->sampleSection()]);

        $this->expectException(ValidationException::class);

        $this->service->reorder(1, new ReorderSectionsDto([10, 99]), 2);
    }

    public function test_create_throws_not_found_for_missing_course(): void
    {
        $this->courseRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->create(99, new CreateSectionDto('Section'), 2);
    }

    private function sampleCourse(): Course
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
            2,
            $now,
            $now,
        );
    }

    private function sampleSection(int $id = 10, int $sortOrder = 0): Section
    {
        return new Section(
            $id,
            1,
            'Section One',
            $sortOrder,
            new \DateTimeImmutable('2026-01-15 10:00:00'),
        );
    }
}
