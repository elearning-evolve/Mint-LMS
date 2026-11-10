<?php
declare(strict_types=1);

namespace MintLMS\Application\Student;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Course\Dto\CourseStructureDto;
use MintLMS\Application\Course\Dto\CourseStructureLessonDto;
use MintLMS\Application\Course\Dto\CourseStructureSectionDto;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Quiz\Dto\QuizDto;
use MintLMS\Application\Quiz\QuestionAnswerCodec;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Application\Student\Dto\StudentCatalogCourseDto;
use MintLMS\Application\Student\Dto\StudentCourseItemDto;
use MintLMS\Application\Student\Dto\StudentCourseOverviewDto;
use MintLMS\Application\Student\Dto\StudentPlayerContextDto;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseAccessRules;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Drip\DripAccessEvaluator;
use MintLMS\Domain\Enrollment\Enrollment;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Domain\Shared\Clock;

final class StudentExperienceService {

	private const MAX_COURSES = 100;

	public function __construct(
		private CourseRepositoryInterface $courseRepository,
		private SectionRepositoryInterface $sectionRepository,
		private EnrollmentRepositoryInterface $enrollmentRepository,
		private ProgressRepositoryInterface $progressRepository,
		private Clock $clock,
		private ?QuizService $quizService = null,
		private ?AuthorizationInterface $authorization = null,
		private ?LessonRepositoryInterface $lessonRepository = null,
	) {
	}

	/**
	 * @return list<StudentCourseItemDto>
	 */
	public function getDashboardCourses( int $userId ): array {
		if ( $userId <= 0 ) {
			return array();
		}

		$items = $this->buildCourseItems( $userId );

		return array_values(
			array_filter(
				$items,
				static fn( StudentCourseItemDto $item ): bool => ! $item->isComplete
			)
		);
	}

	/**
	 * @return list<StudentCourseItemDto>
	 */
	public function getMyCourses( int $userId, string $filter = 'all' ): array {
		if ( $userId <= 0 ) {
			return array();
		}

		$items = $this->buildCourseItems( $userId );

		return match ( $filter ) {
			'in_progress' => array_values(
				array_filter(
					$items,
					static fn( StudentCourseItemDto $item ): bool => ! $item->isComplete
				)
			),
			'completed' => array_values(
				array_filter(
					$items,
					static fn( StudentCourseItemDto $item ): bool => $item->isComplete
				)
			),
			default => $items,
		};
	}

	/**
	 * @return list<StudentCatalogCourseDto>
	 */
	public function getPublishedCatalogCourses( int $page = 1, int $perPage = 50 ): array {
		$result = $this->courseRepository->list(
			max( 1, $page ),
			max( 1, min( $perPage, self::MAX_COURSES ) ),
			null,
			CourseStatus::Published,
		);

		$courses = array();

		foreach ( $result['courses'] as $course ) {
			$courses[] = new StudentCatalogCourseDto(
				$course->id,
				$course->title,
				$course->description ?? '',
				$course->featuredImageId,
				$course->enrollmentType->value,
			);
		}

		return $courses;
	}

	public function getCourseOverview( int $courseId, int $userId ): StudentCourseOverviewDto {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		if ( ! $this->canViewCourseOnFrontend( $userId, $course->status, $course->authorId ) ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$isEditorPreview = $this->canEditorPreviewCourse( $userId, $course->status, $course->authorId );
		$publicBrowse    = $course->enrollmentType->allowsPublicBrowse();
		$isEnrolled      = ( $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $courseId ) ) || $isEditorPreview;
		$structure       = $this->loadGatedStructure( $courseId, $isEnrolled || $publicBrowse, $userId, $isEditorPreview, $isEnrolled );
		$flat            = $this->flattenLessons( $structure );

		$progressPct        = null;
		$lastLessonId       = null;
		$completedLessonIds = array();
		$lastActivityLabel  = null;

		if ( $isEnrolled ) {
			$summary            = $this->progressRepository->getSummary( $userId, $courseId );
			$progressPct        = null !== $summary ? $summary->pctComplete : 0.0;
			$lastLessonId       = null !== $summary ? $summary->lastLessonId : null;
			$completedLessonIds = $this->progressRepository->getCompletedLessonIds( $userId, $courseId );
			if ( null !== $summary ) {
				$lastActivityLabel = $summary->updatedAt->format( 'M j, Y g:i a' );
			}
		}

		$authorName = '';
		if ( function_exists( 'get_userdata' ) ) {
			$author = get_userdata( $course->authorId );
			if ( $author instanceof \WP_User ) {
				$authorName = (string) $author->display_name;
			}
		}

		return new StudentCourseOverviewDto(
			$course->id,
			$course->title,
			$course->slug,
			$course->description,
			$course->featuredImageId,
			$course->enrollmentType->value,
			$isEnrolled,
			$this->canSelfEnroll( $userId, $course->enrollmentType, $isEnrolled, $course->status ),
			$userId > 0,
			$progressPct,
			$lastLessonId,
			$this->firstLessonId( $flat ),
			$completedLessonIds,
			$structure,
			$course->status->value,
			$authorName,
			$lastActivityLabel,
			$isEditorPreview,
			$this->buildContentOutline( $structure, $userId, $isEditorPreview ),
		);
	}

	/**
	 * Builder S3B: lesson + nested quizzes/questions for instructor preview.
	 *
	 * @return array{title: string, meta: string, quizzes: list<array<string, mixed>>}
	 */
	public function getLessonBuilderPreview( int $courseId, int $lessonId, int $userId ): array {
		if ( $courseId <= 0 ) {
			return $this->getStandaloneLessonBuilderPreview( $lessonId, $userId );
		}

		$course    = $this->requireEditableCourse( $courseId, $userId );
		$structure = $this->loadGatedStructure( $courseId, true, $userId, true );
		$outline   = $this->buildContentOutline( $structure, $userId, true );
		$flat      = $this->flattenLessons( $structure );

		$authorName = '';
		if ( function_exists( 'get_userdata' ) ) {
			$author = get_userdata( $course->authorId );
			if ( $author ) {
				$authorName = (string) $author->display_name;
			}
		}

		foreach ( $outline as $section ) {
			foreach ( $section['lessons'] as $lesson ) {
				if ( (int) $lesson['id'] !== $lessonId ) {
					continue;
				}

				$featuredImageId     = null;
				$nextLessonId        = null;
				$nextLessonTitle     = '';
				$previousLessonId    = null;
				$previousLessonTitle = '';
				$lessonContent       = '';
				foreach ( $flat as $index => $structureLesson ) {
					if ( $structureLesson->id === $lessonId ) {
						$featuredImageId = $structureLesson->featuredImageId;
						$lessonContent   = (string) ( $structureLesson->content ?? '' );
						if ( isset( $flat[ $index + 1 ] ) ) {
							$nextLessonId    = $flat[ $index + 1 ]->id;
							$nextLessonTitle = (string) $flat[ $index + 1 ]->title;
						}
						if ( $index > 0 && isset( $flat[ $index - 1 ] ) ) {
							$previousLessonId    = $flat[ $index - 1 ]->id;
							$previousLessonTitle = (string) $flat[ $index - 1 ]->title;
						}
						break;
					}
				}

				$progressPct = 0.0;
				if ( $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $courseId ) ) {
					$summary     = $this->progressRepository->getSummary( $userId, $courseId );
					$progressPct = null !== $summary ? (float) $summary->pctComplete : 0.0;
				}

				return array(
					'title'               => (string) $lesson['title'],
					'meta'                => (string) $lesson['meta'],
					'quizzes'             => $lesson['quizzes'],
					'content'             => $lessonContent,
					'courseTitle'         => $course->title,
					'sectionTitle'        => (string) ( $section['title'] ?? '' ),
					'featuredImageId'     => $featuredImageId,
					'authorName'          => $authorName,
					'progressPct'         => $progressPct,
					'nextLessonId'        => $nextLessonId,
					'nextLessonTitle'     => $nextLessonTitle,
					'previousLessonId'    => $previousLessonId,
					'previousLessonTitle' => $previousLessonTitle,
					'courseId'            => $courseId,
					'lessonId'            => $lessonId,
				);
			}
		}

		// Lesson may be linked to this course in data but missing from outline (race / stale).
		// Fall back to direct lesson load when the course is editable.
		return $this->getStandaloneLessonBuilderPreview( $lessonId, $userId, $course->title );
	}

	/**
	 * Instructor preview for a lesson that is not (yet) inside a course — LearnDash-style.
	 *
	 * @return array{title: string, meta: string, quizzes: list<array<string, mixed>>}
	 */
	public function getStandaloneLessonBuilderPreview( int $lessonId, int $userId, string $courseTitle = '' ): array {
		$lesson = $this->requirePreviewableLesson( $lessonId, $userId );

		if ( $lesson->courseId > 0 && '' === $courseTitle ) {
			try {
				return $this->getLessonBuilderPreview( $lesson->courseId, $lessonId, $userId );
			} catch ( NotFoundException | ForbiddenException $exception ) {
				// Continue with a direct lesson payload if course shell is unavailable.
			}
		}

		$quizzes = $this->buildQuizOutlineForLesson( $lessonId, $userId, true );
		$quizCount = count( $quizzes );
		$meta      = 0 === $quizCount
			? __( 'No quiz yet', 'mint-lms' )
			: ( 1 === $quizCount
				? __( '1 quiz', 'mint-lms' )
				: sprintf(
					/* translators: %d: quiz count */
					__( '%d quizzes', 'mint-lms' ),
					$quizCount
				) );

		$authorName = '';
		if ( function_exists( 'get_userdata' ) ) {
			$author = get_userdata( $userId );
			if ( $author ) {
				$authorName = (string) $author->display_name;
			}
		}

		return array(
			'title'               => $lesson->title,
			'meta'                => $meta,
			'quizzes'             => $quizzes,
			'content'             => $lesson->content,
			'courseTitle'         => $courseTitle,
			'sectionTitle'        => '',
			'featuredImageId'     => $lesson->featuredImageId,
			'authorName'          => $authorName,
			'progressPct'         => 0.0,
			'nextLessonId'        => null,
			'nextLessonTitle'     => '',
			'previousLessonId'    => null,
			'previousLessonTitle' => '',
			'courseId'            => $lesson->courseId,
			'lessonId'            => $lesson->id,
		);
	}

	/**
	 * Builder S3C: quiz + questions for instructor preview.
	 *
	 * @return array{title: string, meta: string, questions: list<array{id: int, text: string}>, courseTitle: string}
	 */
	public function getQuizBuilderPreview( int $courseId, int $lessonId, int $userId ): array {
		$courseTitle = '';
		if ( $courseId > 0 ) {
			$course      = $this->requireEditableCourse( $courseId, $userId );
			$courseTitle = $course->title;
		} else {
			$this->requirePreviewableLesson( $lessonId, $userId );
		}

		if ( null === $this->quizService ) {
			throw new NotFoundException( 'Quiz not found.' );
		}

		$quiz = $this->quizService->getByLessonId( $lessonId, $userId, false );

		$questions = array();
		if ( null !== $quiz ) {
			foreach ( $quiz->questions as $question ) {
				$text = trim( wp_strip_all_tags( $question->prompt ) );
				$questions[] = array(
					'id'   => $question->id,
					'text' => '' !== $text ? $text : __( 'Untitled question', 'mint-lms' ),
				);
			}
		}

		$count = count( $questions );

		$authorName = '';
		if ( function_exists( 'get_userdata' ) ) {
			$author = get_userdata( $userId );
			if ( $author ) {
				$authorName = (string) $author->display_name;
			}
		}

		return array(
			'title'       => null !== $quiz && '' !== trim( $quiz->title ) ? $quiz->title : __( 'Quiz', 'mint-lms' ),
			'meta'        => 1 === $count
				? __( '1 question', 'mint-lms' )
				: sprintf(
					/* translators: %d: question count */
					__( '%d questions', 'mint-lms' ),
					$count
				),
			'questions'   => $questions,
			'courseTitle' => $courseTitle,
			'authorName'  => $authorName,
			'progressPct' => 0.0,
		);
	}

	/**
	 * Instructor live quiz runner (does not persist student attempts).
	 */
	public function getQuizForBuilderLivePreview( int $quizId, int $lessonId, int $userId ): QuizDto {
		if ( null === $this->quizService ) {
			throw new NotFoundException( 'Quiz not found.' );
		}

		if ( $quizId > 0 ) {
			return $this->quizService->get( $quizId, $userId, false );
		}

		if ( $lessonId <= 0 ) {
			throw new NotFoundException( 'Quiz not found.' );
		}

		$quiz = $this->quizService->getByLessonId( $lessonId, $userId, false );

		if ( null === $quiz ) {
			throw new NotFoundException( 'Quiz not found.' );
		}

		return $quiz;
	}

	/**
	 * Resolve host lesson for quiz preview when only mint_quiz is present.
	 */
	public function resolveLessonIdForQuizPreview( int $quizId, int $userId ): int {
		if ( null === $this->quizService || $quizId <= 0 || $userId <= 0 ) {
			return 0;
		}

		try {
			$quiz = $this->quizService->get( $quizId, $userId, false );

			return (int) $quiz->lessonId;
		} catch ( NotFoundException | ForbiddenException $exception ) {
			return 0;
		}
	}

	/**
	 * Builder S3D: single question focus for instructor preview.
	 *
	 * @return array{
	 *   title: string,
	 *   meta: string,
	 *   type: string,
	 *   options: list<array{text: string, correct: bool}>,
	 *   courseTitle: string
	 * }
	 */
	public function getQuestionBuilderPreview( int $courseId, int $questionId, int $lessonId, int $userId ): array {
		$courseTitle = '';
		if ( $courseId > 0 ) {
			$course      = $this->requireEditableCourse( $courseId, $userId );
			$courseTitle = $course->title;
		} elseif ( $lessonId > 0 ) {
			$this->requirePreviewableLesson( $lessonId, $userId );
		} elseif ( null === $this->authorization || $userId <= 0 || ! $this->authorization->canCreateCourse( $userId ) ) {
			throw new ForbiddenException( 'You cannot preview this content.' );
		}

		if ( null === $this->quizService || $questionId <= 0 ) {
			throw new NotFoundException( 'Question not found.' );
		}

		$found = null;
		$index = 0;
		$total = 0;

		if ( $lessonId > 0 ) {
			$quizDto = $this->quizService->getByLessonId( $lessonId, $userId, false );
			if ( null !== $quizDto ) {
				$total = count( $quizDto->questions );
				foreach ( $quizDto->questions as $qi => $question ) {
					if ( $question->id === $questionId ) {
						$found = $question;
						$index = $qi + 1;
						break;
					}
				}
			}
		}

		if ( null === $found ) {
			if ( $courseId <= 0 ) {
				throw new NotFoundException( 'Question not found.' );
			}

			$outline = $this->buildContentOutline(
				$this->loadGatedStructure( $courseId, true, $userId, true ),
				$userId,
				true
			);

			foreach ( $outline as $section ) {
				foreach ( $section['lessons'] as $lesson ) {
					$quizDto = $this->quizService->getByLessonId( (int) $lesson['id'], $userId, false );
					if ( null === $quizDto ) {
						continue;
					}
					$total = count( $quizDto->questions );
					foreach ( $quizDto->questions as $qi => $question ) {
						if ( $question->id === $questionId ) {
							$found = $question;
							$index = $qi + 1;
							break 3;
						}
					}
				}
			}
		}

		if ( null === $found ) {
			throw new NotFoundException( 'Question not found.' );
		}

		$typeLabel = match ( $found->type ) {
			QuizQuestion::TYPE_MCQ_MULTI => __( 'Multiple select', 'mint-lms' ),
			QuizQuestion::TYPE_TRUE_FALSE => __( 'True / false', 'mint-lms' ),
			QuizQuestion::TYPE_ESSAY => __( 'Essay', 'mint-lms' ),
			default => __( 'Multiple choice', 'mint-lms' ),
		};

		$options = array();
		if ( QuizQuestion::TYPE_ESSAY === $found->type ) {
			$options = array();
		} elseif ( QuizQuestion::TYPE_TRUE_FALSE === $found->type ) {
			$correct = strtolower( (string) $found->correctAnswer );
			foreach ( array( __( 'True', 'mint-lms' ), __( 'False', 'mint-lms' ) ) as $label ) {
				$options[] = array(
					'text'    => $label,
					'correct' => strtolower( $label ) === $correct
						|| ( '1' === $correct && 'true' === strtolower( $label ) )
						|| ( '0' === $correct && 'false' === strtolower( $label ) ),
				);
			}
		} else {
			$corrects = QuizQuestion::TYPE_MCQ_MULTI === $found->type
				? QuestionAnswerCodec::decodeMulti( (string) $found->correctAnswer )
				: array( (string) $found->correctAnswer );
			foreach ( $found->options as $option ) {
				$options[] = array(
					'text'    => $option,
					'correct' => in_array( $option, $corrects, true ),
				);
			}
		}

		$title = trim( wp_strip_all_tags( $found->prompt ) );

		return array(
			'title'       => '' !== $title ? $title : __( 'Untitled question', 'mint-lms' ),
			'meta'        => sprintf(
				/* translators: 1: question type, 2: current index, 3: total questions */
				__( '%1$s · %2$d of %3$d questions', 'mint-lms' ),
				$typeLabel,
				max( 1, $index ),
				max( 1, $total )
			),
			'type'        => $found->type,
			'options'     => $options,
			'courseTitle' => $courseTitle,
		);
	}

	private function requireEditableCourse( int $courseId, int $userId ): Course {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		if ( ! $this->canEditorPreviewCourse( $userId, $course->status, $course->authorId ) ) {
			throw new ForbiddenException( 'You cannot preview this course.' );
		}

		return $course;
	}

	private function requirePreviewableLesson( int $lessonId, int $userId ): Lesson {
		if ( null === $this->lessonRepository ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		$lesson = $this->lessonRepository->findById( $lessonId );
		if ( null === $lesson ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		if ( $lesson->courseId > 0 ) {
			$this->requireEditableCourse( $lesson->courseId, $userId );

			return $lesson;
		}

		if ( $userId <= 0 || null === $this->authorization || ! $this->authorization->canCreateCourse( $userId ) ) {
			throw new ForbiddenException( 'You cannot preview this lesson.' );
		}

		return $lesson;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function buildQuizOutlineForLesson( int $lessonId, int $userId, bool $isEditorPreview ): array {
		$quizzes = array();

		if ( null === $this->quizService || $userId <= 0 || $lessonId <= 0 ) {
			return $quizzes;
		}

		try {
			$quizDto = $isEditorPreview
				? $this->quizService->getByLessonId( $lessonId, $userId, false )
				: $this->quizService->getByLessonId( $lessonId, $userId, true, true );
		} catch ( ForbiddenException | NotFoundException $exception ) {
			$quizDto = null;
		}

		if ( null === $quizDto ) {
			return $quizzes;
		}

		if ( ! $isEditorPreview && array() === $quizDto->questions ) {
			return $quizzes;
		}

		$questionCount = count( $quizDto->questions );
		$questions     = array();

		foreach ( $quizDto->questions as $question ) {
			$text = trim( strip_tags( $question->prompt ) );
			if ( '' === $text ) {
				$text = 'Untitled question';
			}
			$questions[] = array(
				'id'   => $question->id,
				'text' => $text,
			);
		}

		$quizzes[] = array(
			'id'        => $quizDto->id,
			'title'     => '' !== trim( $quizDto->title ) ? $quizDto->title : 'Quiz',
			'meta'      => 1 === $questionCount
				? '1 question'
				: $questionCount . ' questions',
			'questions' => $questions,
		);

		return $quizzes;
	}

	public function getPlayerContext( int $courseId, int $lessonId, int $userId ): StudentPlayerContextDto {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		if ( ! $this->canViewCourseOnFrontend( $userId, $course->status, $course->authorId ) ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$isEditorPreview = $this->canEditorPreviewCourse( $userId, $course->status, $course->authorId );
		$publicBrowse    = $course->enrollmentType->allowsPublicBrowse();
		$isEnrolled      = ( $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $courseId ) ) || $isEditorPreview;

		if ( $isEnrolled && ! $isEditorPreview ) {
			$this->assertCourseAccessWindow( $course, $userId );
		}

		$structure  = $this->loadGatedStructure( $courseId, $isEnrolled || $publicBrowse, $userId, $isEditorPreview, $isEnrolled );
		$flat       = $this->flattenLessons( $structure );
		$current    = $this->findLessonInStructure( $structure, $lessonId );

		if ( null === $current ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		if ( ! $isEnrolled && ! $current->isPreview && ! $publicBrowse ) {
			throw new ForbiddenException( 'Enroll in this course to access this lesson.' );
		}

		if ( $current->isDripLocked ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain-text drip copy; stripped below before reaching clients.
			$message = preg_replace(
				'/[\r\n\t\x00-\x08\x0B\x0C\x0E-\x1F<>]/',
				'',
				(string) ( $current->dripMessage ?? '' )
			);
			throw new ForbiddenException(
				'' !== $message ? $message : 'This lesson is not available yet.'
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( ! $this->lessonHasAccessibleContent( $current, $isEnrolled || $publicBrowse ) ) {
			throw new ForbiddenException( 'This lesson is not available.' );
		}

		$progressPct        = 0.0;
		$isCourseComplete   = false;
		$completedLessonIds = array();
		$isCurrentComplete  = false;

		if ( $isEnrolled ) {
			$summary = $this->progressRepository->getSummary( $userId, $courseId );

			if ( null !== $summary ) {
				$progressPct      = $summary->pctComplete;
				$isCourseComplete = $summary->isCourseComplete();
			}

			$completedLessonIds = $this->progressRepository->getCompletedLessonIds( $userId, $courseId );
			$isCurrentComplete  = in_array( $lessonId, $completedLessonIds, true );
		}

		$index = $this->lessonIndex( $flat, $lessonId );

		$quiz           = null;
		$quizRequired   = false;
		$hasPassedQuiz  = true;

		if ( null !== $this->quizService && $userId > 0 ) {
			$quizDto = $this->quizService->getByLessonId( $lessonId, $userId, true, $isEditorPreview );

			if ( null !== $quizDto && array() !== $quizDto->questions ) {
				$quiz = $quizDto;
				if ( $isEditorPreview ) {
					// Review mode: show the live quiz, but don't block navigation / completion.
					$quizRequired  = false;
					$hasPassedQuiz = false;
				} else {
					$quizRequired  = true;
					$hasPassedQuiz = $this->quizService->hasPassed( $userId, $quizDto->id );
				}
			}
		}

		$reallyEnrolled = $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $courseId );

		return new StudentPlayerContextDto(
			$course->id,
			$course->title,
			$progressPct,
			$isEnrolled,
			$isCourseComplete,
			$structure,
			$current,
			$index > 0 ? $flat[ $index - 1 ]->id : null,
			$index >= 0 && $index < count( $flat ) - 1 ? $flat[ $index + 1 ]->id : null,
			$completedLessonIds,
			$isCurrentComplete,
			$quiz,
			$quizRequired,
			$hasPassedQuiz,
			$course->settings->studentComplete && $reallyEnrolled && ! $isEditorPreview,
			$isEditorPreview,
		);
	}

	/**
	 * @return list<StudentCourseItemDto>
	 */
	private function buildCourseItems( int $userId ): array {
		$result = $this->enrollmentRepository->listByUser(
			$userId,
			1,
			self::MAX_COURSES,
			EnrollmentStatus::Active,
		);

		$items = array();

		foreach ( $result['enrollments'] as $enrollment ) {
			$item = $this->buildCourseItemFromEnrollment( $enrollment, $userId );

			if ( null !== $item ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	private function buildCourseItemFromEnrollment( Enrollment $enrollment, int $userId ): ?StudentCourseItemDto {
		$course = $this->courseRepository->findById( $enrollment->courseId );

		if ( null === $course || ! $this->isCourseVisibleInStudentLibrary( $course->status ) ) {
			return null;
		}

		$summary      = $this->progressRepository->getSummary( $userId, $course->id );
		$progressPct  = null !== $summary ? $summary->pctComplete : 0.0;
		$lastLessonId = null !== $summary ? $summary->lastLessonId : null;
		$isComplete   = null !== $summary && $summary->isCourseComplete();
		$structure    = $this->loadGatedStructure( $course->id, true, $userId );
		$flat         = $this->flattenLessons( $structure );
		$completedIds = $this->progressRepository->getCompletedLessonIds( $userId, $course->id );

		return new StudentCourseItemDto(
			$course->id,
			$course->title,
			$course->slug,
			$progressPct,
			$lastLessonId,
			$this->firstAccessibleLessonId( $flat ),
			$isComplete,
			$course->featuredImageId,
			$course->enrollmentType->value,
			count( $flat ),
			count( $completedIds ),
		);
	}

	private function canSelfEnroll( int $userId, EnrollmentType $enrollmentType, bool $isEnrolled, CourseStatus $status ): bool {
		if ( CourseStatus::Published !== $status || $isEnrolled || $userId <= 0 ) {
			return false;
		}

		return $enrollmentType->allowsSelfEnroll();
	}

	private function assertCourseAccessWindow( Course $course, int $userId ): void {
		$now = $this->clock->now();

		if ( CourseAccessRules::isBeforeAccessStart( $course->settings, $now ) ) {
			throw new ForbiddenException( 'This course has not opened yet.' );
		}

		if ( CourseAccessRules::isAfterAccessEnd( $course->settings, $now ) ) {
			throw new ForbiddenException( 'This course is no longer available.' );
		}

		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $course->id );
		if (
			null !== $enrollment
			&& EnrollmentStatus::Active === $enrollment->status
			&& CourseAccessRules::isEnrollmentExpired( $enrollment->expiresAt, $now )
		) {
			throw new ForbiddenException( 'Your access to this course has expired.' );
		}
	}

	private function loadGatedStructure( int $courseId, bool $canBrowseContent, int $userId, bool $skipDrip = false, ?bool $isReallyEnrolled = null ): CourseStructureDto {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$reallyEnrolled = null !== $isReallyEnrolled ? $isReallyEnrolled : $canBrowseContent;
		$enrolledAt     = $this->resolveEnrollmentDate( $userId, $courseId, $reallyEnrolled );
		$rows           = $this->sectionRepository->loadStructureRows( $courseId );

		$orderedLessonIds = array();
		foreach ( $rows as $row ) {
			$lesson = $row['lesson'];
			if ( null !== $lesson ) {
				$orderedLessonIds[] = $lesson->id;
			}
		}

		$completedLessonIds = ( $reallyEnrolled && $userId > 0 )
			? $this->progressRepository->getCompletedLessonIds( $userId, $courseId )
			: array();

		/** @var array<int, CourseStructureSectionDto> $sectionsById */
		$sectionsById = array();

		foreach ( $rows as $row ) {
			$section = $row['section'];
			$lesson  = $row['lesson'];

			if ( ! isset( $sectionsById[ $section->id ] ) ) {
				$sectionsById[ $section->id ] = new CourseStructureSectionDto(
					$section->id,
					$section->title,
					$section->sortOrder,
					$section->createdAt->format( 'c' ),
					array(),
				);
			}

			if ( null === $lesson ) {
				continue;
			}

			$existing  = $sectionsById[ $section->id ];
			$lessons   = $existing->lessons;
			$lessons[] = $this->gateLesson(
				$lesson,
				$canBrowseContent,
				$enrolledAt,
				$skipDrip,
				$course,
				$orderedLessonIds,
				$completedLessonIds,
				$reallyEnrolled,
			);

			$sectionsById[ $section->id ] = new CourseStructureSectionDto(
				$existing->id,
				$existing->title,
				$existing->sortOrder,
				$existing->createdAt,
				$lessons,
			);
		}

		$sections = array_values( $sectionsById );

		usort(
			$sections,
			static function ( CourseStructureSectionDto $a, CourseStructureSectionDto $b ): int {
				if ( 0 === $a->id && 0 !== $b->id ) {
					return -1;
				}
				if ( 0 !== $a->id && 0 === $b->id ) {
					return 1;
				}

				return $a->sortOrder <=> $b->sortOrder;
			}
		);

		foreach ( $sections as $index => $section ) {
			$lessons = $section->lessons;
			usort(
				$lessons,
				static fn( CourseStructureLessonDto $a, CourseStructureLessonDto $b ): int => $a->sortOrder <=> $b->sortOrder
			);
			$sections[ $index ] = new CourseStructureSectionDto(
				$section->id,
				$section->title,
				$section->sortOrder,
				$section->createdAt,
				$lessons,
			);
		}

		return new CourseStructureDto(
			$course->id,
			$course->title,
			$course->slug,
			$course->status->value,
			$sections,
		);
	}

	/**
	 * @param list<int> $orderedLessonIds
	 * @param list<int> $completedLessonIds
	 */
	private function gateLesson(
		Lesson $lesson,
		bool $canBrowseContent,
		?\DateTimeImmutable $enrolledAt,
		bool $skipDrip = false,
		?Course $course = null,
		array $orderedLessonIds = array(),
		array $completedLessonIds = array(),
		bool $reallyEnrolled = false,
	): CourseStructureLessonDto {
		$dripState = $skipDrip || ! $reallyEnrolled
			? array(
				'locked'  => false,
				'message' => null,
			)
			: $this->resolveDripState( $lesson, $reallyEnrolled, $enrolledAt );

		$progressionLocked = false;
		$progressionMessage = null;
		if ( ! $skipDrip && $reallyEnrolled && null !== $course ) {
			$progressionLocked = CourseAccessRules::isLessonBlockedByProgression(
				$course->settings,
				$lesson->id,
				$orderedLessonIds,
				$completedLessonIds
			);
			if ( $progressionLocked ) {
				$progressionMessage = 'Finish the previous lesson first';
			}
		}

		$locked    = $dripState['locked'] || $progressionLocked;
		$lockMsg   = $dripState['locked'] ? $dripState['message'] : $progressionMessage;
		$canAccess = ( ( $canBrowseContent || $lesson->isPreview ) && ! $locked );

		return new CourseStructureLessonDto(
			$lesson->id,
			$lesson->sectionId,
			$lesson->title,
			$lesson->slug,
			$canAccess ? $lesson->content : '',
			$canAccess ? $lesson->videoUrl : null,
			$canAccess ? $lesson->attachmentId : null,
			$lesson->isPreview,
			$lesson->availableAfterDays,
			$locked,
			$lockMsg,
			$lesson->sortOrder,
			$lesson->createdAt->format( 'c' ),
			$lesson->updatedAt->format( 'c' ),
			$canAccess ? $lesson->featuredImageId : null,
			'',
		);
	}

	/**
	 * @return array{locked: bool, message: ?string}
	 */
	private function resolveDripState( \MintLMS\Domain\Lesson\Lesson $lesson, bool $isEnrolled, ?\DateTimeImmutable $enrolledAt ): array {
		$now = $this->clock->now();

		if ( ! DripAccessEvaluator::isLocked( $lesson->availableAfterDays, $enrolledAt, $now, $isEnrolled ) ) {
			return array(
				'locked'  => false,
				'message' => null,
			);
		}

		$daysRemaining = DripAccessEvaluator::daysUntilAvailable( $lesson->availableAfterDays, $enrolledAt, $now, $isEnrolled );
		$days          = $daysRemaining ?? 1;

		return array(
			'locked'  => true,
			'message' => 1 === $days
				? sprintf( 'Available in %d day', $days )
				: sprintf( 'Available in %d days', $days ),
		);
	}

	/**
	 * Nested Section → Lesson → Quiz → Question outline for the S3 course overview.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function buildContentOutline( CourseStructureDto $structure, int $userId, bool $isEditorPreview ): array {
		$outline = array();

		foreach ( $structure->sections as $section ) {
			$lessons = array();

			foreach ( $section->lessons as $lesson ) {
				$quizzes = array();

				if ( null !== $this->quizService && $userId > 0 ) {
					try {
						$quizDto = $this->quizService->getByLessonId( $lesson->id, $userId, true, $isEditorPreview );
					} catch ( ForbiddenException | NotFoundException $exception ) {
						$quizDto = null;
					}

					if ( null !== $quizDto && ( $isEditorPreview || array() !== $quizDto->questions ) ) {
						$questionCount = count( $quizDto->questions );
						$questions     = array();

						foreach ( $quizDto->questions as $question ) {
							$text = trim( strip_tags( $question->prompt ) );
							if ( '' === $text ) {
								$text = 'Untitled question';
							}
							$questions[] = array(
								'id'   => $question->id,
								'text' => $text,
							);
						}

						$quizzes[] = array(
							'id'        => $quizDto->id,
							'title'     => '' !== trim( $quizDto->title ) ? $quizDto->title : 'Quiz',
							'meta'      => 1 === $questionCount
								? '1 question'
								: $questionCount . ' questions',
							'questions' => $questions,
						);
					}
				}

				$quizCount = count( $quizzes );
				$metaParts = array();
				if ( $quizCount > 0 ) {
					$metaParts[] = 1 === $quizCount ? '1 quiz' : $quizCount . ' quizzes';
				}

				$accessible = $isEditorPreview || ! $lesson->isDripLocked;

				$lessons[] = array(
					'id'         => $lesson->id,
					'title'      => $lesson->title,
					'meta'       => array() === $metaParts ? 'No quiz yet' : implode( ' · ', $metaParts ),
					'accessible' => $accessible,
					'quizzes'    => $quizzes,
				);
			}

			$lessonCount = count( $lessons );
			$outline[]   = array(
				'id'      => $section->id,
				'title'   => $section->title,
				'meta'    => 1 === $lessonCount ? '1 lesson' : $lessonCount . ' lessons',
				'lessons' => $lessons,
			);
		}

		return $outline;
	}

	private function canEditorPreviewCourse( int $userId, CourseStatus $status, int $authorId ): bool {
		if ( $userId <= 0 || null === $this->authorization || CourseStatus::Trashed === $status ) {
			return false;
		}

		return $this->authorization->canEditCourse( $userId, $authorId );
	}

	private function canViewCourseOnFrontend( int $userId, CourseStatus $status, int $authorId ): bool {
		if ( CourseStatus::Published === $status || CourseStatus::Archived === $status ) {
			return true;
		}

		return $this->canEditorPreviewCourse( $userId, $status, $authorId );
	}

	private function isCourseVisibleInStudentLibrary( CourseStatus $status ): bool {
		return CourseStatus::Published === $status || CourseStatus::Archived === $status;
	}

	private function resolveEnrollmentDate( int $userId, int $courseId, bool $isEnrolled ): ?\DateTimeImmutable {
		if ( ! $isEnrolled || $userId <= 0 ) {
			return null;
		}

		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $courseId );

		return $enrollment?->enrolledAt;
	}

	private function lessonHasAccessibleContent( CourseStructureLessonDto $lesson, bool $isEnrolled ): bool {
		if ( $lesson->isDripLocked ) {
			return false;
		}

		if ( $isEnrolled || $lesson->isPreview ) {
			return true;
		}

		return false;
	}

	/**
	 * @param list<CourseStructureLessonDto> $lessons
	 */
	private function firstLessonId( array $lessons ): ?int {
		if ( array() === $lessons ) {
			return null;
		}

		return $lessons[0]->id;
	}

	/**
	 * @param list<CourseStructureLessonDto> $lessons
	 */
	private function firstAccessibleLessonId( array $lessons ): ?int {
		foreach ( $lessons as $lesson ) {
			if ( ! $lesson->isDripLocked && ( '' !== $lesson->content || null !== $lesson->videoUrl ) ) {
				return $lesson->id;
			}
		}

		return $this->firstLessonId( $lessons );
	}

	/**
	 * @return list<CourseStructureLessonDto>
	 */
	private function flattenLessons( CourseStructureDto $structure ): array {
		$lessons = array();

		foreach ( $structure->sections as $section ) {
			foreach ( $section->lessons as $lesson ) {
				$lessons[] = $lesson;
			}
		}

		return $lessons;
	}

	private function findLessonInStructure( CourseStructureDto $structure, int $lessonId ): ?CourseStructureLessonDto {
		foreach ( $structure->sections as $section ) {
			foreach ( $section->lessons as $lesson ) {
				if ( $lesson->id === $lessonId ) {
					return $lesson;
				}
			}
		}

		return null;
	}

	/**
	 * @param list<CourseStructureLessonDto> $lessons
	 */
	private function lessonIndex( array $lessons, int $lessonId ): int {
		foreach ( $lessons as $index => $lesson ) {
			if ( $lesson->id === $lessonId ) {
				return $index;
			}
		}

		return -1;
	}
}
