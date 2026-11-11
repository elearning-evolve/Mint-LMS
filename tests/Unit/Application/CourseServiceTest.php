<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\Dto\CreateCourseDto;
use MintLMS\Application\Course\Dto\UpdateCourseDto;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Shared\Clock;
use PHPUnit\Framework\TestCase;

final class CourseServiceTest extends TestCase
{
    private CourseRepositoryInterface $repository;

    private AuthorizationInterface $authorization;

    private Clock $clock;

    private CourseService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CourseRepositoryInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $this->service = new CourseService($this->repository, $this->authorization, $this->clock);

        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-15 10:00:00'));
    }

    public function test_create_persists_draft_course_for_authorized_user(): void
    {
        $this->authorization->method('canCreateCourse')->with(5)->willReturn(true);
        $this->repository->method('findBySlug')->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Course $course): bool {
                return 0 === $course->id
                    && 'Intro to PHP' === $course->title
                    && 'intro-to-php' === $course->slug
                    && CourseStatus::Draft === $course->status
                    && EnrollmentType::Open === $course->enrollmentType
                    && 5 === $course->authorId;
            }))
            ->willReturnCallback(static function (Course $course): Course {
                return new Course(
                    1,
                    $course->title,
                    $course->slug,
                    $course->description,
                    $course->featuredImageId,
                    $course->status,
                    $course->enrollmentType,
                    $course->authorId,
                    $course->createdAt,
                    $course->updatedAt,
                );
            });

        $dto = new CreateCourseDto('Intro to PHP', null, 'Basics', null, EnrollmentType::Open);
        $result = $this->service->create($dto, 5);

        $this->assertSame(1, $result->id);
        $this->assertSame('intro-to-php', $result->slug);
        $this->assertSame('draft', $result->status);
        $this->assertSame('open', $result->enrollmentType);
        $this->assertTrue($result->emailOnPublish);
        $this->assertTrue($result->studentComplete);
        $this->assertSame('linear', $result->progression);
        $this->assertSame('everyone', $result->lessonListVisibility);
        $this->assertFalse($result->certificate);
        $this->assertFalse($result->expireAccess);
        $this->assertFalse($result->prerequisitesEnabled);
        $this->assertFalse($result->paginationEnabled);
        $this->assertFalse($result->materialsEnabled);
    }

    public function test_create_throws_forbidden_when_unauthorized(): void
    {
        $this->authorization->method('canCreateCourse')->with(3)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $dto = new CreateCourseDto('Course', null, '', null, EnrollmentType::Open);
        $this->service->create($dto, 3);
    }

    public function test_create_rejects_empty_title(): void
    {
        $this->authorization->method('canCreateCourse')->willReturn(true);

        $this->expectException(ValidationException::class);

        $dto = new CreateCourseDto('   ', null, '', null, EnrollmentType::Open);
        $this->service->create($dto, 1);
    }

    public function test_update_changes_fields_for_course_owner(): void
    {
        $existing = $this->sampleCourse(authorId: 7);

        $this->repository->method('findById')->with(10)->willReturn($existing);
        $this->authorization->method('canEditCourse')->with(7, 7)->willReturn(true);
        $this->repository->method('findBySlug')->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Course $course): bool {
                return 'Updated Title' === $course->title && 'updated-title' === $course->slug;
            }))
            ->willReturnArgument(0);

        $dto = new UpdateCourseDto('Updated Title', 'updated-title');
        $result = $this->service->update(10, $dto, 7);

        $this->assertSame('Updated Title', $result->title);
        $this->assertSame('updated-title', $result->slug);
    }

    public function test_update_throws_not_found_when_missing(): void
    {
        $this->repository->method('findById')->with(99)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->update(99, new UpdateCourseDto('Title'), 1);
    }

    public function test_delete_removes_course_for_authorized_user(): void
    {
        $course = $this->sampleCourse();

        $this->repository->method('findById')->with(4)->willReturn($course);
        $this->authorization->method('canDeleteCourse')->with(2, 2)->willReturn(true);

        $this->repository->expects($this->once())->method('delete')->with(4);

        $this->service->delete(4, 2);
    }

    public function test_get_throws_forbidden_for_other_users_course(): void
    {
        $course = $this->sampleCourse(authorId: 8);

        $this->repository->method('findById')->with(1)->willReturn($course);
        $this->authorization->method('canViewCourse')->with(3, 8)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->get(1, 3);
    }

    public function test_list_scopes_to_author_when_user_cannot_view_all(): void
    {
        $course = $this->sampleCourse(authorId: 6);

        $this->authorization->method('canListCourses')->with(6)->willReturn(true);
        $this->authorization->method('canViewAllCourses')->with(6)->willReturn(false);

        $this->repository
            ->expects($this->once())
            ->method('list')
            ->with(1, 20, 6, null, null)
            ->willReturn(['courses' => [$course], 'total' => 1]);

        $result = $this->service->list(1, 20, 6);

        $this->assertSame(1, $result->total);
        $this->assertCount(1, $result->courses);
    }

    public function test_publish_sets_status_to_published(): void
    {
        $course = $this->sampleCourse(status: CourseStatus::Draft);

        $this->repository->method('findById')->with(12)->willReturn($course);
        $this->authorization->method('canPublishCourse')->with(2, 2)->willReturn(true);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Course $saved): bool {
                return CourseStatus::Published === $saved->status;
            }))
            ->willReturnArgument(0);

        $result = $this->service->publish(12, 2);

        $this->assertSame('published', $result->status);
    }

    private function sampleCourse(
        int $authorId = 2,
        CourseStatus $status = CourseStatus::Draft,
    ): Course {
        $now = new \DateTimeImmutable('2026-01-15 10:00:00');

        return new Course(
            4,
            'Sample',
            'sample',
            'Description',
            null,
            $status,
            EnrollmentType::Open,
            $authorId,
            $now,
            $now,
        );
    }
}
