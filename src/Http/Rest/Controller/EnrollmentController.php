<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Enrollment\EnrollmentService;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Http\Rest\Response\ApiResponse;

final class EnrollmentController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private EnrollmentService $enrollmentService,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/students',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'listStudents' ),
				'permission_callback' => array( $this, 'isAuthenticated' ),
				'args'                => $this->paginationArgs(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/enroll',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'enroll' ),
				'permission_callback' => array( $this, 'isAuthenticated' ),
				'args'                => $this->enrollArgs(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/enrollments/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'cancel' ),
				'permission_callback' => array( $this, 'isAuthenticated' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/me/courses',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'myCourses' ),
				'permission_callback' => array( $this, 'isAuthenticated' ),
				'args'                => $this->paginationArgs(),
			)
		);
	}

	public function isAuthenticated(): bool {
		return $this->authorization->getCurrentUserId() > 0;
	}

	public function listStudents( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$courseId = (int) $request->get_param( 'id' );
			$page     = (int) $request->get_param( 'page' );
			$per      = (int) $request->get_param( 'per_page' );

			$result = $this->enrollmentService->listStudentsForCourse( $courseId, $page, $per, $userId );

			return ApiResponse::success( $result->toArray() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function enroll( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$actorUserId = $this->authorization->getCurrentUserId();
			$courseId    = (int) $request->get_param( 'id' );
			$targetUser  = $request->get_param( 'user_id' );
			$userId      = null !== $targetUser && '' !== $targetUser ? (int) $targetUser : $actorUserId;

			$enrollment = $this->enrollmentService->enroll( $userId, $courseId, $actorUserId );
			$payload    = $enrollment->toArray();
			$user       = get_userdata( $userId );

			if ( $user instanceof \WP_User ) {
				$payload['displayName'] = (string) $user->display_name;
				$payload['email']       = (string) $user->user_email;
			} else {
				$payload['displayName'] = sprintf(
					/* translators: %d: user ID */
					__( 'User #%d', 'mint-lms' ),
					$userId
				);
				$payload['email'] = '';
			}

			return ApiResponse::success( $payload, 201 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function cancel( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId       = $this->authorization->getCurrentUserId();
			$enrollmentId = (int) $request->get_param( 'id' );

			$this->enrollmentService->cancel( $enrollmentId, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function myCourses( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$page   = (int) $request->get_param( 'page' );
			$per    = (int) $request->get_param( 'per_page' );

			$result = $this->enrollmentService->listCoursesForUser( $userId, $page, $per );

			return ApiResponse::success( $result->toArray() );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function paginationArgs(): array {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => 20,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function enrollArgs(): array {
		return array(
			'user_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
	}
}
