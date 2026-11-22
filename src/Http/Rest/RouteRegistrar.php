<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest;

defined( 'ABSPATH' ) || exit;

use MintLMS\Http\Rest\Controller\ContentPostListController;
use MintLMS\Http\Rest\Controller\CourseController;
use MintLMS\Http\Rest\Controller\EnrollmentController;
use MintLMS\Http\Rest\Controller\LessonController;
use MintLMS\Http\Rest\Controller\OnboardingController;
use MintLMS\Http\Rest\Controller\ProgressController;
use MintLMS\Http\Rest\Controller\QuizController;
use MintLMS\Http\Rest\Controller\SectionController;
use MintLMS\Http\Rest\Controller\UserController;
use MintLMS\Http\Rest\Response\ApiResponse;
use MintLMS\Infrastructure\Ui\MintUi;

final class RouteRegistrar {

	public function __construct(
		private CourseController $courseController,
		private SectionController $sectionController,
		private LessonController $lessonController,
		private EnrollmentController $enrollmentController,
		private ProgressController $progressController,
		private QuizController $quizController,
		private OnboardingController $onboardingController,
		private UserController $userController,
		private ContentPostListController $contentPostListController,
	) {
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	public function registerRoutes(): void {
		$this->courseController->registerRoutes();
		$this->sectionController->registerRoutes();
		$this->lessonController->registerRoutes();
		$this->enrollmentController->registerRoutes();
		$this->progressController->registerRoutes();
		$this->quizController->registerRoutes();
		$this->onboardingController->registerRoutes();
		$this->userController->registerRoutes();
		$this->contentPostListController->registerRoutes();

		register_rest_route(
			'mintlms/v1',
			'/user-pref',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleUserPref' ),
				'permission_callback' => static fn () => current_user_can( 'edit_mintlms_courses' ),
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function handleUserPref( \WP_REST_Request $request ): \WP_REST_Response {
		$userId = get_current_user_id();
		$key    = sanitize_key( (string) $request->get_param( 'key' ) );
		$value  = sanitize_text_field( (string) $request->get_param( 'value' ) );

		$allowed = array( 'mint_lms_show_details' );

		if ( ! in_array( $key, $allowed, true ) ) {
			return ApiResponse::error( 'validation_error', 'Invalid key', 400 );
		}

		MintUi::setShowDetailsPreference( $userId, '1' === $value );

		return ApiResponse::success( array( 'ok' => true ) );
	}
}
