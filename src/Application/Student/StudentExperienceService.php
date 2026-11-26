<?php
declare(strict_types=1);

namespace MintLMS\Application\Student;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\QuizStudentQueryInterface;
use MintLMS\Application\Course\Dto\CourseStructureDto;
use MintLMS\Application\Course\Dto\CourseStructureLessonDto;
use MintLMS\Application\Course\Dto\CourseStructureSectionDto;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Quiz\Dto\QuizDto;
use MintLMS\Application\Quiz\QuestionAnswerCodec;
use MintLMS\Application\Student\Dto\StudentCatalogCourseDto;
use MintLMS\Application\Student\Dto\StudentCourseItemDto;
use MintLMS\Application\Student\Dto\StudentCourseOverviewDto;
use MintLMS\Application\Student\Dto\StudentPlayerContextDto;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseAccessRules;
use MintLMS\Domain\Course\CourseLessonSequence;
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
		private ?QuizStudentQueryInterface $quizService = null,
		private ?AuthorizationInterface $authorization = null,
		private ?LessonRepositoryInterface $lessonRepository = null,
	) {
	}

	/**
	 * @return list<StudentCourseItemDto>
	 */
	/**
	 * Course to open when the player URL has no course id.
	 * Only when the learner has exactly one visible enrollment, so we never
	 * guess between courses.
	 */
	public function resolveEnrolledCourseId( int $userId ): int {
		if ( $userId <= 0 ) {
			return 0;
		}

		$result    = $this->enrollmentRepository->listByUser( $userId, 1, self::MAX_COURSES, null );
		$courseIds = array();

		foreach ( $result['enrollments'] as $enrollment ) {
			if ( EnrollmentStatus::Cancelled === $enrollment->status ) {
				continue;
			}

			$course = $this->courseRepository->findById( $enrollment->courseId );
			if ( null === $course || ! $this->isCourseVisibleInStudentLibrary( $course->status ) ) {
				continue;
			}

			$courseIds[ $course->id ] = $course->id;
		}

		if ( 1 !== count( $courseIds ) ) {
			return 0;
		}

		return (int) array_key_first( $courseIds );
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

		$canEdit        = $this->canEditorPreviewCourse( $userId, $course->status, $course->authorId );
		$publicBrowse   = $course->enrollmentType->allowsPublicBrowse();
		$isEnrolled     = $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $courseId );
		$studentPreview = $canEdit && ! $isEnrolled;
		$canBrowse      = $isEnrolled || $studentPreview || $publicBrowse;
		$showCurriculum = $canBrowse || $course->settings->lessonListVisibleToGuests();
		if ( $isEnrolled ) {
			$structure = $this->loadGatedStructure( $courseId, true, $userId, false, true, false );
		} elseif ( $studentPreview ) {
			$structure = $this->loadGatedStructure( $courseId, true, $userId, false, false, true );
		} else {
			$structure = $this->loadGatedStructure( $courseId, $canBrowse, $userId, false, false, false );
		}
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
			$studentPreview,
			$showCurriculum ? $this->buildContentOutline( $structure, $userId, false, $canBrowse ) : array(),
			$showCurriculum,
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

		$course = $this->courseRepository->findById( $courseId );
		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$isEditorPreview = $this->canEditorPreviewCourse( $userId, $course->status, $course->authorId );
		if ( ! $isEditorPreview ) {
			$this->getPlayerContext( $courseId, $lessonId, $userId );
		}

		$structure = $this->loadGatedStructure( $courseId, true, $userId, $isEditorPreview );
		$outline   = $this->buildContentOutline( $structure, $userId, $isEditorPreview );
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

		$quizzes   = $this->buildQuizOutlineForLesson( $lessonId, $userId, true );
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
				$questions[] = array(
					'id'   => $question->id,
					'text' => \MintLMS\Infrastructure\PostType\PostTypes::questionContentsTitle( $question->id, $question->prompt ),
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

	public function isEditorPreviewing( int $courseId, int $userId ): bool {
		if ( $userId <= 0 || null === $this->authorization ) {
			return false;
		}

		if ( $courseId <= 0 ) {
			return $this->authorization->canCreateCourse( $userId );
		}

		$course = $this->courseRepository->findById( $courseId );
		if ( null === $course ) {
			return false;
		}

		return $this->canEditorPreviewCourse( $userId, $course->status, $course->authorId );
	}

	public function isUserEnrolledInCourse( int $userId, int $courseId ): bool {
		return $userId > 0 && $courseId > 0 && $this->progressRepository->isUserEnrolled( $userId, $courseId );
	}

	public function hasPassedQuiz( int $userId, int $quizId ): bool {
		return $userId > 0 && $quizId > 0 && null !== $this->quizService && $this->quizService->hasPassed( $userId, $quizId );
	}

	public function countQuizAttempts( int $userId, int $quizId ): int {
		if ( $userId <= 0 || $quizId <= 0 || null === $this->quizService ) {
			return 0;
		}

		return $this->quizService->countUserAttempts( $quizId, $userId );
	}

	/**
	 * Instructor live quiz runner (does not persist student attempts).
	 */
	public function getQuizForBuilderLivePreview( int $quizId, int $lessonId, int $userId ): QuizDto {
		if ( null === $this->quizService ) {
			throw new NotFoundException( 'Quiz not found.' );
		}

		if ( $quizId > 0 ) {
			try {
				return $this->quizService->get( $quizId, $userId, false );
			} catch ( ForbiddenException $exception ) {
				return $this->quizService->get( $quizId, $userId, true );
			}
		}

		if ( $lessonId <= 0 ) {
			throw new NotFoundException( 'Quiz not found.' );
		}

		try {
			$quiz = $this->quizService->getByLessonId( $lessonId, $userId, false );
		} catch ( ForbiddenException $exception ) {
			$quiz = $this->quizService->getByLessonId( $lessonId, $userId, true );
		}

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
			foreach ( $this->quizService->listByLessonId( $lessonId, $userId, false ) as $quizDto ) {
				$total = count( $quizDto->questions );
				foreach ( $quizDto->questions as $qi => $question ) {
					if ( $question->id === $questionId ) {
						$found = $question;
						$index = $qi + 1;
						break 2;
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
					foreach ( $this->quizService->listByLessonId( (int) $lesson['id'], $userId, false ) as $quizDto ) {
						$total = count( $quizDto->questions );
						foreach ( $quizDto->questions as $qi => $question ) {
							if ( $question->id === $questionId ) {
								$found = $question;
								$index = $qi + 1;
								break 4;
							}
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
			$trueSet = QuestionAnswerCodec::decodeMulti( (string) $found->correctAnswer );
			$labels  = count( $found->options ) >= 2
				? $found->options
				: array( __( 'True', 'mint-lms' ), __( 'False', 'mint-lms' ) );
			foreach ( $labels as $label ) {
				$options[] = array(
					'text'    => (string) $label,
					'correct' => in_array( (string) $label, $trueSet, true ),
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
		return $this->quizzesOutlineForLesson( $lessonId, $userId, $isEditorPreview );
	}

	public function getPlayerContext( int $courseId, int $lessonId, int $userId, int $preferredQuizId = 0 ): StudentPlayerContextDto {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		if ( ! $this->canViewCourseOnFrontend( $userId, $course->status, $course->authorId ) ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$canEdit        = $this->canEditorPreviewCourse( $userId, $course->status, $course->authorId );
		$publicBrowse   = $course->enrollmentType->allowsPublicBrowse();
		$enrolled       = $userId > 0 && $this->progressRepository->isUserEnrolled( $userId, $courseId );
		// Enrolled staff learn for real. Unenrolled editors get a student preview:
		// published lessons and the same locks, with nothing saved. Builder Preview
		// (mint_preview) is a separate page and still shows drafts with locks off.
		$studentPreview = $canEdit && ! $enrolled;

		if ( $enrolled || $studentPreview ) {
			$this->assertCourseAccessWindow( $course, $userId );
		}

		$structure = $this->loadGatedStructure(
			$courseId,
			$enrolled || $studentPreview || $publicBrowse,
			$userId,
			false,
			$enrolled,
			$studentPreview
		);
		$flat      = $this->flattenLessons( $structure );
		$current   = $this->findLessonInStructure( $structure, $lessonId );

		if ( null === $current ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		if ( ! $enrolled && ! $studentPreview && ! $current->isPreview && ! $publicBrowse ) {
			$exception = new ForbiddenException( 'Enroll in this course to access this lesson.' );
			$exception->forReason( ForbiddenException::REASON_ENROLLMENT );
			throw $exception;
		}

		if ( $current->isDripLocked ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain-text drip/progression copy; stripped below before reaching clients.
			$message   = preg_replace(
				'/[\r\n\t\x00-\x08\x0B\x0C\x0E-\x1F<>]/',
				'',
				(string) ( $current->dripMessage ?? '' )
			);
			$reason    = is_string( $current->lockReason ) && '' !== $current->lockReason
				? $current->lockReason
				: ForbiddenException::REASON_DRIP;
			$exception = new ForbiddenException( '' !== $message ? $message : 'This lesson is not available yet.' );
			$exception->forReason( $reason );
			throw $exception;
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( ! $this->lessonHasAccessibleContent( $current, $enrolled || $studentPreview || $publicBrowse ) ) {
			$exception = new ForbiddenException( 'This lesson is not available.' );
			$exception->forReason( ForbiddenException::REASON_UNAVAILABLE );
			throw $exception;
		}

		$progressPct        = 0.0;
		$isCourseComplete   = false;
		$completedLessonIds = array();
		$isCurrentComplete  = false;

		if ( $enrolled ) {
			$summary = $this->progressRepository->getSummary( $userId, $courseId );

			if ( null !== $summary ) {
				$progressPct      = $summary->pctComplete;
				$isCourseComplete = $summary->isCourseComplete();
			}

			$completedLessonIds = $this->progressRepository->getCompletedLessonIds( $userId, $courseId );
			$isCurrentComplete  = in_array( $lessonId, $completedLessonIds, true );
		}

		$index = $this->lessonIndex( $flat, $lessonId );

		$quiz             = null;
		$quizRequired     = false;
		$hasPassedQuiz    = true;
		$quizAttemptsUsed = 0;

		if ( null !== $this->quizService && $userId > 0 ) {
			// Same published quizzes as a student. Builder preview passes editorReview separately.
			$quizDtos      = $this->quizService->listByLessonId( $lessonId, $userId, true, false );
			$withQuestions = array();
			foreach ( $quizDtos as $quizDto ) {
				if ( array() !== $quizDto->questions ) {
					$withQuestions[] = $quizDto;
				}
			}

			if ( array() !== $withQuestions ) {
				$selected         = $this->selectPlayerQuiz( $withQuestions, $preferredQuizId, $userId, false );
				$quiz             = $selected;
				$quizRequired     = true;
				$hasPassedQuiz    = $this->quizService->hasPassedLessonQuiz( $userId, $lessonId );
				$quizAttemptsUsed = $this->quizService->countUserAttempts( $selected->id, $userId );
			}
		}

		return new StudentPlayerContextDto(
			$course->id,
			$course->title,
			$progressPct,
			$enrolled,
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
			$course->settings->studentComplete && $enrolled,
			false,
			$quizAttemptsUsed,
			$studentPreview,
		);
	}

	/**
	 * Prefer mint_quiz when it belongs to the lesson; else first incomplete; else first.
	 *
	 * @param list<QuizDto> $withQuestions
	 */
	private function selectPlayerQuiz(
		array $withQuestions,
		int $preferredQuizId,
		int $userId,
		bool $isEditorPreview,
	): QuizDto {
		if ( $preferredQuizId > 0 ) {
			foreach ( $withQuestions as $quizDto ) {
				if ( $quizDto->id === $preferredQuizId ) {
					return $quizDto;
				}
			}
		}

		if ( ! $isEditorPreview && null !== $this->quizService ) {
			foreach ( $withQuestions as $quizDto ) {
				if ( ! $this->quizService->hasPassed( $userId, $quizDto->id ) ) {
					return $quizDto;
				}
			}
		}

		return $withQuestions[0];
	}

	/**
	 * @return list<StudentCourseItemDto>
	 */
	private function buildCourseItems( int $userId ): array {
		$result = $this->enrollmentRepository->listByUser(
			$userId,
			1,
			self::MAX_COURSES,
			null,
		);

		$items = array();

		foreach ( $result['enrollments'] as $enrollment ) {
			if ( EnrollmentStatus::Cancelled === $enrollment->status ) {
				continue;
			}

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
		$structure    = $this->loadGatedStructure( $course->id, true, $userId, false, true );
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
			$exception = new ForbiddenException( 'This course has not opened yet.' );
			$exception->forReason( ForbiddenException::REASON_UNAVAILABLE );
			throw $exception;
		}

		if ( CourseAccessRules::isAfterAccessEnd( $course->settings, $now ) ) {
			$exception = new ForbiddenException( 'This course is no longer available.' );
			$exception->forReason( ForbiddenException::REASON_UNAVAILABLE );
			throw $exception;
		}

		$enrollment = $this->enrollmentRepository->findByUserAndCourse( $userId, $course->id );
		if (
			null !== $enrollment
			&& ( EnrollmentStatus::Active === $enrollment->status || EnrollmentStatus::Completed === $enrollment->status )
			&& CourseAccessRules::isEnrollmentExpired( $enrollment->expiresAt, $now )
		) {
			$exception = new ForbiddenException( 'Your access to this course has expired.' );
			$exception->forReason( ForbiddenException::REASON_EXPIRED );
			throw $exception;
		}
	}

	private function loadGatedStructure( int $courseId, bool $canBrowseContent, int $userId, bool $skipDrip = false, ?bool $isReallyEnrolled = null, bool $lockLikeLearner = false ): CourseStructureDto {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$reallyEnrolled = null !== $isReallyEnrolled ? $isReallyEnrolled : $canBrowseContent;
		$enrolledAt     = $this->resolveEnrollmentDate( $userId, $courseId, $reallyEnrolled );
		// Student preview has no enrollment row. Drip uses "enrolled just now",
		// the same clock a new learner would have. Drafts stay hidden.
		if ( null === $enrolledAt && $lockLikeLearner && ! $reallyEnrolled ) {
			$enrolledAt = $this->clock->now();
		}
		$locksApply = $reallyEnrolled || $lockLikeLearner;
		// Guests see published lessons only. An enrolled student must see lessons the
		// author already attached, including drafts — otherwise Start course has no
		// lesson id and the player opens with no course id. Instructor preview
		// ($skipDrip) includes drafts as well. $isReallyEnrolled is the explicit
		// enrollment flag; public browse must not be treated as enrolled.
		$includeDraftLessons = $skipDrip || true === $isReallyEnrolled;
		$publishedOnly       = ! $includeDraftLessons;
		$rows                = $this->sectionRepository->loadStructureRows( $courseId, $publishedOnly );
		// Same order as LessonAccessService / curriculum display (ungrouped first).
		$orderedLessonIds = CourseLessonSequence::orderedLessonIds( $rows );

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
				$locksApply,
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

		$progressionLocked  = false;
		$progressionMessage = null;
		if ( ! $skipDrip && $reallyEnrolled && null !== $course ) {
			$progressionLocked = CourseAccessRules::isLessonBlockedByProgression(
				$course->settings,
				$lesson->id,
				$orderedLessonIds,
				$completedLessonIds
			);
			if ( $progressionLocked ) {
				$progressionMessage = 'Finish the previous lesson first.';
			}
		}

		$locked     = $dripState['locked'] || $progressionLocked;
		$lockMsg    = $dripState['locked'] ? $dripState['message'] : $progressionMessage;
		$lockReason = null;
		if ( $dripState['locked'] ) {
			$lockReason = ForbiddenException::REASON_DRIP;
		} elseif ( $progressionLocked ) {
			$lockReason = ForbiddenException::REASON_PROGRESSION;
		}
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
			$lockReason,
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
	 * @return list<array<string, mixed>>
	 */
	private function quizzesOutlineForLesson( int $lessonId, int $userId, bool $isEditorPreview ): array {
		$quizzes = array();

		if ( null === $this->quizService || $userId <= 0 || $lessonId <= 0 ) {
			return $quizzes;
		}

		try {
			$quizDtos = $this->quizService->listByLessonId( $lessonId, $userId, true, $isEditorPreview );
		} catch ( ForbiddenException | NotFoundException $exception ) {
			return $quizzes;
		}

		foreach ( $quizDtos as $quizDto ) {
			$questions     = array();
			$questionCount = count( $quizDto->questions );

			foreach ( $quizDto->questions as $question ) {
				$text = trim( wp_strip_all_tags( (string) $question->prompt ) );
				if ( '' === $text ) {
					$text = \MintLMS\Infrastructure\PostType\PostTypes::questionContentsTitle( $question->id, $question->prompt );
				}
				if ( '' === trim( $text ) ) {
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

		return $quizzes;
	}

	/**
	 * Nested Section → Lesson → Quiz → Question outline for the S3 course overview.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function buildContentOutline( CourseStructureDto $structure, int $userId, bool $isEditorPreview, bool $canBrowseContent = true ): array {
		$outline = array();

		foreach ( $structure->sections as $section ) {
			$lessons = array();

			foreach ( $section->lessons as $lesson ) {
				$quizzes = $userId > 0
					? $this->quizzesOutlineForLesson( $lesson->id, $userId, $isEditorPreview )
					: array();

				$quizCount = count( $quizzes );
				$metaParts = array();
				if ( $quizCount > 0 ) {
					$metaParts[] = 1 === $quizCount ? '1 quiz' : $quizCount . ' quizzes';
				}

				$accessible = $isEditorPreview
					|| ( ! $lesson->isDripLocked && ( $canBrowseContent || $lesson->isPreview ) );

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
