<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\CourseStructureService;
use MintLMS\Application\Course\Dto\CreateCourseDto;
use MintLMS\Application\Course\Dto\UpdateCourseDto;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Http\Rest\Response\ApiResponse;
use MintLMS\Infrastructure\Http\RestContentSanitizer;
use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\Setup\PageSettings;
use MintLMS\Infrastructure\WooCommerce\WooCommerceIntegration;

final class CourseController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private CourseService $courseService,
		private CourseStructureService $courseStructureService,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/courses',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list' ),
					'permission_callback' => array( $this, 'canListCourses' ),
					'args'                => $this->listArgs(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'canCreateCourse' ),
					'args'                => $this->createArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
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
			'/courses/(?P<id>\d+)/structure',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'structure' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/publish',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'publish' ),
				'permission_callback' => array( $this, 'canManageCourses' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/woocommerce-product',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'createWooCommerceProduct' ),
				'permission_callback' => array( $this, 'canManageCourses' ),
				'args'                => array(
					'price' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/trash',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'trash' ),
				'permission_callback' => array( $this, 'canManageCourses' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/restore',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'restore' ),
				'permission_callback' => array( $this, 'canManageCourses' ),
			)
		);
	}

	public function canListCourses(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canListCourses( $userId );
	}

	public function canCreateCourse(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canCreateCourse( $userId );
	}

	public function canManageCourses(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canCreateCourse( $userId );
	}

	public function list( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$page   = (int) $request->get_param( 'page' );
			$per    = (int) $request->get_param( 'per_page' );
			$status = $request->get_param( 'status' );

			$statusEnum = null;
			if ( is_string( $status ) && '' !== $status ) {
				$statusEnum = CourseStatus::from( $status );
			}

			$search = $request->get_param( 'search' );
			$search = is_string( $search ) && '' !== trim( $search ) ? trim( $search ) : null;

			$result = $this->courseService->list( $page, $per, $userId, $statusEnum, $search );

			return ApiResponse::success( $result->toArray() );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		} catch ( \ValueError $exception ) {
			return ApiResponse::error( 'invalid_status', 'Invalid course status.', 400 );
		}
	}

	public function create( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();

			$enrollmentParam = $request->get_param( 'enrollment_type' );
			$enrollmentType  = is_string( $enrollmentParam ) && '' !== trim( $enrollmentParam )
				? trim( $enrollmentParam )
				: ( new PageSettings() )->getDefaultEnrollment();

			$dto = new CreateCourseDto(
				(string) $request->get_param( 'title' ),
				$this->nullableString( $request->get_param( 'slug' ) ),
				(string) ( $request->get_param( 'description' ) ?? '' ),
				$this->nullableInt( $request->get_param( 'featured_image_id' ) ),
				EnrollmentType::from( $enrollmentType ),
			);

			$course = $this->courseService->create( $dto, $userId );

			return ApiResponse::success( $this->presentCourse( $course->toArray() ), 201 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function get( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$course = $this->courseService->get( $id, $userId );

			return ApiResponse::success( $this->presentCourse( $course->toArray() ) );
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

			[ $updateFeaturedImage, $featuredImageId ] = $this->featuredImageFromRequest( $request );

			$dto = new UpdateCourseDto(
				$request->offsetExists( 'title' ) ? (string) $request->get_param( 'title' ) : null,
				$request->offsetExists( 'slug' ) ? $this->nullableString( $request->get_param( 'slug' ) ) : null,
				$request->offsetExists( 'description' ) ? (string) $request->get_param( 'description' ) : null,
				$updateFeaturedImage,
				$featuredImageId,
				$request->offsetExists( 'enrollment_type' )
					? EnrollmentType::from( (string) $request->get_param( 'enrollment_type' ) )
					: null,
				$request->offsetExists( 'status' )
					? CourseStatus::from( (string) $request->get_param( 'status' ) )
					: null,
				$request->has_param( 'email_on_publish' ) ? (bool) $request->get_param( 'email_on_publish' ) : null,
				$request->has_param( 'student_complete' ) ? (bool) $request->get_param( 'student_complete' ) : null,
				$request->has_param( 'certificate' ) ? (bool) $request->get_param( 'certificate' ) : null,
				$request->has_param( 'progression' ) ? (string) $request->get_param( 'progression' ) : null,
				$request->has_param( 'expire_access' ) ? (bool) $request->get_param( 'expire_access' ) : null,
				$request->has_param( 'expire_access_days' ) ? (int) $request->get_param( 'expire_access_days' ) : null,
				$request->has_param( 'prerequisites_enabled' ) ? (bool) $request->get_param( 'prerequisites_enabled' ) : null,
				$request->has_param( 'prerequisite_course_ids' )
					? array_map( 'intval', (array) $request->get_param( 'prerequisite_course_ids' ) )
					: null,
				$request->has_param( 'prerequisite_compare' ) ? (string) $request->get_param( 'prerequisite_compare' ) : null,
				$request->has_param( 'access_start_at' ) ? $this->nullableString( $request->get_param( 'access_start_at' ) ) : null,
				$request->has_param( 'access_start_at' ) && '' === (string) $request->get_param( 'access_start_at' ),
				$request->has_param( 'access_end_at' ) ? $this->nullableString( $request->get_param( 'access_end_at' ) ) : null,
				$request->has_param( 'access_end_at' ) && '' === (string) $request->get_param( 'access_end_at' ),
				$request->has_param( 'seat_limit' ) ? (int) $request->get_param( 'seat_limit' ) : null,
				$request->has_param( 'materials_enabled' ) ? (bool) $request->get_param( 'materials_enabled' ) : null,
				$request->has_param( 'materials_content' ) ? (string) $request->get_param( 'materials_content' ) : null,
				$request->has_param( 'lesson_list_visibility' ) ? (string) $request->get_param( 'lesson_list_visibility' ) : null,
				$request->has_param( 'pagination_enabled' ) ? (bool) $request->get_param( 'pagination_enabled' ) : null,
				$request->has_param( 'lessons_per_page' ) ? (int) $request->get_param( 'lessons_per_page' ) : null,
				$request->has_param( 'groups_per_page' ) ? (int) $request->get_param( 'groups_per_page' ) : null,
				$request->has_param( 'completion_redirect' ) ? (string) $request->get_param( 'completion_redirect' ) : null,
				$request->has_param( 'points_on_complete' ) ? (int) $request->get_param( 'points_on_complete' ) : null,
			);

			$course = $this->courseService->update( $id, $dto, $userId );

			return ApiResponse::success( $this->presentCourse( $course->toArray() ) );
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

			$this->courseService->delete( $id, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function structure( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$structure = $this->courseStructureService->getStructure( $id, $userId );

			return ApiResponse::success( $this->presentStructure( $structure->toArray() ) );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function publish( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$course = $this->courseService->publish( $id, $userId );

			return ApiResponse::success( $this->presentCourse( $course->toArray() ) );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function trash( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$course = $this->courseService->trash( $id, $userId );

			return ApiResponse::success( $this->presentCourse( $course->toArray() ) );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function restore( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$course = $this->courseService->restore( $id, $userId );

			return ApiResponse::success( $this->presentCourse( $course->toArray() ) );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function createWooCommerceProduct( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! WooCommerceIntegration::isAvailable() ) {
			return ApiResponse::error( 'woocommerce_inactive', 'WooCommerce is not active.', 400 );
		}

		$integration = WooCommerceIntegration::instance();

		if ( null === $integration ) {
			return ApiResponse::error( 'woocommerce_inactive', 'WooCommerce integration is not loaded.', 400 );
		}

		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			// Ensure the actor can manage this course before creating a product.
			$this->courseService->get( $id, $userId );

			$result = $integration->productSync()->createOrUpdateForCourse(
				$id,
				(string) $request->get_param( 'price' ),
				$userId
			);

			$course = $this->courseService->get( $id, $userId );

			return ApiResponse::success(
				array_merge(
					$this->presentCourse( $course->toArray() ),
					$result
				)
			);
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	/**
	 * @param array<string, mixed> $course
	 * @return array<string, mixed>
	 */
	private function presentCourse( array $course ): array {
		$course = $this->withCommerce( $course );

		$imageId = isset( $course['featuredImageId'] ) ? (int) $course['featuredImageId'] : 0;
		$url     = '';

		if ( $imageId > 0 ) {
			$resolved = wp_get_attachment_image_url( $imageId, 'large' );
			if ( ! is_string( $resolved ) || '' === $resolved ) {
				$resolved = wp_get_attachment_url( $imageId );
			}
			$url = is_string( $resolved ) ? $resolved : '';
		}

		$course['featuredImageUrl'] = $url;

		$courseId = isset( $course['id'] ) ? (int) $course['id'] : 0;
		if ( $courseId > 0 ) {
			$post = get_post( $courseId );
			if ( $post instanceof \WP_Post ) {
				if ( empty( $course['authorName'] ) ) {
					$author = get_userdata( (int) $post->post_author );
					$course['authorName'] = $author instanceof \WP_User
						? (string) $author->user_nicename
						: '';
					$course['instructor'] = $course['authorName'];
				}

				$publishedTs = 'publish' === $post->post_status
					? (int) get_post_time( 'U', true, $post )
					: (int) get_post_modified_time( 'U', true, $post );
				$course['publishedAt'] = $publishedTs > 0
					? gmdate( 'c', $publishedTs )
					: (string) ( $course['updatedAt'] ?? '' );

				$revisions = wp_get_post_revisions( $courseId, array( 'numberposts' => -1 ) );
				$course['revisionCount'] = is_array( $revisions ) ? count( $revisions ) : 0;
			}
		}

		if ( ! isset( $course['revisionCount'] ) ) {
			$course['revisionCount'] = 0;
		}

		return $course;
	}

	/**
	 * @param array<string, mixed> $structure
	 * @return array<string, mixed>
	 */
	private function presentStructure( array $structure ): array {
		if ( ! isset( $structure['sections'] ) || ! is_array( $structure['sections'] ) ) {
			return $structure;
		}

		foreach ( $structure['sections'] as $sectionIndex => $section ) {
			if ( ! is_array( $section ) || ! isset( $section['lessons'] ) || ! is_array( $section['lessons'] ) ) {
				continue;
			}

			foreach ( $section['lessons'] as $lessonIndex => $lesson ) {
				if ( ! is_array( $lesson ) ) {
					continue;
				}

				$imageId = isset( $lesson['featuredImageId'] ) ? (int) $lesson['featuredImageId'] : 0;
				$url     = '';

				if ( $imageId > 0 ) {
					$resolved = wp_get_attachment_image_url( $imageId, 'large' );
					if ( ! is_string( $resolved ) || '' === $resolved ) {
						$resolved = wp_get_attachment_url( $imageId );
					}
					$url = is_string( $resolved ) ? $resolved : '';
				}

				$structure['sections'][ $sectionIndex ]['lessons'][ $lessonIndex ]['featuredImageUrl'] = $url;

				$lessonId = isset( $lesson['id'] ) ? (int) $lesson['id'] : 0;
				$wpStatus = $lessonId > 0 ? (string) get_post_status( $lessonId ) : 'draft';
				$structure['sections'][ $sectionIndex ]['lessons'][ $lessonIndex ]['status'] = match ( $wpStatus ) {
					'publish' => 'published',
					'private', PostTypes::STATUS_ARCHIVED => 'archived',
					'trash' => 'trashed',
					default => 'draft',
				};
			}
		}

		return $structure;
	}

	/**
	 * @return array{0: bool, 1: ?int}
	 */
	private function featuredImageFromRequest( \WP_REST_Request $request ): array {
		$json = $request->get_json_params();

		if ( is_array( $json ) && array_key_exists( 'featured_image_id', $json ) ) {
			return array( true, $this->nullableInt( $json['featured_image_id'] ) );
		}

		if ( $request->has_param( 'featured_image_id' ) ) {
			return array( true, $this->nullableInt( $request->get_param( 'featured_image_id' ) ) );
		}

		return array( false, null );
	}

	/**
	 * @param array<string, mixed> $course
	 * @return array<string, mixed>
	 */
	private function withCommerce( array $course ): array {
		$course['woocommerceAvailable'] = WooCommerceIntegration::isAvailable() && null !== WooCommerceIntegration::instance();
		$course['productId']            = 0;
		$course['productUrl']           = '';
		$course['editUrl']              = '';
		$course['productPrice']         = '';
		$course['purchaseUrl']          = '';

		if ( ! $course['woocommerceAvailable'] || ! isset( $course['id'] ) ) {
			return $course;
		}

		$sync      = WooCommerceIntegration::instance()->productSync();
		$courseId  = (int) $course['id'];
		$productId = $sync->findProductIdForCourse( $courseId );

		if ( $productId <= 0 ) {
			return $course;
		}

		$course['productId']    = $productId;
		$course['productUrl']   = (string) get_permalink( $productId );
		$course['editUrl']      = (string) get_edit_post_link( $productId, 'raw' );
		$course['productPrice'] = $sync->getProductPrice( $productId );
		$course['purchaseUrl']  = $sync->getPurchaseUrl( $courseId );

		return $course;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function listArgs(): array {
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
			'status'   => array(
				'type'              => 'string',
				'enum'              => array( 'draft', 'published', 'archived', 'trashed' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search'   => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function createArgs(): array {
		return array(
			'title'             => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'slug'              => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
			),
			'description'       => array(
				'type'              => 'string',
				'sanitize_callback' => array( RestContentSanitizer::class, 'richText' ),
			),
			'featured_image_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'enrollment_type'   => array(
				'type'              => 'string',
				'default'           => 'free',
				'enum'              => array( 'open', 'free', 'manual', 'paid' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function updateArgs(): array {
		return array(
			'title'             => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'slug'              => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
			),
			'description'       => array(
				'type'              => 'string',
				'sanitize_callback' => array( RestContentSanitizer::class, 'richText' ),
			),
			'featured_image_id' => array(
				'validate_callback' => static function ( $value ): bool {
					return null === $value || is_numeric( $value ) || '' === $value;
				},
				'sanitize_callback' => static function ( $value ): ?int {
					if ( null === $value || '' === $value ) {
						return null;
					}

					$id = absint( $value );

					return $id > 0 ? $id : null;
				},
			),
			'enrollment_type'   => array(
				'type'              => 'string',
				'enum'              => array( 'open', 'free', 'manual', 'paid' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'            => array(
				'type'              => 'string',
				'enum'              => array( 'draft', 'published', 'archived', 'trashed' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email_on_publish'  => array(
				'type'              => 'boolean',
				'sanitize_callback' => static fn( $value ): bool => (bool) $value,
			),
			'student_complete'  => array(
				'type'              => 'boolean',
				'sanitize_callback' => static fn( $value ): bool => (bool) $value,
			),
			'certificate'       => array(
				'type'              => 'boolean',
				'sanitize_callback' => static fn( $value ): bool => (bool) $value,
			),
			'progression'       => array(
				'type'              => 'string',
				'enum'              => array( 'linear', 'freeform' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'expire_access'     => array(
				'type'              => 'boolean',
				'sanitize_callback' => static fn( $value ): bool => (bool) $value,
			),
			'expire_access_days' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'prerequisites_enabled' => array(
				'type'              => 'boolean',
				'sanitize_callback' => static fn( $value ): bool => (bool) $value,
			),
			'prerequisite_course_ids' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
			'prerequisite_compare' => array(
				'type'              => 'string',
				'enum'              => array( 'ANY', 'ALL' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'access_start_at'   => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'access_end_at'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'seat_limit'        => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
	}

	private function nullableString( mixed $value ): ?string {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (string) $value;
	}

	private function nullableInt( mixed $value ): ?int {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (int) $value;
	}
}
