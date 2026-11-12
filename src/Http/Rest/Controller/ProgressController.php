<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Progress\ProgressService;
use MintLMS\Http\Rest\Response\ApiResponse;

final class ProgressController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private ProgressService $progressService,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/lessons/(?P<id>\d+)/complete',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'complete' ),
					'permission_callback' => array( $this, 'canTrackProgress' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'uncomplete' ),
					'permission_callback' => array( $this, 'canTrackProgress' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/progress',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getCourseProgress' ),
					'permission_callback' => array( $this, 'canTrackProgress' ),
				),
			)
		);
	}

	public function canTrackProgress(): bool {
		return $this->authorization->getCurrentUserId() > 0;
	}

	public function complete( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$lessonId = (int) $request->get_param( 'id' );
			$courseId = $this->nullableInt( $request->get_param( 'course_id' ) );

			$result = $this->progressService->completeLesson( $userId, $lessonId, $courseId );

			return ApiResponse::success(
				array(
					'lesson_id'             => $lessonId,
					'was_newly_completed'   => $result->wasNewlyCompleted,
					'course_just_completed' => $result->courseJustCompleted,
					'progress'              => $result->summary->pctComplete,
				)
			);
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function uncomplete( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$lessonId = (int) $request->get_param( 'id' );
			$courseId = $this->nullableInt( $request->get_param( 'course_id' ) );

			$this->progressService->uncompleteLesson( $userId, $lessonId, $courseId );

			return ApiResponse::success( null, 204 );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function getCourseProgress( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$courseId = (int) $request->get_param( 'id' );

			$progress = $this->progressService->getProgressForUser( $userId, $courseId );

			return ApiResponse::success( $progress->toArray() );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	private function nullableInt( mixed $value ): ?int {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (int) $value;
	}
}
