<?php
declare(strict_types=1);

namespace MintLMS\Application\Ai;

use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\Dto\CreateCourseDto;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Lesson\Dto\CreateLessonDto;
use MintLMS\Application\Lesson\LessonService;
use MintLMS\Application\Quiz\Dto\CreateQuestionDto;
use MintLMS\Application\Quiz\Dto\CreateQuizDto;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Application\Section\Dto\CreateSectionDto;
use MintLMS\Application\Section\SectionService;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Quiz\QuizQuestion;

/**
 * Creates Mint LMS content as drafts for AI / MCP abilities.
 * Never creates WordPress pages, posts, or third-party post types.
 */
final class AiDraftContentService {

	public function __construct(
		private CourseService $courses,
		private SectionService $sections,
		private LessonService $lessons,
		private QuizService $quizzes,
	) {
	}

	/**
	 * @return array{id: int, title: string, status: string, type: string}
	 */
	public function createCourseDraft( string $title, string $description, int $userId ): array {
		$title = trim( $title );
		if ( '' === $title ) {
			throw new ValidationException( 'Course title is required.', array( 'title' => 'required' ) );
		}

		$course = $this->courses->create(
			new CreateCourseDto(
				$title,
				null,
				trim( $description ),
				null,
				EnrollmentType::Free,
			),
			$userId
		);

		return array(
			'id'     => $course->id,
			'title'  => $course->title,
			'status' => 'draft',
			'type'   => AiScope::POST_COURSE,
		);
	}

	/**
	 * @return array{id: int, title: string, status: string, type: string, course_id: int, section_id: int}
	 */
	public function createLessonDraft(
		string $title,
		string $description,
		int $userId,
		?int $courseId = null,
		?int $sectionId = null
	): array {
		$title = trim( $title );
		if ( '' === $title ) {
			throw new ValidationException( 'Lesson title is required.', array( 'title' => 'required' ) );
		}

		$dto = new CreateLessonDto(
			$title,
			null,
			trim( $description ),
			null,
			null,
			false,
		);

		if ( null !== $courseId && $courseId > 0 ) {
			$lesson = $this->lessons->createForCourse( $courseId, $dto, $userId, $sectionId );
		} else {
			$lesson = $this->lessons->createStandalone( $dto, $userId );
		}

		return array(
			'id'         => $lesson->id,
			'title'      => $lesson->title,
			'status'     => 'draft',
			'type'       => AiScope::POST_LESSON,
			'course_id'  => $lesson->courseId,
			'section_id' => $lesson->sectionId,
		);
	}

	/**
	 * @param list<array{type?: string, prompt?: string, options?: list<string>, correct_answer?: string}>|null $questions
	 * @return array{id: int, title: string, status: string, type: string, lesson_id: int, course_id: int}
	 */
	public function createQuizDraft(
		string $title,
		int $userId,
		?int $lessonId = null,
		?array $questions = null
	): array {
		$title = trim( $title );
		if ( '' === $title ) {
			throw new ValidationException( 'Quiz title is required.', array( 'title' => 'required' ) );
		}

		$normalizedQuestions = null;
		if ( is_array( $questions ) && array() !== $questions ) {
			$normalizedQuestions = array();
			foreach ( $questions as $question ) {
				if ( ! is_array( $question ) ) {
					continue;
				}
				$normalizedQuestions[] = $this->mapQuestionPayload( $question );
			}
		}

		$dto = new CreateQuizDto( $title, 80, $normalizedQuestions );

		if ( null !== $lessonId && $lessonId > 0 ) {
			$quiz = $this->quizzes->create( $lessonId, $dto, $userId );
		} else {
			// Standalone quiz: disposable host lesson (course 0), appears in quiz list.
			$host = $this->lessons->createStandalone(
				new CreateLessonDto( 'Quiz host', null, '', null, null, false ),
				$userId
			);
			$quiz = $this->quizzes->create( $host->id, $dto, $userId );
		}

		return array(
			'id'        => $quiz->id,
			'title'     => $quiz->title,
			'status'    => 'draft',
			'type'      => AiScope::POST_QUIZ,
			'lesson_id' => $quiz->lessonId,
			'course_id' => $quiz->courseId,
		);
	}

	/**
	 * @param list<string> $options
	 * @return array{id: int, quiz_id: int, type: string, prompt: string, status: string, type_cpt: string}
	 */
	public function createQuestionDraft(
		int $quizId,
		string $type,
		string $prompt,
		array $options,
		string $correctAnswer,
		int $userId
	): array {
		$prompt = trim( $prompt );
		if ( '' === $prompt ) {
			throw new ValidationException( 'Question prompt is required.', array( 'prompt' => 'required' ) );
		}
		if ( $quizId <= 0 ) {
			throw new ValidationException( 'quiz_id is required.', array( 'quiz_id' => 'required' ) );
		}

		$type = $this->normalizeQuestionType( $type );
		$quiz = $this->quizzes->addQuestion(
			$quizId,
			new CreateQuestionDto( $type, $prompt, array_values( $options ), $correctAnswer ),
			$userId
		);

		$createdId     = 0;
		$createdPrompt = $prompt;
		foreach ( array_reverse( $quiz->questions ) as $question ) {
			if ( $question->id > 0 && $question->prompt === $prompt ) {
				$createdId     = $question->id;
				$createdPrompt = $question->prompt;
				$type          = $question->type;
				break;
			}
		}
		if ( $createdId <= 0 && array() !== $quiz->questions ) {
			$last          = $quiz->questions[ count( $quiz->questions ) - 1 ];
			$createdId     = $last->id;
			$createdPrompt = $last->prompt;
			$type          = $last->type;
		}

		return array(
			'id'       => $createdId,
			'quiz_id'  => $quizId,
			'type'     => $type,
			'prompt'   => $createdPrompt,
			'status'   => 'draft',
			'type_cpt' => AiScope::POST_QUESTION,
		);
	}

	/**
	 * Persist a normalized Mint hierarchy (course / lesson / quiz root) as drafts.
	 *
	 * @param array<string, mixed> $structure Output of AiStructureDraftNormalizer::normalize().
	 * @return array<string, mixed>
	 */
	public function createFromStructure( array $structure, int $userId ): array {
		$root = (string) ( $structure['root'] ?? '' );
		if ( ! AiScope::isAllowedRoot( $root ) ) {
			throw new ValidationException(
				'Structure root must be course, lesson, or quiz.',
				array( 'root' => 'invalid' )
			);
		}

		if ( AiScope::ROOT_COURSE === $root ) {
			return $this->persistCourseTree(
				is_array( $structure['course'] ?? null ) ? $structure['course'] : array(),
				$userId
			);
		}

		if ( AiScope::ROOT_LESSON === $root ) {
			$lessonIn   = is_array( $structure['lesson'] ?? null ) ? $structure['lesson'] : array();
			$lesson     = $this->createLessonDraft(
				(string) ( $lessonIn['title'] ?? '' ),
				(string) ( $lessonIn['description'] ?? '' ),
				$userId
			);
			$quizzesOut = array();
			foreach ( (array) ( $lessonIn['quizzes'] ?? array() ) as $quizIn ) {
				if ( ! is_array( $quizIn ) ) {
					continue;
				}
				$quizzesOut[] = $this->persistQuizOnLesson( $quizIn, $lesson['id'], $userId );
			}

			return array(
				'root'    => 'lesson',
				'lesson'  => $lesson,
				'quizzes' => $quizzesOut,
			);
		}

		$quizIn = is_array( $structure['quiz'] ?? null ) ? $structure['quiz'] : array();

		return array(
			'root' => 'quiz',
			'quiz' => $this->persistQuizOnLesson( $quizIn, null, $userId ),
		);
	}

	/**
	 * @param array<string, mixed> $course
	 * @return array<string, mixed>
	 */
	private function persistCourseTree( array $course, int $userId ): array {
		$created = $this->createCourseDraft(
			(string) ( $course['title'] ?? '' ),
			(string) ( $course['description'] ?? '' ),
			$userId
		);

		$groupsOut = array();
		$groupsIn  = $course['lesson_groups'] ?? array();
		if ( ! is_array( $groupsIn ) || array() === $groupsIn ) {
			// Prompt asked for a course — ensure at least one lesson group shell for hierarchy.
			$groupsIn = array(
				array(
					'title'   => 'Lesson Group 1',
					'lessons' => array(),
				),
			);
		}

		foreach ( $groupsIn as $groupIn ) {
			if ( ! is_array( $groupIn ) ) {
				continue;
			}
			$group_title = trim( (string) ( $groupIn['title'] ?? 'Lesson Group' ) );
			if ( '' === $group_title ) {
				$group_title = 'Lesson Group';
			}
			$section = $this->sections->create(
				$created['id'],
				new CreateSectionDto( $group_title ),
				$userId
			);

			$lessonsOut = array();
			foreach ( (array) ( $groupIn['lessons'] ?? array() ) as $lessonIn ) {
				if ( ! is_array( $lessonIn ) ) {
					continue;
				}
				$lesson = $this->createLessonDraft(
					(string) ( $lessonIn['title'] ?? '' ),
					(string) ( $lessonIn['description'] ?? '' ),
					$userId,
					$created['id'],
					$section->id
				);

				$quizzesOut = array();
				foreach ( (array) ( $lessonIn['quizzes'] ?? array() ) as $quizIn ) {
					if ( ! is_array( $quizIn ) ) {
						continue;
					}
					$quizzesOut[] = $this->persistQuizOnLesson( $quizIn, $lesson['id'], $userId );
				}

				$lessonsOut[] = array_merge( $lesson, array( 'quizzes' => $quizzesOut ) );
			}

			$groupsOut[] = array(
				'id'      => $section->id,
				'title'   => $section->title,
				'lessons' => $lessonsOut,
			);
		}

		return array(
			'root'          => 'course',
			'course'        => $created,
			'lesson_groups' => $groupsOut,
		);
	}

	/**
	 * @param array<string, mixed> $quizIn
	 * @return array<string, mixed>
	 */
	private function persistQuizOnLesson( array $quizIn, ?int $lessonId, int $userId ): array {
		$questions = array();
		foreach ( (array) ( $quizIn['questions'] ?? array() ) as $question ) {
			if ( is_array( $question ) ) {
				$questions[] = $this->mapQuestionPayload( $question );
			}
		}

		return $this->createQuizDraft(
			(string) ( $quizIn['title'] ?? '' ),
			$userId,
			$lessonId,
			array() !== $questions ? $questions : null
		);
	}

	/**
	 * @param array<string, mixed> $question
	 * @return array{type: string, prompt: string, options: list<string>, correct_answer: string}
	 */
	private function mapQuestionPayload( array $question ): array {
		$type    = $this->normalizeQuestionType( (string) ( $question['type'] ?? QuizQuestion::TYPE_MCQ ) );
		$prompt  = trim( (string) ( $question['prompt'] ?? $question['title'] ?? '' ) );
		$options = array();
		if ( isset( $question['options'] ) && is_array( $question['options'] ) ) {
			foreach ( $question['options'] as $option ) {
				$clean = trim( (string) $option );
				if ( '' !== $clean ) {
					$options[] = $clean;
				}
			}
		}
		$correct = trim( (string) ( $question['correct_answer'] ?? $question['correctAnswer'] ?? '' ) );

		if ( QuizQuestion::TYPE_TRUE_FALSE === $type ) {
			$options = array( 'True', 'False' );
			$correct = in_array( strtolower( $correct ), array( 'true', '1', 'yes' ), true ) ? 'True' : 'False';
		}

		return array(
			'type'           => $type,
			'prompt'         => $prompt,
			'options'        => $options,
			'correct_answer' => $correct,
		);
	}

	private function normalizeQuestionType( string $type ): string {
		$type = strtolower( trim( $type ) );
		if ( in_array( $type, array( 'true_false', 'truefalse', 'boolean' ), true ) ) {
			return QuizQuestion::TYPE_TRUE_FALSE;
		}
		if ( in_array( $type, AiScope::allowedQuestionTypes(), true ) ) {
			return $type;
		}

		return QuizQuestion::TYPE_MCQ;
	}
}
