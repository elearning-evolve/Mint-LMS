<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

final class MenuRegistrar {

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_filter( 'parent_file', array( $this, 'keepMintMenuOpen' ) );
		add_filter( 'submenu_file', array( $this, 'highlightMintCptSubmenu' ) );
	}

	public function registerMenu(): void {
		add_menu_page(
			__( 'Mint LMS', 'mint-lms' ),
			__( 'Mint LMS', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms',
			array( $this, 'renderDashboard' ),
			'dashicons-welcome-learn-more',
			30
		);

		add_submenu_page(
			'mint-lms',
			__( 'Dashboard', 'mint-lms' ),
			__( 'Dashboard', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms',
			array( $this, 'renderDashboard' )
		);

		// Native WordPress list tables (edit.php) under the Mint LMS menu.
		add_submenu_page(
			'mint-lms',
			__( 'Courses', 'mint-lms' ),
			__( 'Courses', 'mint-lms' ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::COURSE
		);

		add_submenu_page(
			'mint-lms',
			__( 'Lessons', 'mint-lms' ),
			__( 'Lessons', 'mint-lms' ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::LESSON
		);

		add_submenu_page(
			'mint-lms',
			__( 'Quizzes', 'mint-lms' ),
			__( 'Quizzes', 'mint-lms' ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::QUIZ
		);

		add_submenu_page(
			'mint-lms',
			__( 'Questions', 'mint-lms' ),
			__( 'Questions', 'mint-lms' ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::QUESTION
		);

		add_submenu_page(
			'mint-lms',
			__( 'Students', 'mint-lms' ),
			__( 'Students', 'mint-lms' ),
			'enroll_mintlms_students',
			'mint-lms-course-students',
			array( $this, 'renderCourseStudents' )
		);

		add_submenu_page(
			'mint-lms',
			__( 'Reports', 'mint-lms' ),
			__( 'Reports', 'mint-lms' ),
			'view_mintlms_reports',
			'mint-lms-reports',
			array( $this, 'renderReports' )
		);

		add_submenu_page(
			'',
			__( 'Guided first course', 'mint-lms' ),
			__( 'Guided first course', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms-guided-course',
			array( $this, 'renderGuidedCourse' )
		);
	}

	/**
	 * Keep Mint LMS top-level menu active on native CPT list/edit screens.
	 */
	public function keepMintMenuOpen( string $parentFile ): string {
		$postType = $this->currentMintPostType();
		if ( null !== $postType ) {
			return 'mint-lms';
		}

		return $parentFile;
	}

	/**
	 * Highlight the matching Courses/Lessons/Quizzes/Questions submenu item.
	 */
	public function highlightMintCptSubmenu( ?string $submenuFile ): ?string {
		$postType = $this->currentMintPostType();
		if ( null !== $postType ) {
			return 'edit.php?post_type=' . $postType;
		}

		return $submenuFile;
	}

	private function currentMintPostType(): ?string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && in_array( $screen->post_type, PostTypes::all(), true ) ) {
			return $screen->post_type;
		}

		$postType = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $postType, PostTypes::all(), true ) ) {
			return $postType;
		}

		$postId = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $postId > 0 ) {
			$type = get_post_type( $postId );
			if ( is_string( $type ) && in_array( $type, PostTypes::all(), true ) ) {
				return $type;
			}
		}

		return null;
	}

	public function renderDashboard(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Mint LMS.', 'mint-lms' ) );
		}

		if ( ! FirstRunState::isFirstRunComplete() ) {
			include MINTLMS_PATH . 'views/admin/first-run.php';
			return;
		}

		include MINTLMS_PATH . 'views/admin/dashboard.php';
	}

	public function renderCourseStudents(): void {
		if ( ! current_user_can( 'enroll_mintlms_students' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage enrollments.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/courses/students.php';
	}

	public function renderGuidedCourse(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Mint LMS.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/guided-course.php';
	}

	public function renderReports(): void {
		if ( ! current_user_can( 'view_mintlms_reports' ) ) {
			wp_die( esc_html__( 'You do not have permission to view reports.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/reports.php';
	}
}
