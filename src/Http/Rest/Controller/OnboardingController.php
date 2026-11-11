<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Http\Rest\Response\ApiResponse;
use MintLMS\Infrastructure\Admin\FirstRunState;

final class OnboardingController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/onboarding/complete',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'complete' ),
					'permission_callback' => array( $this, 'canManageOnboarding' ),
				),
			)
		);
	}

	public function canManageOnboarding(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canCreateCourse( $userId );
	}

	public function complete( \WP_REST_Request $request ): \WP_REST_Response {
		unset( $request );

		FirstRunState::markOnboardingComplete();

		return ApiResponse::success(
			array(
				'redirect' => \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::COURSE ),
			)
		);
	}
}
