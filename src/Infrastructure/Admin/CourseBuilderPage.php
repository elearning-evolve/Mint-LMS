<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

use MintLMS\Application\Course\Dto\CreateCourseDto;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\Setup\PageSettings;
use MintLMS\Plugin;

final class CourseBuilderPage {

	private const CREATE_NONCE_ACTION = 'mint_lms_create_course';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerPages' ) );
		add_action( 'load-admin_page_mint-lms-builder', array( $this, 'setBuilderTitle' ) );
		add_action( 'load-admin_page_mint-lms-course-edit', array( $this, 'setEditTitle' ) );
		add_action( 'load-admin_page_mint-lms-course-new', array( $this, 'createAndRedirect' ) );
		add_action( 'load-admin_page_mint-lms-lesson-edit', array( $this, 'setLessonEditTitle' ) );
	}

	public static function newCourseUrl(): string {
		return admin_url( 'post-new.php?post_type=' . PostTypes::COURSE );
	}

	public function setBuilderTitle(): void {
		global $title;
		$title = __( 'Course Builder', 'mint-lms' );
	}

	public function setEditTitle(): void {
		global $title;
		$title = __( 'Edit Course', 'mint-lms' );
	}

	public function setLessonEditTitle(): void {
		global $title;
		$title = __( 'Edit Lesson', 'mint-lms' );
	}

	public function registerPages(): void {
		add_submenu_page(
			'',
			__( 'Course Builder', 'mint-lms' ),
			__( 'Course Builder', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-builder',
			array( $this, 'renderBuilder' )
		);

		add_submenu_page(
			'',
			__( 'Edit Course', 'mint-lms' ),
			__( 'Edit Course', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-course-edit',
			array( $this, 'renderEdit' )
		);

		add_submenu_page(
			'',
			__( 'New Course', 'mint-lms' ),
			__( 'New Course', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-course-new',
			array( $this, 'renderNew' )
		);

		add_submenu_page(
			'',
			__( 'Edit Lesson', 'mint-lms' ),
			__( 'Edit Lesson', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-lesson-edit',
			array( $this, 'renderLessonEdit' )
		);
	}

	/**
	 * Create a draft course and send the user to Course settings.
	 */
	public function createAndRedirect(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to create courses.', 'mint-lms' ) );
		}

		check_admin_referer( self::CREATE_NONCE_ACTION );

		$coursesUrl = PostTypes::listUrl( PostTypes::COURSE );

		try {
			$enrollmentRaw = ( new PageSettings() )->getDefaultEnrollment();
			$enrollment    = EnrollmentType::tryFrom( $enrollmentRaw ) ?? EnrollmentType::Open;

			$course = Plugin::courseService()->create(
				new CreateCourseDto(
					__( 'Untitled Course', 'mint-lms' ),
					null,
					'',
					null,
					$enrollment,
				),
				get_current_user_id()
			);

			wp_safe_redirect(
				admin_url( 'post.php?post=' . (int) $course->id . '&action=edit' )
			);
			exit;
		} catch ( \Throwable ) {
			wp_safe_redirect( add_query_arg( 'mint_create_error', '1', $coursesUrl ) );
			exit;
		}
	}

	public function renderNew(): void {
		// createAndRedirect() runs on load-*; this is only a fallback if that hook is skipped.
		$this->createAndRedirect();
	}

	public function renderBuilder(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access the course builder.', 'mint-lms' ) );
		}

		$courseId = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $courseId <= 0 ) {
			wp_safe_redirect( PostTypes::listUrl( PostTypes::COURSE ) );
			exit;
		}

		$renderer = new ViewRenderer();
		$renderer->echo(
			'admin/courses/builder',
			array(
				'renderer' => $renderer,
				'courseId' => $courseId,
			)
		);
	}

	public function renderEdit(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit courses.', 'mint-lms' ) );
		}

		$courseId = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $courseId <= 0 ) {
			wp_safe_redirect( PostTypes::listUrl( PostTypes::COURSE ) );
			exit;
		}

		$renderer = new ViewRenderer();
		$renderer->echo(
			'admin/courses/edit',
			array(
				'renderer' => $renderer,
				'courseId' => $courseId,
			)
		);
	}

	public function renderLessonEdit(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit lessons.', 'mint-lms' ) );
		}

		$lessonId = isset( $_GET['lesson_id'] ) ? absint( $_GET['lesson_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $lessonId <= 0 ) {
			wp_safe_redirect( PostTypes::listUrl( PostTypes::LESSON ) );
			exit;
		}

		$courseId = (int) get_post_meta( $lessonId, \MintLMS\Infrastructure\PostType\PostTypes::META_COURSE_ID, true );
		if ( $courseId > 0 ) {
			$from = isset( $_GET['from'] ) ? sanitize_key( wp_unslash( (string) $_GET['from'] ) ) : 'lessons'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url  = 'admin.php?page=mint-lms-builder&course_id=' . $courseId
				. '&lesson_id=' . $lessonId
				. '&from=' . ( $from ?: 'lessons' );
			if ( isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$url .= '&tab=' . sanitize_key( wp_unslash( (string) $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			if ( isset( $_GET['quiz_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$url .= '&quiz_id=' . absint( $_GET['quiz_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			if ( isset( $_GET['question_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$url .= '&question_id=' . absint( $_GET['question_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			wp_safe_redirect( admin_url( $url ) );
			exit;
		}

		$renderer = new ViewRenderer();
		$renderer->echo(
			'admin/layout-builder',
			array(
				'content' => $renderer->render(
					'admin/courses/_builder-content',
					array(
						'renderer' => $renderer,
						'courseId' => 0,
						'lessonId' => $lessonId,
					)
				),
			)
		);
	}
}
