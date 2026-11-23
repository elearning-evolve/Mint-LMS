<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\QuizLifecycleInterface;
use MintLMS\Application\Contract\QuizStudentQueryInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Lesson\LessonAccessService;
use MintLMS\Application\Quiz\Dto\CreateQuestionDto;
use MintLMS\Application\Quiz\Dto\CreateQuizDto;
use MintLMS\Application\Quiz\Dto\QuizAttemptDto;
use MintLMS\Application\Quiz\Dto\QuizDto;
use MintLMS\Application\Quiz\Dto\QuizGradeDto;
use MintLMS\Application\Quiz\Dto\ReorderQuestionsDto;
use MintLMS\Application\Quiz\Dto\ReorderQuizzesDto;
use MintLMS\Application\Quiz\Dto\SubmitQuizAttemptDto;
use MintLMS\Application\Quiz\Dto\UpdateQuestionDto;
use MintLMS\Application\Quiz\Dto\UpdateQuizDto;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Quiz\EssayGradingMode;
use MintLMS\Domain\Quiz\EssaySubmission;
use MintLMS\Domain\Quiz\EssaySubmissionRepositoryInterface;
use MintLMS\Domain\Quiz\Quiz;
use MintLMS\Domain\Quiz\QuizAttempt;
use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Domain\Quiz\QuizRepositoryInterface;
use MintLMS\Domain\Shared\Clock;

final class QuizService implements QuizLifecycleInterface, QuizStudentQueryInterface {

	public function __construct(
		private QuizRepositoryInterface $quizRepository,
		private LessonRepositoryInterface $lessonRepository,
		private CourseRepositoryInterface $courseRepository,
		private AuthorizationInterface $authorization,
		private Clock $clock,
		private ProgressRepositoryInterface $progressRepository,
		private UserLookupInterface $userLookup,
		private ?LessonAccessService $lessonAccessService = null,
		private ?EssaySubmissionRepositoryInterface $essaySubmissionRepository = null,
		private ?QuizTimerService $quizTimer = null,
	) {
	}

	public function getByLessonId( int $lessonId, int $userId, bool $forStudent = false, bool $editorReview = false ): ?QuizDto {
		$quizzes = $this->listByLessonId( $lessonId, $userId, $forStudent, $editorReview );

		return $quizzes[0] ?? null;
	}

	/**
	 * Quizzes attached to a lesson. Students only see published quizzes/questions;
	 * builder / editor review includes drafts (each item publishes via Live).
	 *
	 * @return list<QuizDto>
	 */
	public function listByLessonId( int $lessonId, int $userId, bool $forStudent = false, bool $editorReview = false ): array {
		$lesson = $this->findLessonOrFail( $lessonId );

		if ( $forStudent ) {
			if ( $lesson->courseId <= 0 ) {
				// Library lesson (not in a course yet): students never see it, but instructor
				// preview must still list quizzes after quiz→lesson associate.
				if ( ! $editorReview ) {
					throw new NotFoundException( 'Quiz not found.' );
				}
				$this->assertCanManageLessonContent( $userId, $lesson );
			} else {
				$course = $this->findCourseOrFail( $lesson->courseId );
				if ( $editorReview || $this->canEditorReviewCourse( $userId, $course->authorId ) ) {
					$editorReview = true;
				} else {
					$this->assertStudentQuizAccess( $userId, $lesson->courseId, $lesson->isPreview, $lesson->id );
				}
			}
		} else {
			$this->assertCanManageLessonContent( $userId, $lesson );
		}

		$publishedOnly = $forStudent && ! $editorReview;
		$out           = array();
		foreach ( $this->quizRepository->findAllByLessonId( $lessonId, $publishedOnly ) as $quiz ) {
			$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id, $publishedOnly );
			$out[]     = QuizDto::fromQuiz( $quiz, $questions, ! $forStudent );
		}

		return $out;
	}

	public function get( int $quizId, int $userId, bool $forStudent = false ): QuizDto {
		$quiz         = $this->findQuizOrFail( $quizId );
		$editorReview = false;

		if ( $forStudent ) {
			if ( $quiz->courseId <= 0 ) {
				throw new NotFoundException( 'Quiz not found.' );
			}
			$lesson       = $this->findLessonOrFail( $quiz->lessonId );
			$course       = $this->findCourseOrFail( $quiz->courseId );
			$editorReview = $this->canEditorReviewCourse( $userId, $course->authorId );
			if ( ! $editorReview ) {
				$this->assertStudentQuizAccess( $userId, $quiz->courseId, $lesson->isPreview, $lesson->id );
			}
		} else {
			$lesson = $this->findLessonOrFail( $quiz->lessonId );
			$this->assertCanManageLessonContent( $userId, $lesson );
		}

		$publishedOnly = $forStudent && ! $editorReview;
		if ( $publishedOnly ) {
			$status = function_exists( 'get_post_status' ) ? get_post_status( $quiz->id ) : 'publish';
			if ( is_string( $status ) && 'publish' !== $status ) {
				throw new NotFoundException( 'Quiz not found.' );
			}
		}

		$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id, $publishedOnly );

		return QuizDto::fromQuiz( $quiz, $questions, ! $forStudent );
	}

	public function create( int $lessonId, CreateQuizDto $dto, int $userId ): QuizDto {
		$lesson = $this->findLessonOrFail( $lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$title = trim( $dto->title );

		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$passPercent = $this->normalizePassPercent( $dto->passPercent );
		$sortOrder   = $this->quizRepository->nextQuizSortOrderForLesson( $lessonId );

		$quiz = $this->quizRepository->saveQuiz(
			new Quiz(
				0,
				$lessonId,
				$lesson->courseId,
				$title,
				$passPercent,
				$sortOrder,
			)
		);

		if ( null !== $dto->questions ) {
			foreach ( $dto->questions as $index => $questionData ) {
				$this->createQuestionFromArray( $quiz->id, $questionData, $index );
			}
		}

		return $this->get( $quiz->id, $userId );
	}

	/**
	 * Create a course-scoped quiz without adding a visible course lesson.
	 * Uses a disposable host lesson (courseId 0); quiz META_COURSE_ID links it to the course.
	 */
	public function createForCourse( int $courseId, CreateQuizDto $dto, int $userId ): QuizDto {
		$course = $this->findCourseOrFail( $courseId );
		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$title = trim( $dto->title );
		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$passPercent = $this->normalizePassPercent( $dto->passPercent );
		$host        = $this->createStandaloneHostLesson();

		$quiz = $this->quizRepository->saveQuiz(
			new Quiz(
				0,
				$host->id,
				$courseId,
				$title,
				$passPercent,
				0,
			)
		);

		if ( null !== $dto->questions ) {
			foreach ( $dto->questions as $index => $questionData ) {
				$this->createQuestionFromArray( $quiz->id, $questionData, $index );
			}
		}

		return $this->get( $quiz->id, $userId );
	}

	/**
	 * Create a course-scoped question without requiring an existing (or visible) quiz.
	 * Host quiz is disposable until the author links it via attach.
	 *
	 * @return array{quiz: QuizDto, questionId: int}
	 */
	public function createQuestionForCourse( int $courseId, int $userId ): array {
		$quiz = $this->createForCourse(
			$courseId,
			new CreateQuizDto( __( 'New Quiz', 'mint-lms' ), 80, null ),
			$userId
		);

		$updated = $this->addQuestion(
			$quiz->id,
			new CreateQuestionDto(
				'essay',
				'New Question',
				array(),
				'',
			),
			$userId
		);

		$questionId = 0;
		foreach ( array_reverse( $updated->questions ) as $question ) {
			if ( $question->id > 0 ) {
				$questionId = $question->id;
				break;
			}
		}

		if ( $questionId <= 0 ) {
			throw new ValidationException( 'Could not create question.' );
		}

		return array(
			'quiz'       => $updated,
			'questionId' => $questionId,
		);
	}

	public function update( int $quizId, UpdateQuizDto $dto, int $userId ): QuizDto {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$title       = null !== $dto->title ? trim( $dto->title ) : $quiz->title;
		$passPercent = null !== $dto->passPercent ? $this->normalizePassPercent( $dto->passPercent ) : $quiz->passPercent;

		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$updated = new Quiz(
			$quiz->id,
			$quiz->lessonId,
			$quiz->courseId,
			$title,
			$passPercent,
			$quiz->sortOrder,
		);

		$this->quizRepository->saveQuiz( $updated );

		return $this->get( $quizId, $userId );
	}

	public function reorderQuizzes( int $lessonId, ReorderQuizzesDto $dto, int $userId ): void {
		$lesson = $this->findLessonOrFail( $lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		if ( array() === $dto->quizIds ) {
			throw new ValidationException( 'Validation failed.', array( 'ids' => 'At least one quiz ID is required.' ) );
		}

		$quizzes = $this->quizRepository->findAllByLessonId( $lessonId );

		if ( count( $quizzes ) !== count( $dto->quizIds ) ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'ids' => 'Quiz IDs must include every quiz on this lesson.' )
			);
		}

		$existingIds = array_map(
			static fn( Quiz $quiz ): int => $quiz->id,
			$quizzes
		);

		foreach ( $dto->quizIds as $quizId ) {
			if ( ! in_array( $quizId, $existingIds, true ) ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'ids' => 'One or more quiz IDs do not belong to this lesson.' )
				);
			}
		}

		if ( count( array_unique( $dto->quizIds ) ) !== count( $dto->quizIds ) ) {
			throw new ValidationException( 'Validation failed.', array( 'ids' => 'Quiz IDs must be unique.' ) );
		}

		$this->quizRepository->reorderQuizzes( $lessonId, $dto->quizIds );
	}

	public function reorderQuestions( int $quizId, ReorderQuestionsDto $dto, int $userId ): void {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		if ( array() === $dto->questionIds ) {
			throw new ValidationException( 'Validation failed.', array( 'ids' => 'At least one question ID is required.' ) );
		}

		$questions = $this->quizRepository->findQuestionsByQuizId( $quizId );

		if ( count( $questions ) !== count( $dto->questionIds ) ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'ids' => 'Question IDs must include every question on this quiz.' )
			);
		}

		$existingIds = array_map(
			static fn( QuizQuestion $question ): int => $question->id,
			$questions
		);

		foreach ( $dto->questionIds as $questionId ) {
			if ( ! in_array( $questionId, $existingIds, true ) ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'ids' => 'One or more question IDs do not belong to this quiz.' )
				);
			}
		}

		if ( count( array_unique( $dto->questionIds ) ) !== count( $dto->questionIds ) ) {
			throw new ValidationException( 'Validation failed.', array( 'ids' => 'Question IDs must be unique.' ) );
		}

		$this->quizRepository->reorderQuestions( $quizId, $dto->questionIds );
	}

	/**
	 * Move a quiz onto an existing lesson (first attach or change).
	 * Removes only disposable empty host lessons created for Add New Quiz.
	 */
	public function attachToLesson( int $quizId, int $targetLessonId, int $userId ): QuizDto {
		$quiz          = $this->findQuizOrFail( $quizId );
		$currentLesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $currentLesson );

		if ( $quiz->lessonId === $targetLessonId ) {
			return $this->get( $quizId, $userId );
		}

		$target = $this->findLessonOrFail( $targetLessonId );
		$this->assertCanManageLessonContent( $userId, $target );

		$previousLessonId = $quiz->lessonId;
		$sortOrder        = $this->quizRepository->nextQuizSortOrderForLesson( $targetLessonId );

		$this->quizRepository->saveQuiz(
			new Quiz(
				$quiz->id,
				$targetLessonId,
				$target->courseId,
				$quiz->title,
				$quiz->passPercent,
				$sortOrder,
			)
		);

		if ( $previousLessonId > 0 && $previousLessonId !== $targetLessonId && $this->isDisposableHostLesson( $currentLesson ) ) {
			$this->lessonRepository->delete( $previousLessonId );
		}

		delete_post_meta( $quizId, \MintLMS\Infrastructure\PostType\PostTypes::META_QUESTION_SHELL );

		return $this->get( $quizId, $userId );
	}

	/**
	 * Detach quiz from its lesson — places it on a fresh standalone host lesson.
	 */
	public function detachFromLesson( int $quizId, int $userId ): QuizDto {
		$quiz          = $this->findQuizOrFail( $quizId );
		$currentLesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $currentLesson );

		if ( $currentLesson->courseId <= 0 && $this->isDisposableHostLesson( $currentLesson ) ) {
			return $this->get( $quizId, $userId );
		}

		$host     = $this->createStandaloneHostLesson();
		$courseId = $quiz->courseId > 0 ? $quiz->courseId : $currentLesson->courseId;

		$this->quizRepository->saveQuiz(
			new Quiz(
				$quiz->id,
				$host->id,
				$courseId,
				$quiz->title,
				$quiz->passPercent,
				$quiz->sortOrder,
			)
		);

		return $this->get( $quizId, $userId );
	}

	/**
	 * Move a question onto an existing quiz (first attach or change).
	 */
	public function attachQuestionToQuiz( int $questionId, int $targetQuizId, int $userId ): QuizDto {
		$question      = $this->findQuestionOrFail( $questionId );
		$current       = $this->findQuizOrFail( $question->quizId );
		$currentLesson = $this->findLessonOrFail( $current->lessonId );
		$this->assertCanManageLessonContent( $userId, $currentLesson );

		if ( $question->quizId === $targetQuizId ) {
			return $this->get( $targetQuizId, $userId );
		}

		$target       = $this->findQuizOrFail( $targetQuizId );
		$targetLesson = $this->findLessonOrFail( $target->lessonId );
		$this->assertCanManageLessonContent( $userId, $targetLesson );

		$previousQuizId   = $question->quizId;
		$previousLessonId = $current->lessonId;
		$sortOrder        = $this->quizRepository->nextQuestionSortOrder( $targetQuizId );

		$this->quizRepository->saveQuestion(
			new QuizQuestion(
				$question->id,
				$targetQuizId,
				$question->type,
				$question->prompt,
				$question->options,
				$question->correctAnswer,
				$sortOrder,
			)
		);

		$remaining = $this->quizRepository->findQuestionsByQuizId( $previousQuizId );
		if ( array() === $remaining && $this->isDisposableHostLesson( $currentLesson ) ) {
			$this->quizRepository->deleteQuiz( $previousQuizId );
			if ( $previousLessonId > 0 ) {
				$this->lessonRepository->delete( $previousLessonId );
			}
		}

		return $this->get( $targetQuizId, $userId );
	}

	/**
	 * Detach question from its quiz — places it on a fresh standalone host quiz.
	 */
	public function detachQuestionFromQuiz( int $questionId, int $userId ): QuizDto {
		$question      = $this->findQuestionOrFail( $questionId );
		$current       = $this->findQuizOrFail( $question->quizId );
		$currentLesson = $this->findLessonOrFail( $current->lessonId );
		$this->assertCanManageLessonContent( $userId, $currentLesson );

		if ( $currentLesson->courseId <= 0 && $this->isDisposableHostLesson( $currentLesson ) ) {
			$others = array_filter(
				$this->quizRepository->findQuestionsByQuizId( $current->id ),
				static fn( QuizQuestion $q ): bool => $q->id !== $questionId
			);
			if ( array() === $others ) {
				return $this->get( $current->id, $userId );
			}
		}

		$hostLesson = $this->createStandaloneHostLesson();
		$courseId   = $current->courseId > 0 ? $current->courseId : $currentLesson->courseId;
		$hostQuiz   = $this->quizRepository->saveQuiz(
			new Quiz(
				0,
				$hostLesson->id,
				$courseId,
				__( 'New Quiz', 'mint-lms' ),
				80,
				0,
			)
		);

		$this->quizRepository->saveQuestion(
			new QuizQuestion(
				$question->id,
				$hostQuiz->id,
				$question->type,
				$question->prompt,
				$question->options,
				$question->correctAnswer,
				0,
			)
		);

		return $this->get( $hostQuiz->id, $userId );
	}

	public function delete( int $quizId, int $userId ): void {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$hostLessonId = $quiz->lessonId;
		$this->quizRepository->deleteQuiz( $quizId );

		// Unlinked quizzes sit on disposable host lessons — remove the empty host.
		if ( $this->isDisposableHostLesson( $lesson ) ) {
			$remaining = $this->quizRepository->findAllByLessonId( $hostLessonId );
			if ( array() === $remaining ) {
				$this->lessonRepository->delete( $hostLessonId );
			}
		}
	}

	public function addQuestion( int $quizId, CreateQuestionDto $dto, int $userId ): QuizDto {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$this->createQuestion( $quizId, $dto );

		return $this->get( $quizId, $userId );
	}

	public function updateQuestion( int $quizId, int $questionId, UpdateQuestionDto $dto, int $userId ): QuizDto {
		$quiz     = $this->findQuizOrFail( $quizId );
		$lesson   = $this->findLessonOrFail( $quiz->lessonId );
		$question = $this->findQuestionOrFail( $questionId );

		if ( $question->quizId !== $quizId ) {
			throw new NotFoundException( 'Question not found.' );
		}

		$this->assertCanManageLessonContent( $userId, $lesson );

		$type          = null !== $dto->type ? $dto->type : $question->type;
		$prompt        = null !== $dto->prompt ? trim( $dto->prompt ) : $question->prompt;
		$options       = null !== $dto->options ? $dto->options : $question->options;
		$correctAnswer = null !== $dto->correctAnswer ? $dto->correctAnswer : $question->correctAnswer;

		if ( QuizQuestion::TYPE_ESSAY === $type ) {
			$options       = array();
			$correctAnswer = '';
		} else {
			$correctAnswer = QuestionAnswerCodec::normalizeForType( $type, $correctAnswer, $options );
		}

		$this->assertValidQuestion( $type, $prompt, $options, $correctAnswer );

		$updated = new QuizQuestion(
			$question->id,
			$quizId,
			$type,
			$prompt,
			$options,
			$correctAnswer,
			$question->sortOrder,
		);

		$this->quizRepository->saveQuestion( $updated );

		return $this->get( $quizId, $userId );
	}

	public function deleteQuestion( int $quizId, int $questionId, int $userId ): QuizDto {
		$quiz     = $this->findQuizOrFail( $quizId );
		$lesson   = $this->findLessonOrFail( $quiz->lessonId );
		$question = $this->findQuestionOrFail( $questionId );

		if ( $question->quizId !== $quizId ) {
			throw new NotFoundException( 'Question not found.' );
		}

		$this->assertCanManageLessonContent( $userId, $lesson );

		$this->quizRepository->deleteQuestion( $questionId );

		return $this->get( $quizId, $userId );
	}

	/**
	 * Instructor / builder dry-run: grade only, never persist an attempt.
	 */
	public function previewGrade( int $quizId, SubmitQuizAttemptDto $dto, int $userId ): QuizGradeDto {
		if ( $userId <= 0 ) {
			throw new ForbiddenException( 'You must be logged in to preview this quiz.' );
		}

		$quiz      = $this->findQuizOrFail( $quizId );
		$lesson    = $this->findLessonOrFail( $quiz->lessonId );
		$questions = $this->quizRepository->findQuestionsByQuizId( $quizId );

		$this->assertCanPreviewGrade( $userId, $lesson );

		if ( array() === $questions ) {
			throw new ValidationException( 'Validation failed.', array( 'quiz' => 'Quiz has no questions.' ) );
		}

		return $this->evaluateAttempt( $questions, $dto->answers, $quiz->passPercent, false, true );
	}

	/**
	 * @param array<string, mixed> $runtimeSettings Optional attempt runtime (e.g. timer attestation).
	 */
	public function submitAttempt( int $quizId, SubmitQuizAttemptDto $dto, int $userId, array $runtimeSettings = array() ): QuizAttemptDto {
		if ( $userId <= 0 ) {
			throw new ForbiddenException( 'You must be logged in to submit a quiz.' );
		}

		$quiz      = $this->findQuizOrFail( $quizId );
		$questions = $this->quizRepository->findQuestionsByQuizId( $quizId );
		$lesson    = $this->findLessonOrFail( $quiz->lessonId );
		$enrolled  = $quiz->courseId > 0 && $this->progressRepository->isUserEnrolled( $userId, $quiz->courseId );

		if ( ! $enrolled ) {
			if ( $this->isCourseEditor( $userId, $quiz ) ) {
				throw new ForbiddenException( 'Preview attempts are not saved. Use quiz preview to try this quiz.' );
			}
			if ( ! $lesson->isPreview ) {
				throw new ForbiddenException( 'You must be enrolled to submit this quiz.' );
			}
		}

		if ( null !== $this->lessonAccessService ) {
			$this->lessonAccessService->assertCanAccessLesson( $userId, $lesson->id );
		}

		if ( array() === $questions ) {
			throw new ValidationException( 'Validation failed.', array( 'quiz' => 'Quiz has no questions.' ) );
		}

		if ( ! empty( $runtimeSettings['questionCompletion'] ) ) {
			$this->assertAnswersComplete( $questions, $dto->answers );
		}

		if ( ! empty( $runtimeSettings['restrictRetakes'] ) ) {
			$used = $this->countUserAttempts( $quizId, $userId );
			$max  = max( 1, (int) ( $runtimeSettings['retriesAllowed'] ?? 0 ) + 1 );
			if ( $used >= $max ) {
				throw new ForbiddenException( 'No quiz retries remain.' );
			}
		}

		if ( null !== $this->quizTimer ) {
			$this->quizTimer->assertWithinLimit( $userId, $quizId, $runtimeSettings );
		}

		// Never ship answer keys to students in the submit payload (UI may show right/wrong only).
		$grade = $this->evaluateAttempt( $questions, $dto->answers, $quiz->passPercent, true, false );

		$attempt = $this->quizRepository->saveAttempt(
			new QuizAttempt(
				0,
				$userId,
				$quizId,
				$grade->scorePercent,
				$grade->passed,
				$dto->answers,
				$this->clock->now(),
			)
		);

		$this->persistEssaySubmissions( $attempt, $questions, $dto->answers, $grade );

		if ( null !== $this->quizTimer ) {
			$this->quizTimer->clear( $userId, $quizId );
		}

		return QuizAttemptDto::fromAttempt( $attempt, null, $grade );
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array{startedAt:int, deadlineAt:int, remainingSeconds:int, limitSeconds:int}|null
	 */
	public function startTimedAttempt( int $quizId, int $userId, array $settings ): ?array {
		if ( $userId <= 0 ) {
			throw new ForbiddenException( 'You must be logged in to start a quiz.' );
		}

		$quiz     = $this->findQuizOrFail( $quizId );
		$lesson   = $this->findLessonOrFail( $quiz->lessonId );
		$enrolled = $quiz->courseId > 0 && $this->progressRepository->isUserEnrolled( $userId, $quiz->courseId );

		if ( ! $enrolled && ! $lesson->isPreview ) {
			throw new ForbiddenException( 'You must be enrolled to start this quiz.' );
		}

		if ( null !== $this->lessonAccessService ) {
			$this->lessonAccessService->assertCanAccessLesson( $userId, $lesson->id );
		}

		if ( null === $this->quizTimer ) {
			return null;
		}

		return $this->quizTimer->start( $userId, $quizId, $settings );
	}

	/**
	 * @return list<QuizAttemptDto>
	 */
	public function getAttempts( int $quizId, int $userId ): array {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$attempts = $this->quizRepository->findAttemptsByQuizId( $quizId );
		$dtos     = array();

		foreach ( $attempts as $attempt ) {
			$name   = $this->userLookup->getDisplayName( $attempt->userId );
			$dtos[] = QuizAttemptDto::fromAttempt( $attempt, $name );
		}

		return $dtos;
	}

	/**
	 * Clear every learner's attempt history for a quiz (admin “reset identification”).
	 * Intentional quiz-wide wipe — confirm in the builder UI before calling.
	 */
	public function resetAttempts( int $quizId, int $userId ): void {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$this->quizRepository->deleteAttemptsByQuizId( $quizId );
	}

	public function hasPassed( int $userId, int $quizId ): bool {
		if ( $userId <= 0 ) {
			return false;
		}

		return $this->quizRepository->hasPassed( $userId, $quizId );
	}

	public function syncQuestionPoints( int $questionId, int $points ): void {
		$this->quizRepository->updateQuestionPoints( $questionId, max( 0, $points ) );
	}

	public function lessonRequiresQuizPass( int $lessonId ): bool {
		foreach ( $this->quizRepository->findAllByLessonId( $lessonId ) as $quiz ) {
			$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id );
			if ( array() !== $questions ) {
				return true;
			}
		}

		return false;
	}

	public function hasPassedLessonQuiz( int $userId, int $lessonId ): bool {
		foreach ( $this->quizRepository->findAllByLessonId( $lessonId ) as $quiz ) {
			$questions = $this->quizRepository->findQuestionsByQuizId( $quiz->id );
			if ( array() === $questions ) {
				continue;
			}
			if ( ! $this->hasPassed( $userId, $quiz->id ) ) {
				return false;
			}
		}

		return true;
	}


	public function syncCourseIdForLesson( int $lessonId, int $courseId ): void {
		foreach ( $this->quizRepository->findAllByLessonId( $lessonId ) as $quiz ) {
			$this->quizRepository->saveQuiz(
				new Quiz(
					$quiz->id,
					$quiz->lessonId,
					$courseId,
					$quiz->title,
					$quiz->passPercent,
					$quiz->sortOrder,
				)
			);
		}
	}

	public function onLessonDeleted( int $lessonId ): void {
		$this->quizRepository->deleteByLessonId( $lessonId );
	}

	public function onLessonTrashed( int $lessonId ): void {
		foreach ( $this->quizRepository->findAllByLessonId( $lessonId ) as $quiz ) {
			$this->trashQuizCascade( $quiz->id );
		}
	}

	/**
	 * Move a quiz and its questions to WordPress trash (restorable).
	 */
	public function trash( int $quizId, int $userId ): void {
		$quiz   = $this->findQuizOrFail( $quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $userId, $lesson );

		$this->trashQuizCascade( $quizId );
	}

	/**
	 * Move a single question to WordPress trash (restorable).
	 */
	public function trashQuestion( int $quizId, int $questionId, int $userId ): void {
		$quiz     = $this->findQuizOrFail( $quizId );
		$lesson   = $this->findLessonOrFail( $quiz->lessonId );
		$question = $this->findQuestionOrFail( $questionId );

		if ( $question->quizId !== $quizId ) {
			throw new NotFoundException( 'Question not found.' );
		}

		$this->assertCanManageLessonContent( $userId, $lesson );
		$this->quizRepository->trashQuestion( $questionId );
	}

	private function trashQuizCascade( int $quizId ): void {
		foreach ( $this->quizRepository->findQuestionsByQuizId( $quizId ) as $question ) {
			$this->quizRepository->trashQuestion( $question->id );
		}
		$this->quizRepository->trashQuiz( $quizId );
	}

	/**
	 * @param list<QuizQuestion> $questions
	 * @param array<int, string> $answers
	 */
	public function gradeAttempt( array $questions, array $answers ): float {
		return $this->evaluateAttempt( $questions, $answers, 0, false )->scorePercent;
	}

	/**
	 * @param list<QuizQuestion>   $questions
	 * @param array<int, string>   $answers
	 * @param bool                 $revealCorrectAnswers When false, correctAnswer is omitted (student submit).
	 */
	public function evaluateAttempt(
		array $questions,
		array $answers,
		int $passPercent,
		bool $persisted,
		bool $revealCorrectAnswers = false,
	): QuizGradeDto {
		$items          = array();
		$correctCount   = 0;
		$gradedCount    = 0;
		$pendingEssay   = false;
		$pointsPossible = 0.0;
		$pointsEarned   = 0.0;

		foreach ( $questions as $question ) {
			$submitted = $answers[ $question->id ] ?? '';
			if ( is_array( $submitted ) ) {
				$submitted = QuestionAnswerCodec::encodeMulti( array_map( 'strval', $submitted ) );
			}
			$given           = (string) $submitted;
			$points          = (float) $question->pointsPossible();
			$pointsPossible += $points;

			if ( QuizQuestion::TYPE_ESSAY === $question->type ) {
				$mode          = EssayGradingMode::normalize( $question->gradingMode );
				$awards        = EssayGradingMode::awardsPointsOnSubmit( $mode );
				$pending       = EssayGradingMode::requiresManualGrade( $mode );
				$earned        = $awards ? $points : 0.0;
				$pointsEarned += $earned;
				if ( $pending ) {
					$pendingEssay = true;
				}

				$items[] = array(
					'id'             => $question->id,
					'type'           => $question->type,
					'pending'        => $pending,
					'correct'        => EssayGradingMode::GRADED_FULL_POINTS === $mode ? true : null,
					'given'          => $given,
					'correctAnswer'  => null,
					'pointsPossible' => $points,
					'pointsEarned'   => $earned,
					'gradingMode'    => $mode,
				);
				continue;
			}

			++$gradedCount;
			$isCorrect     = $this->isAnswerCorrect( $question, $given );
			$earned        = $isCorrect ? $points : 0.0;
			$pointsEarned += $earned;
			if ( $isCorrect ) {
				++$correctCount;
			}

			$correctAnswer = $question->correctAnswer;
			$givenOut      = $given;
			if (
				QuizQuestion::TYPE_MCQ_MULTI === $question->type
				|| QuizQuestion::TYPE_TRUE_FALSE === $question->type
			) {
				$correctAnswer = QuestionAnswerCodec::decodeMulti( $question->correctAnswer );
				$givenOut      = QuestionAnswerCodec::decodeMulti( $given );
			}

			$items[] = array(
				'id'             => $question->id,
				'type'           => $question->type,
				'pending'        => false,
				'correct'        => $isCorrect,
				'given'          => $givenOut,
				'correctAnswer'  => $revealCorrectAnswers ? $correctAnswer : null,
				'pointsPossible' => $points,
				'pointsEarned'   => $earned,
			);
		}

		$score  = $pointsPossible > 0.0 ? round( ( $pointsEarned / $pointsPossible ) * 100, 2 ) : 0.0;
		$passed = $pointsPossible > 0.0 && ( $score + 0.0001 ) >= $passPercent;

		return new QuizGradeDto(
			$score,
			$passed,
			$pendingEssay,
			$gradedCount,
			$correctCount,
			$items,
			$persisted,
			$pointsEarned,
			$pointsPossible,
		);
	}

	public function countUserAttempts( int $quizId, int $userId ): int {
		return $this->quizRepository->countAttemptsByQuizAndUser( $quizId, $userId );
	}

	public function isAnswerCorrect( QuizQuestion $question, string $submitted ): bool {
		if ( QuizQuestion::TYPE_ESSAY === $question->type ) {
			return false;
		}

		if ( QuizQuestion::TYPE_TRUE_FALSE === $question->type ) {
			$raw = trim( $question->correctAnswer );
			if ( in_array( strtolower( $raw ), array( 'true', 'false' ), true ) && ! str_starts_with( $raw, '[' ) ) {
				return strtolower( trim( $submitted ) ) === strtolower( $raw );
			}

			$expected = QuestionAnswerCodec::decodeMulti( $question->correctAnswer );
			$actual   = QuestionAnswerCodec::decodeMulti( $submitted );

			return $expected === $actual && array() !== $expected;
		}

		if ( QuizQuestion::TYPE_MCQ_MULTI === $question->type ) {
			$expected = QuestionAnswerCodec::decodeMulti( $question->correctAnswer );
			$actual   = QuestionAnswerCodec::decodeMulti( $submitted );

			return $expected === $actual && array() !== $expected;
		}

		return trim( $submitted ) === trim( $question->correctAnswer );
	}

	private function createQuestion( int $quizId, CreateQuestionDto $dto ): QuizQuestion {
		$prompt  = trim( $dto->prompt );
		$type    = $dto->type;
		$options = QuizQuestion::TYPE_ESSAY === $type ? array() : $dto->options;
		$correct = QuestionAnswerCodec::normalizeForType( $type, $dto->correctAnswer, $options );

		$this->assertValidQuestion( $type, $prompt, $options, $correct );

		return $this->quizRepository->saveQuestion(
			new QuizQuestion(
				0,
				$quizId,
				$type,
				$prompt,
				$options,
				$correct,
				$this->quizRepository->nextQuestionSortOrder( $quizId ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function createQuestionFromArray( int $quizId, array $data, int $sortOrder ): void {
		$type          = (string) ( $data['type'] ?? QuizQuestion::TYPE_MCQ );
		$prompt        = (string) ( $data['prompt'] ?? '' );
		$options       = isset( $data['options'] ) && is_array( $data['options'] )
			? array_values( array_map( 'strval', $data['options'] ) )
			: array();
		$correctAnswer = QuestionAnswerCodec::normalizeForType(
			$type,
			$data['correct_answer'] ?? $data['correctAnswer'] ?? '',
			$options
		);

		if ( QuizQuestion::TYPE_ESSAY === $type ) {
			$options = array();
		}

		$this->assertValidQuestion( $type, $prompt, $options, $correctAnswer );

		$this->quizRepository->saveQuestion(
			new QuizQuestion(
				0,
				$quizId,
				$type,
				trim( $prompt ),
				$options,
				$correctAnswer,
				$sortOrder,
			)
		);
	}

	/**
	 * @param list<string> $options
	 */
	private function assertValidQuestion( string $type, string $prompt, array $options, string $correctAnswer ): void {
		if ( '' === trim( $prompt ) ) {
			throw new ValidationException( 'Validation failed.', array( 'prompt' => 'Prompt is required.' ) );
		}

		if ( ! in_array( $type, QuizQuestion::types(), true ) ) {
			throw new ValidationException( 'Validation failed.', array( 'type' => 'Invalid question type.' ) );
		}

		if ( QuizQuestion::TYPE_ESSAY === $type ) {
			return;
		}

		$optionCount = count( $options );

		if ( $optionCount < 2 || $optionCount > 6 ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'options' => 'This question must have between 2 and 6 options.' )
			);
		}

		foreach ( $options as $option ) {
			if ( '' === trim( $option ) ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'options' => 'Options cannot be empty.' )
				);
			}
		}

		if ( QuizQuestion::TYPE_MCQ_MULTI === $type || QuizQuestion::TYPE_TRUE_FALSE === $type ) {
			$answers = QuestionAnswerCodec::decodeMulti( $correctAnswer );

			if ( count( $answers ) < 1 ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'correct_answer' => 'Mark at least one option as True.' )
				);
			}

			foreach ( $answers as $answer ) {
				if ( ! in_array( $answer, $options, true ) ) {
					throw new ValidationException(
						'Validation failed.',
						array( 'correct_answer' => 'Each correct answer must match one of the options.' )
					);
				}
			}

			return;
		}

		if ( ! in_array( $correctAnswer, $options, true ) ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'correct_answer' => 'Correct answer must match one of the options.' )
			);
		}
	}

	private function normalizePassPercent( int $passPercent ): int {
		return max( 0, min( 100, $passPercent ) );
	}

	private function createStandaloneHostLesson(): \MintLMS\Domain\Lesson\Lesson {
		$now  = $this->clock->now();
		$slug = 'new-lesson-' . bin2hex( random_bytes( 2 ) );

		$lesson = $this->lessonRepository->save(
			new \MintLMS\Domain\Lesson\Lesson(
				0,
				0,
				0,
				__( 'New Lesson', 'mint-lms' ),
				$slug,
				'',
				null,
				null,
				false,
				null,
				0,
				$now,
				$now,
			)
		);

		\MintLMS\Infrastructure\PostType\PostTypes::markDisposableHostLesson( (int) $lesson->id );

		return $lesson;
	}

	private function isDisposableHostLesson( \MintLMS\Domain\Lesson\Lesson $lesson ): bool {
		return \MintLMS\Infrastructure\PostType\PostTypes::isDisposableHostLesson( (int) $lesson->id );
	}

	private function findQuizOrFail( int $id ): Quiz {
		$quiz = $this->quizRepository->findById( $id );

		if ( null === $quiz ) {
			throw new NotFoundException( 'Quiz not found.' );
		}

		return $quiz;
	}

	private function findQuestionOrFail( int $id ): QuizQuestion {
		$question = $this->quizRepository->findQuestionById( $id );

		if ( null === $question ) {
			throw new NotFoundException( 'Question not found.' );
		}

		return $question;
	}

	private function findLessonOrFail( int $id ): \MintLMS\Domain\Lesson\Lesson {
		$lesson = $this->lessonRepository->findById( $id );

		if ( null === $lesson ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		return $lesson;
	}

	private function assertCanManageLessonContent( int $userId, \MintLMS\Domain\Lesson\Lesson $lesson ): void {
		if ( $lesson->courseId > 0 ) {
			$course = $this->findCourseOrFail( $lesson->courseId );
			if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
				throw new ForbiddenException();
			}

			return;
		}

		if ( ! $this->authorization->canCreateCourse( $userId ) ) {
			throw new ForbiddenException();
		}

		// Standalone host lessons: instructors may only manage their own posts.
		if ( $this->authorization->canViewAllContent( $userId ) ) {
			return;
		}

		if ( ! $this->authorization->ownsPost( $userId, $lesson->id ) ) {
			throw new ForbiddenException();
		}
	}

	private function findCourseOrFail( int $id ): \MintLMS\Domain\Course\Course {
		$course = $this->courseRepository->findById( $id );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		return $course;
	}

	private function assertStudentQuizAccess( int $userId, int $courseId, bool $isPreview, int $lessonId ): void {
		if ( $userId <= 0 ) {
			throw new ForbiddenException( 'You must be logged in to access this quiz.' );
		}

		if ( ! $isPreview && ! $this->progressRepository->isUserEnrolled( $userId, $courseId ) ) {
			throw new ForbiddenException( 'You must be enrolled to access this quiz.' );
		}

		if ( null !== $this->lessonAccessService ) {
			$this->lessonAccessService->assertCanAccessLesson( $userId, $lessonId );
		}
	}

	private function canEditorReviewCourse( int $userId, int $authorId ): bool {
		return $userId > 0 && $this->authorization->canEditCourse( $userId, $authorId );
	}

	private function isCourseEditor( int $userId, Quiz $quiz ): bool {
		if ( $quiz->courseId <= 0 ) {
			return $this->authorization->canCreateCourse( $userId );
		}

		$course = $this->courseRepository->findById( $quiz->courseId );
		if ( null === $course ) {
			return false;
		}

		return $this->canEditorReviewCourse( $userId, $course->authorId );
	}

	private function assertCanPreviewGrade( int $userId, \MintLMS\Domain\Lesson\Lesson $lesson ): void {
		$this->assertCanManageLessonContent( $userId, $lesson );
	}

	/**
	 * @param list<QuizQuestion> $questions
	 * @param array<int, string> $answers
	 */
	private function assertAnswersComplete( array $questions, array $answers ): void {
		foreach ( $questions as $question ) {
			$submitted = $answers[ $question->id ] ?? '';
			if ( is_array( $submitted ) ) {
				if ( array() === $submitted ) {
					throw new ValidationException( 'Validation failed.', array( 'answers' => 'Answer every question before submitting.' ) );
				}
				continue;
			}
			$raw = (string) $submitted;
			if ( QuizQuestion::TYPE_ESSAY === $question->type ) {
				if ( ! EssayAnswerCodec::hasContent( $raw ) ) {
					throw new ValidationException( 'Validation failed.', array( 'answers' => 'Answer every question before submitting.' ) );
				}
				continue;
			}
			if ( '' === trim( $raw ) ) {
				throw new ValidationException( 'Validation failed.', array( 'answers' => 'Answer every question before submitting.' ) );
			}
		}
	}

	/**
	 * @param list<QuizQuestion> $questions
	 * @param array<int, string> $answers
	 */
	private function persistEssaySubmissions( QuizAttempt $attempt, array $questions, array $answers, QuizGradeDto $grade ): void {
		if ( null === $this->essaySubmissionRepository || $attempt->id <= 0 ) {
			return;
		}

		$now = $this->clock->now();
		foreach ( $grade->questions as $item ) {
			if ( ( $item['type'] ?? '' ) !== QuizQuestion::TYPE_ESSAY ) {
				continue;
			}

			$questionId = (int) ( $item['id'] ?? 0 );
			$decoded    = EssayAnswerCodec::decode( (string) ( $answers[ $questionId ] ?? '' ) );
			$pending    = ! empty( $item['pending'] );
			$earned     = (float) ( $item['pointsEarned'] ?? 0 );
			$possible   = (int) ( $item['pointsPossible'] ?? 1 );

			$this->essaySubmissionRepository->save(
				new EssaySubmission(
					0,
					$attempt->id,
					$questionId,
					$attempt->userId,
					$attempt->quizId,
					$pending ? EssaySubmission::STATUS_PENDING : EssaySubmission::STATUS_GRADED,
					max( 0, $possible ),
					$earned,
					$decoded['text'],
					$decoded['attachmentId'] > 0 ? $decoded['attachmentId'] : null,
					null,
					$pending ? null : $now,
					$now,
				)
			);
		}
	}

	/**
	 * Authorize essay file upload; controller persists the media attachment.
	 */
	public function assertCanUploadEssay( int $quizId, int $questionId, int $userId ): void {
		if ( $userId <= 0 ) {
			throw new ForbiddenException( 'You must be logged in to upload.' );
		}

		$quiz     = $this->findQuizOrFail( $quizId );
		$question = $this->findQuestionOrFail( $questionId );
		if ( $question->quizId !== $quizId || QuizQuestion::TYPE_ESSAY !== $question->type ) {
			throw new NotFoundException( 'Question not found.' );
		}
		if ( 'Upload' !== $question->submitMethod ) {
			throw new ValidationException( 'Validation failed.', array( 'upload' => 'This essay does not accept file uploads.' ) );
		}

		$lesson   = $this->findLessonOrFail( $quiz->lessonId );
		$enrolled = $quiz->courseId > 0 && $this->progressRepository->isUserEnrolled( $userId, $quiz->courseId );
		if ( ! $enrolled && ! $lesson->isPreview ) {
			throw new ForbiddenException( 'You must be enrolled to upload.' );
		}
		if ( null !== $this->lessonAccessService ) {
			$this->lessonAccessService->assertCanAccessLesson( $userId, $lesson->id );
		}
	}

	/**
	 * @return array{answer: string, attachmentId: int}
	 */
	public function encodeEssayUploadAnswer( int $attachmentId ): array {
		$attachmentId = max( 0, $attachmentId );

		return array(
			'answer'       => EssayAnswerCodec::encode( '', $attachmentId ),
			'attachmentId' => $attachmentId,
		);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function listEssaySubmissions( int $userId, ?string $status = null, ?int $quizId = null ): array {
		if ( null === $this->essaySubmissionRepository ) {
			return array();
		}

		$authorId = $this->authorization->canViewAllContent( $userId ) ? null : $userId;
		$rows     = $this->essaySubmissionRepository->listForGrading( $status, $quizId, $authorId );

		$out = array();
		foreach ( $rows as $row ) {
			$out[] = $this->serializeEssaySubmission( $row );
		}

		return $out;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function gradeEssaySubmission( int $submissionId, float $pointsAwarded, int $graderId ): array {
		if ( null === $this->essaySubmissionRepository ) {
			throw new NotFoundException( 'Essay submission not found.' );
		}

		$submission = $this->essaySubmissionRepository->findById( $submissionId );
		if ( null === $submission ) {
			throw new NotFoundException( 'Essay submission not found.' );
		}

		$quiz   = $this->findQuizOrFail( $submission->quizId );
		$lesson = $this->findLessonOrFail( $quiz->lessonId );
		$this->assertCanManageLessonContent( $graderId, $lesson );

		$pointsAwarded = max( 0.0, min( (float) $submission->pointsPossible, $pointsAwarded ) );
		$updated       = $this->essaySubmissionRepository->updateGrade(
			$submissionId,
			EssaySubmission::STATUS_GRADED,
			$pointsAwarded,
			$graderId,
			$this->clock->now()
		);

		$this->recalculateAttemptAfterEssayGrade( $updated->attemptId, $quiz->passPercent );

		return $this->serializeEssaySubmission( $updated );
	}

	private function recalculateAttemptAfterEssayGrade( int $attemptId, int $passPercent ): void {
		if ( null === $this->essaySubmissionRepository || $attemptId <= 0 ) {
			return;
		}

		$attempt = $this->quizRepository->findAttemptById( $attemptId );
		if ( null === $attempt ) {
			return;
		}

		$questions = $this->quizRepository->findQuestionsByQuizId( $attempt->quizId );
		$essays    = $this->essaySubmissionRepository->findByAttemptId( $attemptId );
		$byQ       = array();
		foreach ( $essays as $essay ) {
			$byQ[ $essay->questionId ] = $essay;
		}

		$pointsPossible = 0.0;
		$pointsEarned   = 0.0;

		foreach ( $questions as $question ) {
			$points          = (float) $question->pointsPossible();
			$pointsPossible += $points;

			if ( QuizQuestion::TYPE_ESSAY === $question->type ) {
				$essay = $byQ[ $question->id ] ?? null;
				if ( null !== $essay && EssaySubmission::STATUS_GRADED === $essay->status ) {
					$pointsEarned += (float) $essay->pointsAwarded;
				} elseif ( null !== $essay ) {
					$pointsEarned += (float) $essay->pointsAwarded;
				} elseif ( EssayGradingMode::awardsPointsOnSubmit( $question->gradingMode ) ) {
					$pointsEarned += $points;
				}
				continue;
			}

			$given = (string) ( $attempt->answers[ $question->id ] ?? '' );
			if ( $this->isAnswerCorrect( $question, $given ) ) {
				$pointsEarned += $points;
			}
		}

		$score  = $pointsPossible > 0.0 ? round( ( $pointsEarned / $pointsPossible ) * 100, 2 ) : 0.0;
		$passed = $pointsPossible > 0.0 && ( $score + 0.0001 ) >= $passPercent;
		$this->quizRepository->updateAttemptScore( $attemptId, $score, $passed );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function serializeEssaySubmission( EssaySubmission $submission ): array {
		$question = $this->quizRepository->findQuestionById( $submission->questionId );
		$quiz     = $this->quizRepository->findById( $submission->quizId );
		$userName = $this->userLookup->getDisplayName( $submission->userId );

		return array(
			'id'             => $submission->id,
			'attemptId'      => $submission->attemptId,
			'questionId'     => $submission->questionId,
			'userId'         => $submission->userId,
			'userName'       => $userName,
			'quizId'         => $submission->quizId,
			'quizTitle'      => null !== $quiz ? $quiz->title : '',
			'questionPrompt' => null !== $question ? trim( strip_tags( $question->prompt ) ) : '',
			'status'         => $submission->status,
			'pointsPossible' => $submission->pointsPossible,
			'pointsAwarded'  => $submission->pointsAwarded,
			'answerText'     => $submission->answerText,
			'attachmentId'   => $submission->attachmentId,
			'attachmentUrl'  => null,
			'gradedBy'       => $submission->gradedBy,
			'gradedAt'       => null !== $submission->gradedAt ? $submission->gradedAt->format( 'c' ) : null,
			'createdAt'      => $submission->createdAt->format( 'c' ),
		);
	}
}
