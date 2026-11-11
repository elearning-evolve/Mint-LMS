<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Course\CourseStructureService;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Section\Section;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CourseStructureServiceTest extends TestCase
{
    private CourseRepositoryInterface $courseRepository;

    private SectionRepositoryInterface $sectionRepository;

    private AuthorizationInterface $authorization;

    private CourseStructureService $service;

    protected function setUp(): void
    {
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->sectionRepository = $this->createMock(SectionRepositoryInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->service = new CourseStructureService(
            $this->courseRepository,
            $this->sectionRepository,
            $this->authorization,
        );
    }

    public function test_get_structure_builds_nested_tree_from_single_query_rows(): void
    {
        $course = $this->sampleCourse();
        $sectionOne = new Section(10, 1, 'Basics', 0, new \DateTimeImmutable('2026-01-15 10:00:00'));
        $sectionTwo = new Section(11, 1, 'Advanced', 1, new \DateTimeImmutable('2026-01-15 10:00:00'));
        $lessonOne = $this->sampleLesson(20, 10, 'Intro', 0);
        $lessonTwo = $this->sampleLesson(21, 10, 'Setup', 1);

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canViewCourse')->with(2, 2)->willReturn(true);
        $this->sectionRepository
            ->expects($this->once())
            ->method('loadStructureRows')
            ->with(1)
            ->willReturn([
                ['section' => $sectionOne, 'lesson' => $lessonOne],
                ['section' => $sectionOne, 'lesson' => $lessonTwo],
                ['section' => $sectionTwo, 'lesson' => null],
            ]);

        $result = $this->service->getStructure(1, 2);

        $this->assertSame(1, $result->courseId);
        $this->assertSame('Sample Course', $result->title);
        $this->assertCount(2, $result->sections);
        $this->assertSame('Basics', $result->sections[0]->title);
        $this->assertCount(2, $result->sections[0]->lessons);
        $this->assertSame('Intro', $result->sections[0]->lessons[0]->title);
        $this->assertSame('Advanced', $result->sections[1]->title);
        $this->assertSame([], $result->sections[1]->lessons);
    }

    public function test_get_structure_throws_not_found_for_missing_course(): void
    {
        $this->courseRepository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->getStructure(99, 2);
    }

    public function test_get_structure_throws_forbidden_for_unauthorized_user(): void
    {
        $course = $this->sampleCourse(authorId: 8);

        $this->courseRepository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canViewCourse')->with(3, 8)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->getStructure(1, 3);
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

    private function sampleLesson(int $id, int $sectionId, string $title, int $sortOrder): Lesson
    {
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');

        return new Lesson(
            $id,
            $sectionId,
            1,
            $title,
            strtolower(str_replace(' ', '-', $title)),
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
