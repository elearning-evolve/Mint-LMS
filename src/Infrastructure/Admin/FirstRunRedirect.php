<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Admin\SampleCourseSeeder;
use MintLMS\Infrastructure\PostType\PostTypes;

final class FirstRunRedirect {

	public const ACTIVATION_REDIRECT_TRANSIENT = 'mintlms_activation_redirect';
	public const START_GUIDED_ACTION           = 'mintlms_start_guided';
	public const SKIP_FIRST_RUN_ACTION         = 'mintlms_skip_first_run';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'handleActivationRedirect' ) );
		add_action( 'admin_init', array( $this, 'handleStartGuidedAction' ) );
		add_action( 'admin_init', array( $this, 'handleSkipFirstRunAction' ) );
		add_action( 'admin_init', array( $this, 'handleGuidedFlowRedirect' ) );
	}

	public function handleActivationRedirect(): void {
		if ( ! get_transient( self::ACTIVATION_REDIRECT_TRANSIENT ) ) {
			return;
		}

		delete_transient( self::ACTIVATION_REDIRECT_TRANSIENT );

		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			return;
		}

		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core multisite activation flag.
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mint-lms' ) );
		exit;
	}

	/**
	 * Welcome "Create your first course" entry point.
	 * Admin → sample course + builder. Everyone else → Untitled Course editor.
	 * (Guided wizard removed — Welcome + empty dashboard cover first-run UX.)
	 */
	public function handleStartGuidedAction(): void {
		if ( ! isset( $_GET['mintlms_action'] ) || self::START_GUIDED_ACTION !== $_GET['mintlms_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Mint LMS.', 'mint-lms' ) );
		}

		check_admin_referer( self::START_GUIDED_ACTION );

		// Admin only: seed a full sample course and open Course Builder.
		if ( current_user_can( 'manage_options' ) ) {
			try {
				$courseId = SampleCourseSeeder::make()->seedForUser( get_current_user_id() );
			} catch ( \Throwable $e ) {
				wp_die(
					esc_html(
						sprintf(
							/* translators: %s: error message */
							__( 'Could not create the sample course: %s', 'mint-lms' ),
							$e->getMessage()
						)
					)
				);
			}

			FirstRunState::markOnboardingComplete();

			wp_safe_redirect(
				admin_url( 'admin.php?page=mint-lms-builder&course_id=' . absint( $courseId ) )
			);
			exit;
		}

		FirstRunState::markOnboardingComplete();

		wp_safe_redirect( CourseBuilderPage::titledCourseUrl() );
		exit;
	}

	public function handleSkipFirstRunAction(): void {
		if ( ! isset( $_GET['mintlms_action'] ) || self::SKIP_FIRST_RUN_ACTION !== $_GET['mintlms_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Mint LMS.', 'mint-lms' ) );
		}

		check_admin_referer( self::SKIP_FIRST_RUN_ACTION );

		FirstRunState::markOnboardingComplete();

		wp_safe_redirect( PostTypes::listUrl( PostTypes::COURSE ) );
		exit;
	}

	public function handleGuidedFlowRedirect(): void {
		if ( ! is_admin() || ! current_user_can( 'edit_mintlms_courses' ) ) {
			return;
		}

		$page          = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$onCoursesList = $this->isCoursesListScreen();

		// Legacy guided wizard URL — always send users back to the dashboard.
		if ( 'mint-lms-guided-course' === $page ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms' ) );
			exit;
		}

		$userId = get_current_user_id();

		if ( 'mint-lms' !== $page && ! $onCoursesList ) {
			return;
		}

		// Preview mode must not bounce Courses → Dashboard (would drop the query flag).
		if ( FirstRunState::isWelcomePreviewRequested() ) {
			return;
		}

		// Keep first-time users on the shared Admin/Instructor welcome until they act.
		if ( FirstRunState::shouldShowWelcome( $userId ) ) {
			if ( $onCoursesList ) {
				wp_safe_redirect( admin_url( 'admin.php?page=mint-lms' ) );
				exit;
			}
		}
	}

	private function isCoursesListScreen(): bool {
		// Avoid global $pagenow — detect CPT list via request (admin_init is before get_current_screen()).
		$script = isset( $_SERVER['PHP_SELF'] ) ? basename( sanitize_text_field( wp_unslash( (string) $_SERVER['PHP_SELF'] ) ) ) : '';
		if ( 'edit.php' !== $script ) {
			return false;
		}

		$postType = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return PostTypes::COURSE === $postType;
	}

	public static function startGuidedUrl(): string {
		return wp_nonce_url(
			admin_url( 'admin.php?page=mint-lms&mintlms_action=' . self::START_GUIDED_ACTION ),
			self::START_GUIDED_ACTION
		);
	}

	public static function skipFirstRunUrl(): string {
		return wp_nonce_url(
			admin_url( 'admin.php?page=mint-lms&mintlms_action=' . self::SKIP_FIRST_RUN_ACTION ),
			self::SKIP_FIRST_RUN_ACTION
		);
	}
}
