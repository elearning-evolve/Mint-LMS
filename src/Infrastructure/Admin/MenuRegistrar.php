<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

final class MenuRegistrar {

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_action( 'admin_head', array( $this, 'printMenuIconStyles' ) );
		add_filter( 'parent_file', array( $this, 'keepMintMenuOpen' ) );
		add_filter( 'submenu_file', array( $this, 'highlightMintCptSubmenu' ) );
	}

	/**
	 * Submenu icon sizing in wp-admin (outside #mint-lms-root).
	 */
	public function printMenuIconStyles(): void {
		echo '<style id="mint-lms-menu-icons">'
			. '#adminmenu .mint-lms-menu-icon{display:inline-flex;width:16px;height:16px;margin-right:6px;vertical-align:-3px;align-items:center;justify-content:center;flex:none;color:inherit;}'
			. '#adminmenu .mint-lms-menu-icon svg{width:16px;height:16px;display:block;color:inherit;}'
			. '#adminmenu .mint-lms-menu-label{vertical-align:middle;}'
			. '#adminmenu #toplevel_page_mint-lms .wp-submenu a{display:flex;align-items:center;}'
			. '</style>';
	}

	/**
	 * Model Mint mark for wp-admin left menu (lime tile + list lines).
	 */
	private static function menuIconDataUri(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" fill="none">'
			. '<rect width="36" height="36" rx="10" fill="#98FBCB"/>'
			. '<path d="M9 11.5h18M9 18h12M9 24.5h7.5" stroke="#0F0E1A" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>'
			. '</svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public function registerMenu(): void {
		add_menu_page(
			__( 'Mint LMS', 'mint-lms' ),
			__( 'Mint LMS', 'mint-lms' ),
			'edit_mintlms_courses',
			'mint-lms',
			array( $this, 'renderDashboard' ),
			self::menuIconDataUri(),
			30
		);

		add_submenu_page(
			'mint-lms',
			__( 'Dashboard', 'mint-lms' ),
			MenuIcons::labeled( 'dashboard', __( 'Dashboard', 'mint-lms' ) ),
			'edit_mintlms_courses',
			'mint-lms',
			array( $this, 'renderDashboard' )
		);

		// Native WordPress list tables (edit.php) under the Mint LMS menu.
		add_submenu_page(
			'mint-lms',
			__( 'Courses', 'mint-lms' ),
			MenuIcons::labeled( 'courses', __( 'Courses', 'mint-lms' ) ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::COURSE
		);

		add_submenu_page(
			'mint-lms',
			__( 'Lessons', 'mint-lms' ),
			MenuIcons::labeled( 'lessons', __( 'Lessons', 'mint-lms' ) ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::LESSON
		);

		add_submenu_page(
			'mint-lms',
			__( 'Quizzes', 'mint-lms' ),
			MenuIcons::labeled( 'quizzes', __( 'Quizzes', 'mint-lms' ) ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::QUIZ
		);

		add_submenu_page(
			'mint-lms',
			__( 'Questions', 'mint-lms' ),
			MenuIcons::labeled( 'questions', __( 'Questions', 'mint-lms' ) ),
			'edit_mintlms_courses',
			'edit.php?post_type=' . PostTypes::QUESTION
		);

		add_submenu_page(
			'mint-lms',
			__( 'Essay grading', 'mint-lms' ),
			MenuIcons::labeled( 'questions', __( 'Essay grading', 'mint-lms' ) ),
			'edit_mintlms_courses',
			'mint-lms-essay-grading',
			array( $this, 'renderEssayGrading' )
		);

		add_submenu_page(
			'mint-lms',
			__( 'Students', 'mint-lms' ),
			MenuIcons::labeled( 'students', __( 'Students', 'mint-lms' ) ),
			'enroll_mintlms_students',
			'mint-lms-course-students',
			array( $this, 'renderCourseStudents' )
		);

		add_submenu_page(
			'mint-lms',
			__( 'Reports', 'mint-lms' ),
			MenuIcons::labeled( 'reports', __( 'Reports', 'mint-lms' ) ),
			'view_mintlms_reports',
			'mint-lms-reports',
			array( $this, 'renderReports' )
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

		$userId = get_current_user_id();

		// First-time welcome: Admin = 3 steps; Manager/Instructor = 2-step role variants.
		if ( FirstRunState::shouldShowWelcome( $userId ) ) {
			include MINTLMS_PATH . 'views/admin/first-run.php';
			return;
		}

		include MINTLMS_PATH . 'views/admin/dashboard.php';
	}

	public function renderEssayGrading(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to grade essays.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/essays/grading.php';
	}

	public function renderCourseStudents(): void {
		if ( ! current_user_can( 'enroll_mintlms_students' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage enrollments.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/courses/students.php';
	}

	public function renderReports(): void {
		if ( ! current_user_can( 'view_mintlms_reports' ) ) {
			wp_die( esc_html__( 'You do not have permission to view reports.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/reports.php';
	}
}
