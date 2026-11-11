<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Quiz\Dto\QuizDto;
use MintLMS\Application\Quiz\Dto\QuizQuestionDto;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Application\Student\StudentExperienceService;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseSettings;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Enrollment\Enrollment;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Progress\ProgressSummary;
use MintLMS\Domain\Section\Section;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Domain\Shared\Clock;
use MintLMS\Domain\Shared\UserId;
use PHPUnit\Framework\TestCase;

final class StudentExperienceServiceTest extends TestCase
{
    private CourseRepositoryInterface $courseRepository;

    private SectionRepositoryInterface $sectionRepository;

    private EnrollmentRepositoryInterface $enrollmentRepository;

    private ProgressRepositoryInterface $progressRepository;

    private Clock $clock;

    private StudentExperienceService $service;

    protected function setUp(): void
    {
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->sectionRepository = $this->createMock(SectionRepositoryInterface::class);
        $this->enrollmentRepository = $this->createMock(EnrollmentRepositoryInterface::class);
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-05 00:00:00'));

        $this->service = new StudentExperienceService(
            $this->courseRepository,
            $this->sectionRepository,
            $this->enrollmentRepository,
            $this->progressRepository,
            $this->clock,
        );
    }

    public function test_dashboard_excludes_completed_courses(): void
    {
        $userId = 7;
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $this->enrollmentRepository
            ->method('listByUser')
            ->with($userId, 1, 100, EnrollmentStatus::Active)
            ->willReturn([
                'enrollments' => [
                    new Enrollment(1, $userId, 10, EnrollmentStatus::Active, $now, null, null),
                    new Enrollment(2, $userId, 20, EnrollmentStatus::Active, $now, null, null),
                ],
                'total' => 2,
            ]);

        $this->courseRepository
            ->method('findById')
            ->willReturnMap([
                [10, $this->sampleCourse(10, 'In Progress Course')],
                [20, $this->sampleCourse(20, 'Done Course')],
            ]);

        $this->progressRepository
            ->method('getSummary')
            ->willReturnMap([
                [
                    $userId,
                    10,
                    new ProgressSummary(UserId::fromInt($userId), 10, 1, 4, 25.0, 101, $now),
                ],
                [
                    $userId,
                    20,
                    new ProgressSummary(UserId::fromInt($userId), 20, 3, 3, 100.0, 201, $now),
                ],
            ]);

        $this->sectionRepository
            ->method('loadStructureRows')
            ->willReturn($this->sampleStructureRows());

        $this->progressRepository
            ->method('getCompletedLessonIds')
            ->willReturn([]);

        $courses = $this->service->getDashboardCourses($userId);

        $this->assertCount(1, $courses);
        $this->assertSame(10, $courses[0]->courseId);
        $this->assertSame(101, $courses[0]->lastLessonId);
    }

    public function test_my_courses_completed_filter(): void
    {
        $userId = 7;
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $this->enrollmentRepository
            ->method('listByUser')
            ->willReturn([
                'enrollments' => [
                    new Enrollment(1, $userId, 10, EnrollmentStatus::Active, $now, null, null),
                    new Enrollment(2, $userId, 20, EnrollmentStatus::Active, $now, null, null),
                ],
                'total' => 2,
            ]);

        $this->courseRepository
            ->method('findById')
            ->willReturnMap([
                [10, $this->sampleCourse(10, 'In Progress Course')],
                [20, $this->sampleCourse(20, 'Done Course')],
            ]);

        $this->progressRepository
            ->method('getSummary')
            ->willReturnMap([
                [
                    $userId,
                    10,
                    new ProgressSummary(UserId::fromInt($userId), 10, 1, 4, 25.0, 101, $now),
                ],
                [
                    $userId,
                    20,
                    new ProgressSummary(UserId::fromInt($userId), 20, 3, 3, 100.0, 201, $now),
                ],
            ]);

        $this->sectionRepository
            ->method('loadStructureRows')
            ->willReturn($this->sampleStructureRows());

        $this->progressRepository
            ->method('getCompletedLessonIds')
            ->willReturn([]);

        $completed = $this->service->getMyCourses($userId, 'completed');

        $this->assertCount(1, $completed);
        $this->assertSame(20, $completed[0]->courseId);
        $this->assertTrue($completed[0]->isComplete);
    }

    public function test_course_overview_strips_non_preview_content_for_guests(): void
    {
        $course = $this->sampleCourse(10, 'Sample Course');

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->sampleStructureRows());

        $overview = $this->service->getCourseOverview(10, 0);

        $this->assertFalse($overview->isEnrolled);
        $this->assertFalse($overview->canEnroll);
        $this->assertFalse($overview->isLoggedIn);

        $lessons = $overview->structure->sections[0]->lessons;
        $this->assertSame('', $lessons[0]->content);
        $this->assertNull($lessons[0]->videoUrl);
        $this->assertSame('Preview body', $lessons[1]->content);
        $this->assertTrue($overview->curriculumVisible);
        $this->assertNotSame([], $overview->contentOutline);
    }

    public function test_free_course_shows_lesson_list_to_guests_without_access(): void
    {
        $course = $this->sampleCourse(10, 'Free Course', EnrollmentType::Free);

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->sampleStructureRows());

        $overview = $this->service->getCourseOverview(10, 0);

        $this->assertTrue($overview->curriculumVisible);
        $this->assertNotSame([], $overview->contentOutline);
        $this->assertFalse($overview->contentOutline[0]['lessons'][0]['accessible']);
        $this->assertSame('', $overview->structure->sections[0]->lessons[0]->content);
    }

    public function test_enrolled_only_lesson_list_hides_curriculum_from_guests(): void
    {
        $settings = CourseSettings::fromArray(array('lessonListVisibility' => 'enrolled'));
        $course   = $this->sampleCourse(10, 'Hidden List', EnrollmentType::Free, $settings);

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->sampleStructureRows());

        $overview = $this->service->getCourseOverview(10, 0);

        $this->assertFalse($overview->curriculumVisible);
        $this->assertSame([], $overview->contentOutline);
    }

    public function test_player_context_blocks_non_preview_lesson_for_guest(): void
    {
        $course = $this->sampleCourse(10, 'Sample Course');

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->sampleStructureRows());
        $this->progressRepository->method('isUserEnrolled')->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->getPlayerContext(10, 100, 0);
    }

    public function test_player_context_allows_preview_lesson_for_guest(): void
    {
        $course = $this->sampleCourse(10, 'Sample Course');

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->sampleStructureRows());
        $this->progressRepository->method('isUserEnrolled')->willReturn(false);

        $context = $this->service->getPlayerContext(10, 101, 0);

        $this->assertFalse($context->isEnrolled);
        $this->assertSame(101, $context->currentLesson->id);
        $this->assertSame('Preview body', $context->currentLesson->content);
    }

    public function test_player_context_blocks_drip_locked_lesson(): void
    {
        $userId = 7;
        $course = $this->sampleCourse(10, 'Sample Course');
        $enrolledAt = new \DateTimeImmutable('2026-01-01 00:00:00');

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->dripStructureRows());
        $this->progressRepository->method('isUserEnrolled')->willReturn(true);
        $this->enrollmentRepository
            ->method('findByUserAndCourse')
            ->with($userId, 10)
            ->willReturn(new Enrollment(1, $userId, 10, EnrollmentStatus::Active, $enrolledAt, null, null));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Available in 3 days');

        $this->service->getPlayerContext(10, 100, $userId);
    }

    public function test_lesson_preview_allows_enrolled_student(): void
    {
        $userId = 7;
        $course = $this->sampleCourse(10, 'Sample Course');

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->sampleStructureRows());
        $this->progressRepository->method('isUserEnrolled')->willReturn(true);
        $this->progressRepository->method('getSummary')->willReturn(null);
        $this->progressRepository->method('getCompletedLessonIds')->willReturn([]);

        $preview = $this->service->getLessonBuilderPreview(10, 100, $userId);

        $this->assertSame('Locked lesson', $preview['title']);
        $this->assertSame('Sample Course', $preview['courseTitle']);
        $this->assertSame(100, $preview['lessonId']);
    }

    public function test_lesson_preview_blocks_guest_for_non_preview_lesson(): void
    {
        $course = $this->sampleCourse(10, 'Sample Course');

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->sampleStructureRows());
        $this->progressRepository->method('isUserEnrolled')->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->getLessonBuilderPreview(10, 100, 0);
    }

    public function test_player_context_includes_quiz_when_present(): void
    {
        $userId = 7;
        $course = $this->sampleCourse(10, 'Sample Course');

        $this->courseRepository->method('findById')->with(10)->willReturn($course);
        $this->sectionRepository->method('loadStructureRows')->with(10)->willReturn($this->sampleStructureRows());
        $this->progressRepository->method('isUserEnrolled')->willReturn(true);
        $this->progressRepository->method('getSummary')->willReturn(null);
        $this->progressRepository->method('getCompletedLessonIds')->willReturn([]);

        $quizService = $this->createMock(QuizService::class);
        $quizDto = new QuizDto(
            55,
            100,
            10,
            'Lesson quiz',
            70,
            0,
            [
                new QuizQuestionDto(1, 'mcq', 'Pick one', ['A', 'B'], null, 0),
            ],
        );
        $quizService->method('getByLessonId')->with(100, $userId, true)->willReturn($quizDto);
        $quizService->method('hasPassed')->with($userId, 55)->willReturn(false);

        $service = new StudentExperienceService(
            $this->courseRepository,
            $this->sectionRepository,
            $this->enrollmentRepository,
            $this->progressRepository,
            $this->clock,
            $quizService,
        );

        $context = $service->getPlayerContext(10, 100, $userId);

        $this->assertTrue($context->quizRequired);
        $this->assertFalse($context->hasPassedQuiz);
        $this->assertNotNull($context->quiz);
        $this->assertSame(55, $context->quiz->id);
    }

    public function test_unpublished_course_is_not_found(): void
    {
        $draft = new Course(
            10,
            'Draft',
            'draft',
            '',
            null,
            CourseStatus::Draft,
            EnrollmentType::Open,
            1,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $this->courseRepository->method('findById')->with(10)->willReturn($draft);

        $this->expectException(NotFoundException::class);

        $this->service->getCourseOverview(10, 0);
    }

    private function sampleCourse(int $id, string $title, EnrollmentType $enrollmentType = EnrollmentType::Open, ?CourseSettings $settings = null): Course
    {
        return new Course(
            $id,
            $title,
            'course-' . $id,
            'Description',
            null,
            CourseStatus::Published,
            $enrollmentType,
            1,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            $settings ?? new CourseSettings(),
        );
    }

    /**
     * @return list<array{section: Section, lesson: Lesson|null}>
     */
    private function sampleStructureRows(): array
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');
        $section = new Section(1, 10, 'Section 1', 0, $now);

        return [
            [
                'section' => $section,
                'lesson' => new Lesson(
                    100,
                    1,
                    10,
                    'Locked lesson',
                    'locked-lesson',
                    'Secret content',
                    'https://example.com/video.mp4',
                    55,
                    false,
                    null,
                    0,
                    $now,
                    $now,
                ),
            ],
            [
                'section' => $section,
                'lesson' => new Lesson(
                    101,
                    1,
                    10,
                    'Preview lesson',
                    'preview-lesson',
                    'Preview body',
                    null,
                    null,
                    true,
                    null,
                    1,
                    $now,
                    $now,
                ),
            ],
        ];
    }

    /**
     * @return list<array{section: Section, lesson: Lesson|null}>
     */
    private function dripStructureRows(): array
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');
        $section = new Section(1, 10, 'Section 1', 0, $now);

        return [
            [
                'section' => $section,
                'lesson' => new Lesson(
                    100,
                    1,
                    10,
                    'Drip lesson',
                    'drip-lesson',
                    'Secret content',
                    null,
                    null,
                    false,
                    7,
                    0,
                    $now,
                    $now,
                ),
            ],
        ];
    }
}
