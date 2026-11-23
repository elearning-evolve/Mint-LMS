<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\PostType;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Quiz\Quiz;
use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Infrastructure\Database\Repository\WpPostLessonRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostQuizRepository;
use MintLMS\Logger;

final class PostTypeRegistrar {

	public function register(): void {
		add_action( 'init', array( $this, 'registerPostTypes' ) );
		add_action( 'init', array( $this, 'registerStatuses' ), 11 );
		add_action( 'admin_menu', array( $this, 'registerStarterPages' ) );
		add_action( 'admin_init', array( $this, 'redirectLegacyCptLists' ) );
		add_action( 'load-admin_page_mint-lms-new-lesson', array( $this, 'bootstrapNewLesson' ) );
		add_action( 'load-admin_page_mint-lms-new-quiz', array( $this, 'bootstrapNewQuiz' ) );
		add_action( 'load-admin_page_mint-lms-new-question', array( $this, 'bootstrapNewQuestion' ) );
		add_action( 'load-admin_page_mint-lms-edit-quiz', array( $this, 'bootstrapEditQuiz' ) );
	}

	/**
	 * Hidden admin entrypoints for Add New Lesson / Quiz / Question (more reliable than post-new.php).
	 */
	public function registerStarterPages(): void {
		add_submenu_page(
			'',
			__( 'New Lesson', 'mint-lms' ),
			__( 'New Lesson', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-new-lesson',
			'__return_null'
		);

		add_submenu_page(
			'',
			__( 'New Quiz', 'mint-lms' ),
			__( 'New Quiz', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-new-quiz',
			'__return_null'
		);

		add_submenu_page(
			'',
			__( 'New Question', 'mint-lms' ),
			__( 'New Question', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-new-question',
			'__return_null'
		);

		add_submenu_page(
			'',
			__( 'Edit Quiz', 'mint-lms' ),
			__( 'Edit Quiz', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-edit-quiz',
			'__return_null'
		);
	}

	public function bootstrapNewLesson(): void {
		$this->bootstrapStarter( 'lesson' );
	}

	public function bootstrapNewQuiz(): void {
		$this->bootstrapStarter( 'quiz' );
	}

	public function bootstrapNewQuestion(): void {
		$this->bootstrapStarter( 'question' );
	}

	/**
	 * Quizzes / Questions list Edit → resolve course/lesson (repair meta / untrash) → builder.
	 */
	public function bootstrapEditQuiz(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit content.', 'mint-lms' ) );
		}

		$quizId     = isset( $_GET['quiz_id'] ) ? absint( $_GET['quiz_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect gate.
		$questionId = isset( $_GET['question_id'] ) ? absint( $_GET['question_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect gate.

		// WP-native Add New Question creates a bare CPT with no quiz junction — attach a host shell.
		if ( $questionId > 0 ) {
			try {
				$quizId = ( new QuestionLinkResolver() )->ensureForBuilder(
					$questionId,
					fn (): array => $this->createNewQuizStarter()
				);
			} catch ( \Throwable $e ) {
				Logger::error( 'Mint LMS ensure question for builder failed: ' . $e->getMessage() );
				$quizId = 0;
			}
		}

		if ( $quizId <= 0 ) {
			wp_safe_redirect(
				$questionId > 0
					? PostTypes::listUrl( PostTypes::QUESTION )
					: PostTypes::listUrl( PostTypes::QUIZ )
			);
			exit;
		}

		try {
			$target = $this->resolveQuizBuilderTarget( $quizId );
		} catch ( \Throwable $e ) {
			Logger::error( 'Mint LMS edit quiz resolve failed: ' . $e->getMessage() );
			wp_safe_redirect( PostTypes::listUrl( PostTypes::QUIZ ) );
			exit;
		}

		if ( null === $target ) {
			wp_safe_redirect( PostTypes::listUrl( PostTypes::QUIZ ) );
			exit;
		}

		$from = $questionId > 0 ? 'questions' : 'quizzes';

		if ( (int) $target['course_id'] <= 0 ) {
			$url = 'admin.php?page=mint-lms-lesson-edit&lesson_id=' . $target['lesson_id']
				. '&quiz_id=' . $quizId
				. '&tab=quiz&from=' . $from;
			if ( $questionId > 0 ) {
				$url .= '&question_id=' . $questionId;
			}
			wp_safe_redirect( admin_url( $url ) );
			exit;
		}

		$url = 'admin.php?page=mint-lms-builder&course_id=' . $target['course_id']
			. '&lesson_id=' . $target['lesson_id']
			. '&quiz_id=' . $quizId
			. '&from=' . $from
			. '&tab=quiz';

		if ( $questionId > 0 ) {
			$url .= '&question_id=' . $questionId;
		}

		wp_safe_redirect( admin_url( $url ) );
		exit;
	}

	/**
	 * @param 'lesson'|'quiz'|'question' $type
	 */
	private function bootstrapStarter( string $type ): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to create content.', 'mint-lms' ) );
		}

		try {
			if ( 'quiz' === $type ) {
				$created = $this->createNewQuizStarter();
				wp_safe_redirect(
					admin_url( 'post.php?post=' . (int) $created['quiz_id'] . '&action=edit' )
				);
				exit;
			}

			if ( 'question' === $type ) {
				$created = $this->createNewQuestionStarter();
				wp_safe_redirect(
					admin_url( 'post.php?post=' . (int) $created['question_id'] . '&action=edit' )
				);
				exit;
			}

			// Add New Lesson (legacy mint-lms-new-lesson) → native WP editor.
			$created = $this->createNewLessonStarter();
			wp_safe_redirect(
				admin_url( 'post.php?post=' . (int) $created['lesson_id'] . '&action=edit' )
			);
			exit;
		} catch ( \Throwable $e ) {
			Logger::error( 'Mint LMS new content starter failed: ' . $e->getMessage() );
			wp_safe_redirect(
				match ( $type ) {
					'quiz'     => PostTypes::listUrl( PostTypes::QUIZ ),
					'question' => PostTypes::listUrl( PostTypes::QUESTION ),
					default    => PostTypes::listUrl( PostTypes::LESSON ),
				}
			);
			exit;
		}
	}

	public function registerPostTypes(): void {
		// Content is learner-gated by enrollment, never by "published" alone:
		// - No front-end queries / feeds for these types (student URLs are Mint pages).
		// - REST stays on for the block editor, but reads require edit access.
		$common = array(
			'public'                => false,
			'publicly_queryable'    => false,
			'show_in_rest'          => true,
			'rest_controller_class' => ContentPostsRestController::class,
			'exclude_from_search'   => true,
			'has_archive'           => false,
			'rewrite'               => false,
			'query_var'             => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
		);

		// Native WP list + edit UI. Menu links live under Mint LMS (see MenuRegistrar).
		// Primitive caps map to Mint caps so instructors can use edit.php without edit_posts.
		$caps = $this->mintPostCapabilities();

		register_post_type(
			PostTypes::COURSE,
			array_merge(
				$common,
				array(
					'labels'       => $this->labels(
						__( 'Courses', 'mint-lms' ),
						__( 'Course', 'mint-lms' )
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					'supports'     => array( 'title', 'editor', 'thumbnail', 'author' ),
					'capabilities' => $caps,
				)
			)
		);

		register_post_type(
			PostTypes::LESSON,
			array_merge(
				$common,
				array(
					'labels'       => $this->labels(
						__( 'Lessons', 'mint-lms' ),
						__( 'Lesson', 'mint-lms' )
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					'supports'     => array( 'title', 'editor', 'thumbnail', 'author' ),
					'capabilities' => $caps,
				)
			)
		);

		register_post_type(
			PostTypes::QUIZ,
			array_merge(
				$common,
				array(
					'labels'       => $this->labels(
						__( 'Quizzes', 'mint-lms' ),
						__( 'Quiz', 'mint-lms' )
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					// Match lesson/course native Add New / Edit chrome (title + editor + featured image).
					'supports'     => array( 'title', 'editor', 'thumbnail', 'author' ),
					'capabilities' => $caps,
				)
			)
		);

		register_post_type(
			PostTypes::QUESTION,
			array_merge(
				$common,
				array(
					'labels'       => $this->labels(
						__( 'Questions', 'mint-lms' ),
						__( 'Question', 'mint-lms' )
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					'supports'     => array( 'title', 'editor', 'author', 'thumbnail' ),
					'capabilities' => $caps,
				)
			)
		);
	}

	/**
	 * Map CPT primitive caps onto Mint caps (not WordPress edit_posts).
	 * Meta caps (edit_post, etc.) still flow through map_meta_cap → these primitives.
	 *
	 * @return array<string, string>
	 */
	private function mintPostCapabilities(): array {
		return array(
			'edit_posts'             => 'edit_mintlms_courses',
			'edit_others_posts'      => 'edit_others_mintlms_courses',
			'delete_posts'           => 'edit_mintlms_courses',
			'publish_posts'          => 'edit_mintlms_courses',
			'read_private_posts'     => 'edit_mintlms_courses',
			'delete_private_posts'   => 'edit_mintlms_courses',
			'delete_published_posts' => 'edit_mintlms_courses',
			'delete_others_posts'    => 'edit_others_mintlms_courses',
			'edit_private_posts'     => 'edit_mintlms_courses',
			'edit_published_posts'   => 'edit_mintlms_courses',
			'create_posts'           => 'edit_mintlms_courses',
		);
	}

	public function registerStatuses(): void {
		register_post_status(
			PostTypes::STATUS_ARCHIVED,
			array(
				'label'                     => _x( 'Archived', 'course status', 'mint-lms' ),
				'public'                    => false,
				'internal'                  => true,
				'protected'                 => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
			)
		);
	}

	/**
	 * Old custom Mint list screens → native WordPress CPT list tables.
	 */
	public function redirectLegacyCptLists(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect.

		$map = array(
			'mint-lms-courses'   => PostTypes::COURSE,
			'mint-lms-lessons'   => PostTypes::LESSON,
			'mint-lms-quizzes'   => PostTypes::QUIZ,
			'mint-lms-questions' => PostTypes::QUESTION,
		);

		if ( ! isset( $map[ $page ] ) ) {
			return;
		}

		wp_safe_redirect( PostTypes::listUrl( $map[ $page ] ) );
		exit;
	}

	/**
	 * Ensure a quiz has usable course/lesson links for the builder.
	 *
	 * @return array{course_id: int, lesson_id: int}|null
	 */
	private function resolveQuizBuilderTarget( int $quizId ): ?array {
		return ( new QuizLinkResolver() )->ensureForBuilder(
			$quizId,
			fn (): array => $this->createNewLessonStarter()
		);
	}

	/**
	 * Standalone library lesson (no course). Used by quiz/question starters as a host.
	 *
	 * @return array{course_id: int, lesson_id: int}
	 */
	private function createNewLessonStarter(): array {
		$now = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );

		$lesson = ( new WpPostLessonRepository() )->save(
			new Lesson(
				0,
				0,
				0,
				__( 'New Lesson', 'mint-lms' ),
				'new-lesson-' . wp_generate_password( 4, false, false ),
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

		PostTypes::markDisposableHostLesson( (int) $lesson->id );

		return array(
			'course_id' => 0,
			'lesson_id' => $lesson->id,
		);
	}

	/**
	 * @return array{course_id: int, lesson_id: int, quiz_id: int}
	 */
	private function createNewQuizStarter(): array {
		$wpdb = mintlms_wpdb();

		if ( null === $wpdb ) {
			throw new \RuntimeException( 'Database unavailable.' );
		}

		$created  = $this->createNewLessonStarter();
		$quizRepo = new WpPostQuizRepository( $wpdb );

		$quiz = $quizRepo->saveQuiz(
			new Quiz(
				0,
				$created['lesson_id'],
				$created['course_id'],
				__( 'New Quiz', 'mint-lms' ),
				80,
				0,
			)
		);

		return array(
			'course_id' => $created['course_id'],
			'lesson_id' => $created['lesson_id'],
			'quiz_id'   => $quiz->id,
		);
	}

	/**
	 * @return array{course_id: int, lesson_id: int, quiz_id: int, question_id: int}
	 */
	private function createNewQuestionStarter(): array {
		$wpdb = mintlms_wpdb();

		if ( null === $wpdb ) {
			throw new \RuntimeException( 'Database unavailable.' );
		}

		$created  = $this->createNewQuizStarter();
		$quizRepo = new WpPostQuizRepository( $wpdb );

		$question = $quizRepo->saveQuestion(
			new QuizQuestion(
				0,
				$created['quiz_id'],
				QuizQuestion::TYPE_MCQ,
				__( 'New Question', 'mint-lms' ),
				array(
					__( 'Option A', 'mint-lms' ),
					__( 'Option B', 'mint-lms' ),
				),
				'',
				0,
			)
		);

		return array(
			'course_id'   => $created['course_id'],
			'lesson_id'   => $created['lesson_id'],
			'quiz_id'     => $created['quiz_id'],
			'question_id' => $question->id,
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function labels( string $plural, string $singular ): array {
		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'add_new'            => __( 'Add New', 'mint-lms' ),
			/* translators: %s: singular post type label */
			'add_new_item'       => sprintf( __( 'Add New %s', 'mint-lms' ), $singular ),
			/* translators: %s: singular post type label */
			'edit_item'          => sprintf( __( 'Edit %s', 'mint-lms' ), $singular ),
			/* translators: %s: singular post type label */
			'new_item'           => sprintf( __( 'New %s', 'mint-lms' ), $singular ),
			/* translators: %s: singular post type label */
			'view_item'          => sprintf( __( 'View %s', 'mint-lms' ), $singular ),
			/* translators: %s: plural post type label */
			'search_items'       => sprintf( __( 'Search %s', 'mint-lms' ), $plural ),
			/* translators: %s: plural post type label */
			'not_found'          => sprintf( __( 'No %s found.', 'mint-lms' ), strtolower( $plural ) ),
			/* translators: %s: plural post type label */
			'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'mint-lms' ), strtolower( $plural ) ),
			'menu_name'          => $plural,
			'all_items'          => $plural,
		);
	}
}
