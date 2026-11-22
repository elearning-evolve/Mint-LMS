<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Ai\AiDraftContentService;
use MintLMS\Application\Ai\AiScope;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Infrastructure\Capability\Roles;

/**
 * Registers Mint LMS Abilities for Claude Desktop / MCP Adapter.
 * Creates draft Mint CPTs only — never pages, posts, or other plugins' types.
 */
final class AiAbilitiesRegistrar {

	public const CATEGORY = 'mint-lms';

	public function __construct(
		private AiDraftContentService $drafts,
		private AiStructureDraftNormalizer $normalizer,
	) {
	}

	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'registerCategory' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'registerAbilities' ) );
	}

	public function registerCategory(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Mint LMS', 'mint-lms' ),
				'description' => __( 'Create and manage Mint LMS courses, lessons, quizzes, and questions as drafts only.', 'mint-lms' ),
			)
		);
	}

	public function registerAbilities(): void {
		$this->registerAbility(
			'mint-lms/create-course-draft',
			__( 'Create Mint LMS course draft', 'mint-lms' ),
			__( 'Creates a draft Mint LMS course (mint-course) only. Never creates WordPress pages or posts. Use when the user asks to create a Mint LMS course.', 'mint-lms' ),
			array(
				'type'       => 'object',
				'properties' => array(
					'title'       => array(
						'type'        => 'string',
						'description' => 'Course title',
					),
					'description' => array(
						'type'        => 'string',
						'description' => 'Course description',
					),
				),
				'required'   => array( 'title' ),
			),
			array( $this, 'executeCreateCourse' )
		);

		$this->registerAbility(
			'mint-lms/create-lesson-draft',
			__( 'Create Mint LMS lesson draft', 'mint-lms' ),
			__( 'Creates a draft Mint LMS lesson (mint-lesson). Optionally attach to a course and lesson group. Standalone when course_id is omitted. Never creates pages/posts.', 'mint-lms' ),
			array(
				'type'       => 'object',
				'properties' => array(
					'title'       => array(
						'type'        => 'string',
						'description' => 'Lesson title',
					),
					'description' => array(
						'type'        => 'string',
						'description' => 'Lesson content/description',
					),
					'course_id'   => array(
						'type'        => 'integer',
						'description' => 'Optional Mint course ID to attach the lesson to',
					),
					'section_id'  => array(
						'type'        => 'integer',
						'description' => 'Optional lesson group (section) ID within the course',
					),
				),
				'required'   => array( 'title' ),
			),
			array( $this, 'executeCreateLesson' )
		);

		$this->registerAbility(
			'mint-lms/create-quiz-draft',
			__( 'Create Mint LMS quiz draft', 'mint-lms' ),
			__( 'Creates a draft Mint LMS quiz (mint-quiz). Pass lesson_id to attach, or omit for a standalone quiz. Optional questions array. Never creates pages/posts.', 'mint-lms' ),
			array(
				'type'       => 'object',
				'properties' => array(
					'title'     => array(
						'type'        => 'string',
						'description' => 'Quiz title',
					),
					'lesson_id' => array(
						'type'        => 'integer',
						'description' => 'Optional Mint lesson ID',
					),
					'questions' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'type'           => array( 'type' => 'string' ),
								'prompt'         => array( 'type' => 'string' ),
								'options'        => array(
									'type'  => 'array',
									'items' => array( 'type' => 'string' ),
								),
								'correct_answer' => array( 'type' => 'string' ),
							),
						),
					),
				),
				'required'   => array( 'title' ),
			),
			array( $this, 'executeCreateQuiz' )
		);

		$this->registerAbility(
			'mint-lms/create-question-draft',
			__( 'Create Mint LMS question draft', 'mint-lms' ),
			__( 'Creates a draft Mint LMS question on an existing quiz. Types: mcq, true_false. Never creates pages/posts.', 'mint-lms' ),
			array(
				'type'       => 'object',
				'properties' => array(
					'quiz_id'        => array(
						'type'        => 'integer',
						'description' => 'Existing Mint quiz ID',
					),
					'type'           => array(
						'type'        => 'string',
						'description' => 'mcq or true_false',
					),
					'prompt'         => array(
						'type'        => 'string',
						'description' => 'Question text',
					),
					'options'        => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
					'correct_answer' => array(
						'type'        => 'string',
						'description' => 'Exact correct option text, or True/False',
					),
				),
				'required'   => array( 'quiz_id', 'prompt' ),
			),
			array( $this, 'executeCreateQuestion' )
		);

		$this->registerAbility(
			'mint-lms/create-structure-drafts',
			__( 'Create Mint LMS structure as drafts', 'mint-lms' ),
			__( 'Creates a full Mint LMS hierarchy as drafts from structured JSON (root: course, lesson, or quiz). Use when the user prompt describes an entire course tree or standalone lesson/quiz with nested items. Only mint-course, mint-lesson, mint-quiz, mint-question. Never creates WordPress pages, posts, or other plugin content.', 'mint-lms' ),
			array(
				'type'       => 'object',
				'properties' => array(
					'root'   => array(
						'type'        => 'string',
						'description' => 'course, lesson, or quiz',
					),
					'course' => array( 'type' => 'object' ),
					'lesson' => array( 'type' => 'object' ),
					'quiz'   => array( 'type' => 'object' ),
				),
				'required'   => array( 'root' ),
			),
			array( $this, 'executeCreateStructure' )
		);
	}

	/**
	 * @param array<string, mixed> $input_schema
	 * @param callable             $execute
	 */
	private function registerAbility(
		string $name,
		string $label,
		string $description,
		array $input_schema,
		callable $execute
	): void {
		wp_register_ability(
			$name,
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => self::CATEGORY,
				'input_schema'        => $input_schema,
				'output_schema'       => array(
					'type' => 'object',
				),
				'execute_callback'    => $execute,
				'permission_callback' => array( $this, 'canManageMintContent' ),
				'meta'                => array(
					'public'       => true,
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * @param mixed $input
	 */
	public function canManageMintContent( $input = null ): bool {
		return current_user_can( Roles::CAP_EDIT ) || current_user_can( 'manage_options' );
	}

	/**
	 * @param mixed $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public function executeCreateCourse( $input = null ) {
		$input = is_array( $input ) ? $input : array();

		return $this->run(
			function () use ( $input ) {
				$result = $this->drafts->createCourseDraft(
					(string) ( $input['title'] ?? '' ),
					(string) ( $input['description'] ?? '' ),
					get_current_user_id()
				);

				return $this->withEditUrl( $result );
			}
		);
	}

	/**
	 * @param mixed $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public function executeCreateLesson( $input = null ) {
		$input = is_array( $input ) ? $input : array();

		return $this->run(
			function () use ( $input ) {
				$courseId  = isset( $input['course_id'] ) ? (int) $input['course_id'] : null;
				$sectionId = isset( $input['section_id'] ) ? (int) $input['section_id'] : null;
				$result    = $this->drafts->createLessonDraft(
					(string) ( $input['title'] ?? '' ),
					(string) ( $input['description'] ?? '' ),
					get_current_user_id(),
					$courseId,
					$sectionId
				);

				return $this->withEditUrl( $result );
			}
		);
	}

	/**
	 * @param mixed $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public function executeCreateQuiz( $input = null ) {
		$input = is_array( $input ) ? $input : array();

		return $this->run(
			function () use ( $input ) {
				$lessonId  = isset( $input['lesson_id'] ) ? (int) $input['lesson_id'] : null;
				$questions = isset( $input['questions'] ) && is_array( $input['questions'] ) ? $input['questions'] : null;
				$result    = $this->drafts->createQuizDraft(
					(string) ( $input['title'] ?? '' ),
					get_current_user_id(),
					$lessonId,
					$questions
				);

				return $this->withEditUrl( $result );
			}
		);
	}

	/**
	 * @param mixed $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public function executeCreateQuestion( $input = null ) {
		$input = is_array( $input ) ? $input : array();

		return $this->run(
			function () use ( $input ) {
				$options = array();
				if ( isset( $input['options'] ) && is_array( $input['options'] ) ) {
					foreach ( $input['options'] as $option ) {
						$options[] = (string) $option;
					}
				}

				$result = $this->drafts->createQuestionDraft(
					(int) ( $input['quiz_id'] ?? 0 ),
					(string) ( $input['type'] ?? 'mcq' ),
					(string) ( $input['prompt'] ?? '' ),
					$options,
					(string) ( $input['correct_answer'] ?? '' ),
					get_current_user_id()
				);

				if ( $result['id'] > 0 ) {
					$edit = get_edit_post_link( $result['id'], 'raw' );
					$result['edit_url'] = is_string( $edit ) ? $edit : '';
				}

				return $result;
			}
		);
	}

	/**
	 * @param mixed $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public function executeCreateStructure( $input = null ) {
		$input = is_array( $input ) ? $input : array();

		return $this->run(
			function () use ( $input ) {
				// Reject any attempt to target non-Mint types in the payload.
				$normalized = $this->normalizer->normalize( $input );
				$created    = $this->drafts->createFromStructure( $normalized, get_current_user_id() );

				return array_merge(
					$created,
					array(
						'persisted' => true,
						'status'    => 'draft',
						'scope'     => 'mintlms_only',
						'note'      => __( 'Draft Mint LMS content created. WordPress pages/posts were not touched.', 'mint-lms' ),
					)
				);
			}
		);
	}

	/**
	 * @param callable(): array<string, mixed> $callback
	 * @return array<string, mixed>|\WP_Error
	 */
	private function run( callable $callback ) {
		try {
			return $callback();
		} catch ( ValidationException $e ) {
			return new \WP_Error( 'mintlms_ai_validation', $e->getMessage(), array( 'status' => 400 ) );
		} catch ( ForbiddenException $e ) {
			$forbidden_msg = $e->getMessage();
			if ( '' === $forbidden_msg ) {
				$forbidden_msg = __( 'Forbidden.', 'mint-lms' );
			}
			return new \WP_Error( 'mintlms_ai_forbidden', $forbidden_msg, array( 'status' => 403 ) );
		} catch ( NotFoundException $e ) {
			return new \WP_Error( 'mintlms_ai_not_found', $e->getMessage(), array( 'status' => 404 ) );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'mintlms_ai_error', __( 'Could not create Mint LMS draft content.', 'mint-lms' ), array( 'status' => 500 ) );
		}
	}

	/**
	 * @param array<string, mixed> $result
	 * @return array<string, mixed>
	 */
	private function withEditUrl( array $result ): array {
		$id = (int) ( $result['id'] ?? 0 );
		if ( $id > 0 ) {
			$edit = get_edit_post_link( $id, 'raw' );
			$result['edit_url'] = is_string( $edit ) ? $edit : '';
		}
		$result['scope'] = 'mintlms_only';

		// Hard guard: never claim a forbidden post type.
		$type = (string) ( $result['type'] ?? '' );
		if ( '' !== $type && AiScope::isForbiddenPostType( $type ) ) {
			return array(
				'error' => 'out_of_scope',
			);
		}

		return $result;
	}
}
