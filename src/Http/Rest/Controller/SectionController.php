<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Section\Dto\CreateSectionDto;
use MintLMS\Application\Section\Dto\ReorderSectionsDto;
use MintLMS\Application\Section\Dto\UpdateSectionDto;
use MintLMS\Application\Section\SectionService;
use MintLMS\Http\Rest\Response\ApiResponse;

final class SectionController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private SectionService $sectionService,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/sections',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->createArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/sections/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->updateArgs(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/sections/reorder',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reorder' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->reorderArgs(),
				),
			)
		);
	}

	public function canManageCourses(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canCreateCourse( $userId );
	}

	public function create( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$courseId = (int) $request->get_param( 'id' );

			$dto = new CreateSectionDto( (string) $request->get_param( 'title' ) );

			$section = $this->sectionService->create( $courseId, $dto, $userId );

			return ApiResponse::success( $section->toArray(), 201 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function update( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$dto = new UpdateSectionDto(
				$request->offsetExists( 'title' ) ? (string) $request->get_param( 'title' ) : null,
			);

			$section = $this->sectionService->update( $id, $dto, $userId );

			return ApiResponse::success( $section->toArray() );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function delete( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$this->sectionService->delete( $id, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function reorder( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$courseId = (int) $request->get_param( 'id' );
			$ids      = $request->get_param( 'ids' );

			$dto = new ReorderSectionsDto( is_array( $ids ) ? array_map( 'intval', $ids ) : array() );

			$this->sectionService->reorder( $courseId, $dto, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function createArgs(): array {
		return array(
			'title' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function updateArgs(): array {
		return array(
			'title' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function reorderArgs(): array {
		return array(
			'ids' => array(
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => static function ( mixed $value ): bool {
					if ( ! is_array( $value ) ) {
						return false;
					}

					foreach ( $value as $id ) {
						if ( ! is_numeric( $id ) || (int) $id <= 0 ) {
							return false;
						}
					}

					return true;
				},
				'sanitize_callback' => static function ( mixed $value ): array {
					if ( ! is_array( $value ) ) {
						return array();
					}

					return array_map( 'absint', $value );
				},
			),
		);
	}
}
