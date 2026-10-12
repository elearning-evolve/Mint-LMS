<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Setup\PageSettings;
use MintLMS\Infrastructure\PostType\PostTypes;

final class AssetLoader {

	private const ENQUEUE_PRIORITY = 999;

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ), self::ENQUEUE_PRIORITY );
		add_action( 'admin_head', array( $this, 'hideWordPressChrome' ) );
		add_filter( 'admin_body_class', array( $this, 'focusModeBodyClass' ) );
	}

	public function enqueueAdminAssets( string $hookSuffix ): void {
		if ( ! str_contains( $hookSuffix, 'mint-lms' ) ) {
			return;
		}

		$this->enqueueStyles( 'mint-lms-admin' );
		$this->enqueueScripts( 'mint-lms-admin' );

		if ( str_contains( $hookSuffix, 'mint-lms-builder' ) || str_contains( $hookSuffix, 'mint-lms-course-edit' ) || str_contains( $hookSuffix, 'mint-lms-lesson-edit' ) ) {
			wp_enqueue_media();
		}

		if ( str_contains( $hookSuffix, 'mint-lms-builder' ) || str_contains( $hookSuffix, 'mint-lms-lesson-edit' ) ) {
			wp_enqueue_editor();
		}

		$restBase     = esc_url_raw( rest_url( 'mintlms/v1' ) );
		$pageSettings = new PageSettings();

		wp_localize_script(
			'mint-lms-admin',
			'mintLmsAdmin',
			array(
				'restBase'           => $restBase,
				'mediaBase'          => esc_url_raw( rest_url( 'wp/v2/media' ) ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'defaultEnrollment'  => $pageSettings->getDefaultEnrollment(),
				'urls'               => array(
					'courses'      => PostTypes::listUrl( PostTypes::COURSE ),
					'lessons'      => PostTypes::listUrl( PostTypes::LESSON ),
					'quizzes'      => PostTypes::listUrl( PostTypes::QUIZ ),
					'questions'    => PostTypes::listUrl( PostTypes::QUESTION ),
					'builder'      => admin_url( 'admin.php?page=mint-lms-builder' ),
					'editQuiz'     => admin_url( 'admin.php?page=mint-lms-edit-quiz' ),
					'edit'         => admin_url( 'admin.php?page=mint-lms-course-edit' ),
					'lessonEdit'   => admin_url( 'admin.php?page=mint-lms-lesson-edit' ),
					'newCourse'    => CourseBuilderPage::newCourseUrl(),
					'newLesson'    => admin_url( 'post-new.php?post_type=' . PostTypes::LESSON ),
					'newQuiz'      => admin_url( 'post-new.php?post_type=' . PostTypes::QUIZ ),
					'newQuestion'  => admin_url( 'post-new.php?post_type=' . PostTypes::QUESTION ),
					'dashboard'    => admin_url( 'admin.php?page=mint-lms' ),
					'playerPage'   => $pageSettings->getPlayerUrl(),
					'catalogPage'  => $pageSettings->getCatalogUrl(),
					'guidedCourse' => admin_url( 'admin.php?page=mint-lms-guided-course' ),
				),
			)
		);

		if ( str_contains( $hookSuffix, 'mint-lms-guided-course' ) ) {
			wp_localize_script(
				'mint-lms-admin',
				'mintLmsGuided',
				array(
					'restBase'    => $restBase,
					'nonce'       => wp_create_nonce( 'wp_rest' ),
					'coursesUrl'  => PostTypes::listUrl( PostTypes::COURSE ),
					'builderBase' => admin_url( 'admin.php?page=mint-lms-builder&course_id=' ),
					'i18n'        => array(
						'step'            => __( 'Step', 'mint-lms' ),
						'stepTitles'      => array(
							1 => __( 'Name your course', 'mint-lms' ),
							2 => __( 'Add your first lesson', 'mint-lms' ),
							3 => __( 'Publish your course', 'mint-lms' ),
						),
						'titleRequired'   => __( 'Course title is required.', 'mint-lms' ),
						'sectionRequired' => __( 'Lesson group and lesson titles are required.', 'mint-lms' ),
						'genericError'    => __( 'Something went wrong. Please try again.', 'mint-lms' ),
					),
				)
			);
		}
	}

	/**
	 * LearnDash-style focus mode: hide the WP admin sidebar on builder + course edit.
	 */
	public function focusModeBodyClass( string $classes ): string {
		if ( $this->isFocusModeScreen() ) {
			$classes .= ' mint-lms-focus-mode';
		}

		return $classes;
	}

	public function hideWordPressChrome(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || ! str_contains( (string) $screen->id, 'mint-lms' ) ) {
			return;
		}

		echo '<style>
			.mint-lms-admin-wrap{margin:0;padding:0}
			.mint-lms-admin-wrap>.notice{display:none}
			#wpbody-content{padding-bottom:0}
		';

		if ( $this->isFocusModeScreen() ) {
			echo '
			/* LearnDash-style: hide WP admin menu on course builder / edit */
			body.mint-lms-focus-mode #adminmenuback,
			body.mint-lms-focus-mode #adminmenuwrap,
			body.mint-lms-focus-mode #adminmenumain {
				display: none !important;
			}
			body.mint-lms-focus-mode #wpcontent,
			body.mint-lms-focus-mode #wpfooter {
				margin-left: 0 !important;
			}
			body.mint-lms-focus-mode #wpfooter {
				display: none !important;
			}
			body.mint-lms-focus-mode #wpbody-content {
				padding-bottom: 0 !important;
			}
			body.mint-lms-focus-mode .mint-lms-admin-wrap.wrap {
				margin-left: 0;
				margin-right: 0;
			}
			';
		}

		echo '</style>';
	}

	private function isFocusModeScreen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen ) {
			return false;
		}

		$id = (string) $screen->id;

		return str_contains( $id, 'mint-lms-builder' )
			|| str_contains( $id, 'mint-lms-course-edit' )
			|| str_contains( $id, 'mint-lms-lesson-edit' );
	}

	private function enqueueStyles( string $handle ): void {
		wp_enqueue_style(
			$handle,
			MINTLMS_URL . 'assets/dist/main.css',
			array(),
			MINTLMS_VERSION
		);

		wp_add_inline_style(
			$handle,
			'#mint-lms-root.mint-lms-ui,#mint-lms-root.mint-lms-student{isolation:isolate}'
		);
	}

	private function enqueueScripts( string $handle ): void {
		$script = MINTLMS_PATH . 'assets/dist/main.js';
		$version = is_readable( $script ) ? (string) filemtime( $script ) : MINTLMS_VERSION;

		wp_enqueue_script(
			$handle,
			MINTLMS_URL . 'assets/dist/main.js',
			array(),
			$version,
			true
		);
	}
}
