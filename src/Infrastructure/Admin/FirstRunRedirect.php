<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

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

	public function handleStartGuidedAction(): void {
		if ( ! isset( $_GET['mintlms_action'] ) || self::START_GUIDED_ACTION !== $_GET['mintlms_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Mint LMS.', 'mint-lms' ) );
		}

		check_admin_referer( self::START_GUIDED_ACTION );

		FirstRunState::markFirstRunStarted();

		wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-guided-course' ) );
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

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$onCoursesList = $this->isCoursesListScreen();

		if ( 'mint-lms-guided-course' === $page ) {
			if ( FirstRunState::isGuidedCourseComplete() ) {
				wp_safe_redirect( PostTypes::listUrl( PostTypes::COURSE ) );
				exit;
			}

			return;
		}

		if ( 'mint-lms' !== $page && ! $onCoursesList ) {
			return;
		}

		if ( ! FirstRunState::isFirstRunComplete() ) {
			if ( $onCoursesList ) {
				wp_safe_redirect( admin_url( 'admin.php?page=mint-lms' ) );
				exit;
			}

			return;
		}

		if ( FirstRunState::shouldOfferGuidedFlow( get_current_user_id() ) && 'mint-lms' === $page ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mint-lms-guided-course' ) );
			exit;
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
