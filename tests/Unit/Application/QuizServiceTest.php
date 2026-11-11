<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Quiz\Dto\SubmitQuizAttemptDto;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Quiz\Quiz;
use MintLMS\Domain\Quiz\QuizAttempt;
use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Domain\Quiz\QuizRepositoryInterface;
use MintLMS\Domain\Shared\Clock;
use PHPUnit\Framework\TestCase;

final class QuizServiceTest extends TestCase
{
    private QuizRepositoryInterface $quizRepository;

    private LessonRepositoryInterface $lessonRepository;

    private CourseRepositoryInterface $courseRepository;

    private AuthorizationInterface $authorization;

    private Clock $clock;

    private ProgressRepositoryInterface $progressRepository;

    private UserLookupInterface $userLookup;

    private QuizService $service;

    protected function setUp(): void
    {
        $this->quizRepository = $this->createMock(QuizRepositoryInterface::class);
        $this->lessonRepository = $this->createMock(LessonRepositoryInterface::class);
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);
        $this->userLookup = $this->createMock(UserLookupInterface::class);
        $this->service = new QuizService(
            $this->quizRepository,
            $this->lessonRepository,
            $this->courseRepository,
            $this->authorization,
            $this->clock,
            $this->progressRepository,
            $this->userLookup,
        );

        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-15 10:00:00'));
    }

    public function test_grade_attempt_all_correct_scores_100(): void
    {
        $questions = [
            $this->mcqQuestion(1, 'A'),
            $this->trueFalseQuestion(2, 'true'),
        ];

        $score = $this->service->gradeAttempt($questions, [
            1 => 'A',
            2 => 'true',
        ]);

        $this->assertSame(100.0, $score);
    }

    public function test_grade_attempt_half_correct_scores_50(): void
    {
        $questions = [
            $this->mcqQuestion(1, 'A'),
            $this->trueFalseQuestion(2, 'true'),
        ];

        $score = $this->service->gradeAttempt($questions, [
            1 => 'B',
            2 => 'true',
        ]);

        $this->assertSame(50.0, $score);
    }

    public function test_submit_attempt_passes_when_score_meets_threshold(): void
    {
        $quiz = new Quiz(5, 10, 1, 'Quiz', 70, 0);
        $questions = [
            $this->mcqQuestion(1, 'A'),
            $this->trueFalseQuestion(2, 'false'),
        ];

        $this->quizRepository->method('findById')->with(5)->willReturn($quiz);
        $this->quizRepository->method('findQuestionsByQuizId')->with(5)->willReturn($questions);
        $this->lessonRepository->method('findById')->with(1)->willReturn($this->sampleLesson(1, false));
        $this->progressRepository->method('isUserEnrolled')->with(7, 10)->willReturn(true);
        $this->quizRepository
            ->expects($this->once())
            ->method('saveAttempt')
            ->with($this->callback(static function (QuizAttempt $attempt): bool {
                return 7 === $attempt->userId
                    && 5 === $attempt->quizId
                    && 100.0 === $attempt->scorePercent
                    && true === $attempt->passed;
            }))
            ->willReturnCallback(static fn (QuizAttempt $attempt): QuizAttempt => new QuizAttempt(
                99,
                $attempt->userId,
                $attempt->quizId,
                $attempt->scorePercent,
                $attempt->passed,
                $attempt->answers,
                $attempt->completedAt,
            ));

        $result = $this->service->submitAttempt(
            5,
            new SubmitQuizAttemptDto([
                1 => 'A',
                2 => 'false',
            ]),
            7
        );

        $this->assertTrue($result->passed);
        $this->assertSame(100.0, $result->scorePercent);
    }

    public function test_submit_attempt_fails_when_below_threshold(): void
    {
        $quiz = new Quiz(5, 10, 1, 'Quiz', 70, 0);
        $questions = [
            $this->mcqQuestion(1, 'A'),
            $this->trueFalseQuestion(2, 'false'),
        ];

        $this->quizRepository->method('findById')->with(5)->willReturn($quiz);
        $this->quizRepository->method('findQuestionsByQuizId')->with(5)->willReturn($questions);
        $this->lessonRepository->method('findById')->with(1)->willReturn($this->sampleLesson(1, false));
        $this->progressRepository->method('isUserEnrolled')->with(7, 10)->willReturn(true);
        $this->quizRepository
            ->method('saveAttempt')
            ->willReturnCallback(static fn (QuizAttempt $attempt): QuizAttempt => new QuizAttempt(
                100,
                $attempt->userId,
                $attempt->quizId,
                $attempt->scorePercent,
                $attempt->passed,
                $attempt->answers,
                $attempt->completedAt,
            ));

        $result = $this->service->submitAttempt(
            5,
            new SubmitQuizAttemptDto([
                1 => 'B',
                2 => 'false',
            ]),
            7
        );

        $this->assertFalse($result->passed);
        $this->assertSame(50.0, $result->scorePercent);
    }

    public function test_has_passed_delegates_to_repository(): void
    {
        $this->quizRepository->method('hasPassed')->with(3, 8)->willReturn(true);

        $this->assertTrue($this->service->hasPassed(3, 8));
    }

    public function test_submit_attempt_requires_login(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->service->submitAttempt(1, new SubmitQuizAttemptDto([]), 0);
    }

    public function test_submit_attempt_requires_enrollment_for_non_preview_lesson(): void
    {
        $quiz = new Quiz(5, 10, 1, 'Quiz', 70, 0);
        $questions = [$this->mcqQuestion(1, 'A')];

        $this->quizRepository->method('findById')->with(5)->willReturn($quiz);
        $this->quizRepository->method('findQuestionsByQuizId')->with(5)->willReturn($questions);
        $this->lessonRepository->method('findById')->with(1)->willReturn($this->sampleLesson(1, false));
        $this->progressRepository->method('isUserEnrolled')->with(7, 10)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->service->submitAttempt(5, new SubmitQuizAttemptDto([1 => 'A']), 7);
    }

    public function test_true_false_comparison_is_case_insensitive(): void
    {
        $question = $this->trueFalseQuestion(1, 'True');

        $this->assertTrue($this->service->isAnswerCorrect($question, 'true'));
        $this->assertFalse($this->service->isAnswerCorrect($question, 'false'));
    }

    public function test_mcq_multi_requires_exact_set_match(): void
    {
        $question = new QuizQuestion(
            3,
            5,
            QuizQuestion::TYPE_MCQ_MULTI,
            'Pick all that apply',
            ['A', 'B', 'C'],
            '["A","C"]',
            0,
        );

        $this->assertTrue($this->service->isAnswerCorrect($question, '["C","A"]'));
        $this->assertFalse($this->service->isAnswerCorrect($question, '["A"]'));
        $this->assertFalse($this->service->isAnswerCorrect($question, 'A'));
    }

    public function test_grade_attempt_excludes_essay_from_denominator(): void
    {
        $questions = [
            $this->mcqQuestion(1, 'A'),
            new QuizQuestion(2, 5, QuizQuestion::TYPE_ESSAY, 'Explain', [], '', 1),
        ];

        $score = $this->service->gradeAttempt($questions, [
            1 => 'A',
            2 => 'Student essay text',
        ]);

        $this->assertSame(100.0, $score);
    }

    public function test_grade_attempt_essay_only_scores_zero_pending(): void
    {
        $questions = [
            new QuizQuestion(1, 5, QuizQuestion::TYPE_ESSAY, 'Explain A', [], '', 0),
            new QuizQuestion(2, 5, QuizQuestion::TYPE_ESSAY, 'Explain B', [], '', 1),
        ];

        $grade = $this->service->evaluateAttempt(
            $questions,
            [
                1 => 'Answer A',
                2 => 'Answer B',
            ],
            70,
            false
        );

        $this->assertSame(0.0, $grade->scorePercent);
        $this->assertFalse($grade->passed);
        $this->assertTrue($grade->pendingEssay);
        $this->assertFalse($grade->persisted);
    }

    public function test_submit_attempt_rejects_editor_without_enrollment(): void
    {
        $quiz = new Quiz(5, 10, 1, 'Quiz', 70, 0);
        $questions = [$this->mcqQuestion(1, 'A')];

        $this->quizRepository->method('findById')->with(5)->willReturn($quiz);
        $this->quizRepository->method('findQuestionsByQuizId')->with(5)->willReturn($questions);
        $this->lessonRepository->method('findById')->with(1)->willReturn($this->sampleLesson(1, false));
        $this->progressRepository->method('isUserEnrolled')->with(7, 10)->willReturn(false);
        $this->courseRepository->method('findById')->willReturn(
            new \MintLMS\Domain\Course\Course(
                10,
                'Course',
                'course',
                '',
                null,
                \MintLMS\Domain\Course\CourseStatus::Published,
                \MintLMS\Domain\Course\EnrollmentType::Free,
                7,
                new \DateTimeImmutable('2026-01-01 00:00:00'),
                new \DateTimeImmutable('2026-01-01 00:00:00'),
            )
        );
        $this->authorization->method('canEditCourse')->willReturn(true);
        $this->quizRepository->expects($this->never())->method('saveAttempt');

        $this->expectException(ForbiddenException::class);

        $this->service->submitAttempt(5, new SubmitQuizAttemptDto([1 => 'A']), 7);
    }

    private function mcqQuestion(int $id, string $correct): QuizQuestion
    {
        return new QuizQuestion(
            $id,
            5,
            QuizQuestion::TYPE_MCQ,
            'Pick one',
            ['A', 'B', 'C'],
            $correct,
            0,
        );
    }

    private function trueFalseQuestion(int $id, string $correct): QuizQuestion
    {
        return new QuizQuestion(
            $id,
            5,
            QuizQuestion::TYPE_TRUE_FALSE,
            'True or false?',
            ['True', 'False'],
            $correct,
            1,
        );
    }

    private function sampleLesson(int $id, bool $isPreview): Lesson
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        return new Lesson(
            $id,
            1,
            10,
            'Lesson',
            'lesson-' . $id,
            'Content',
            null,
            null,
            $isPreview,
            null,
            0,
            $now,
            $now,
        );
    }
}
