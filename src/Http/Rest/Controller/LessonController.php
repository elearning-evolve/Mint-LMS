<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Lesson\Dto\CreateLessonDto;
use MintLMS\Application\Lesson\Dto\ReorderLessonsDto;
use MintLMS\Application\Lesson\Dto\UpdateLessonDto;
use MintLMS\Application\Lesson\LessonService;
use MintLMS\Infrastructure\Http\RestContentSanitizer;
use MintLMS\Http\Rest\Response\ApiResponse;
use MintLMS\Infrastructure\PostType\PostTypes;

final class LessonController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private LessonService $lessonService,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/sections/(?P<id>\d+)/lessons',
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
			'/courses/(?P<id>\d+)/lessons',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'createForCourse' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => array_merge(
						$this->createArgs(),
						array(
							'title'      => array(
								'type'              => 'string',
								'required'          => false,
								'sanitize_callback' => 'sanitize_text_field',
							),
							'section_id' => array(
								'required'          => false,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
							),
						)
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/lessons',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'createStandalone' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->createArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/lessons/(?P<id>\d+)/attach',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'attach' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => array(
						'course_id'  => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'section_id' => array(
							'required'          => false,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/lessons/(?P<id>\d+)/detach',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'detach' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/lessons/(?P<id>\d+)',
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
			'/sections/(?P<id>\d+)/lessons/reorder',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reorder' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => $this->reorderArgs(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/courses/(?P<id>\d+)/lessons/reorder',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reorderForCourse' ),
					'permission_callback' => array( $this, 'canManageCourses' ),
					'args'                => array_merge(
						$this->reorderArgs(),
						array(
							'section_id' => array(
								'required'          => false,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'default'           => 0,
							),
						)
					),
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
			$userId    = $this->authorization->getCurrentUserId();
			$sectionId = (int) $request->get_param( 'id' );

			$dto = new CreateLessonDto(
				(string) $request->get_param( 'title' ),
				$this->nullableString( $request->get_param( 'slug' ) ),
				(string) ( $request->get_param( 'content' ) ?? '' ),
				$this->nullableString( $request->get_param( 'video_url' ) ),
				$this->nullableInt( $request->get_param( 'attachment_id' ) ),
				(bool) $request->get_param( 'is_preview' ),
			);

			$lesson = $this->lessonService->create( $sectionId, $dto, $userId );

			return ApiResponse::success( $this->lessonPayload( $lesson ), 201 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function createForCourse( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId    = $this->authorization->getCurrentUserId();
			$courseId  = (int) $request->get_param( 'id' );
			$sectionId = absint( $request->get_param( 'section_id' ) );

			$dto = new CreateLessonDto(
				(string) ( $request->get_param( 'title' ) ?? '' ),
				$this->nullableString( $request->get_param( 'slug' ) ),
				(string) ( $request->get_param( 'content' ) ?? '' ),
				$this->nullableString( $request->get_param( 'video_url' ) ),
				$this->nullableInt( $request->get_param( 'attachment_id' ) ),
				(bool) $request->get_param( 'is_preview' ),
			);

			$lesson = $this->lessonService->createForCourse(
				$courseId,
				$dto,
				$userId,
				$sectionId > 0 ? $sectionId : null
			);

			return ApiResponse::success( $this->lessonPayload( $lesson ), 201 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function createStandalone( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();

			$dto = new CreateLessonDto(
				(string) ( $request->get_param( 'title' ) ?? '' ),
				$this->nullableString( $request->get_param( 'slug' ) ),
				(string) ( $request->get_param( 'content' ) ?? '' ),
				$this->nullableString( $request->get_param( 'video_url' ) ),
				$this->nullableInt( $request->get_param( 'attachment_id' ) ),
				(bool) $request->get_param( 'is_preview' ),
			);

			$lesson = $this->lessonService->createStandalone( $dto, $userId );

			return ApiResponse::success( $this->lessonPayload( $lesson ), 201 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function attach( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId    = $this->authorization->getCurrentUserId();
			$lessonId  = (int) $request->get_param( 'id' );
			$courseId  = (int) $request->get_param( 'course_id' );
			$sectionId = $request->offsetExists( 'section_id' ) ? (int) $request->get_param( 'section_id' ) : null;

			$lesson = $this->lessonService->attachToCourse(
				$lessonId,
				$courseId,
				( $sectionId && $sectionId > 0 ) ? $sectionId : null,
				$userId
			);

			return ApiResponse::success( $this->lessonPayload( $lesson ) );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function detach( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId   = $this->authorization->getCurrentUserId();
			$lessonId = (int) $request->get_param( 'id' );

			$lesson = $this->lessonService->detachFromCourse( $lessonId, $userId );

			return ApiResponse::success( $this->lessonPayload( $lesson ) );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function get( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId = $this->authorization->getCurrentUserId();
			$id     = (int) $request->get_param( 'id' );

			$lesson = $this->lessonService->get( $id, $userId );

			return ApiResponse::success( $this->lessonPayload( $lesson ) );
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

			// offsetExists() uses isset() and misses JSON null — same as course clear.
			[ $updateFeaturedImage, $featuredImageId ] = $this->featuredImageFromRequest( $request );

			$dto = new UpdateLessonDto(
				$request->offsetExists( 'title' ) ? (string) $request->get_param( 'title' ) : null,
				$request->offsetExists( 'slug' ) ? $this->nullableString( $request->get_param( 'slug' ) ) : null,
				$request->offsetExists( 'content' ) ? (string) $request->get_param( 'content' ) : null,
				$request->offsetExists( 'video_url' ) ? $this->nullableString( $request->get_param( 'video_url' ) ) : null,
				$request->offsetExists( 'attachment_id' ) ? $this->nullableInt( $request->get_param( 'attachment_id' ) ) : null,
				$request->offsetExists( 'is_preview' ) ? (bool) $request->get_param( 'is_preview' ) : null,
				$request->offsetExists( 'available_after_days' ) ? $this->nullableNonNegativeInt( $request->get_param( 'available_after_days' ) ) : null,
				$request->offsetExists( 'available_after_days' ),
				$featuredImageId,
				$updateFeaturedImage,
			);

			$lesson = $this->lessonService->update( $id, $dto, $userId );

			if ( $request->offsetExists( 'settings' ) && is_array( $request->get_param( 'settings' ) ) ) {
				$this->saveLessonSettings( $id, $request->get_param( 'settings' ) );
			}

			return ApiResponse::success( $this->lessonPayload( $lesson ) );
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

			$this->lessonService->delete( $id, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function reorder( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId    = $this->authorization->getCurrentUserId();
			$sectionId = (int) $request->get_param( 'id' );
			$ids       = $request->get_param( 'ids' );

			$dto = new ReorderLessonsDto( is_array( $ids ) ? array_map( 'intval', $ids ) : array() );

			$this->lessonService->reorder( $sectionId, $dto, $userId );

			return ApiResponse::success( null, 204 );
		} catch ( ValidationException $exception ) {
			return ApiResponse::error( 'validation_error', $exception->getMessage(), 400, $exception->errors() );
		} catch ( NotFoundException $exception ) {
			return ApiResponse::error( 'not_found', $exception->getMessage(), 404 );
		} catch ( ForbiddenException $exception ) {
			return ApiResponse::error( 'forbidden', $exception->getMessage(), 403 );
		}
	}

	public function reorderForCourse( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			$userId    = $this->authorization->getCurrentUserId();
			$courseId  = (int) $request->get_param( 'id' );
			$sectionId = (int) ( $request->get_param( 'section_id' ) ?? 0 );
			$ids       = $request->get_param( 'ids' );

			$dto = new ReorderLessonsDto( is_array( $ids ) ? array_map( 'intval', $ids ) : array() );

			$this->lessonService->reorder( $sectionId, $dto, $userId, $courseId );

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
			'title'         => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'slug'          => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
			),
			'content'       => array(
				'type'              => 'string',
				'sanitize_callback' => array( RestContentSanitizer::class, 'richText' ),
			),
			'video_url'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			),
			'attachment_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'is_preview'    => array(
				'type'    => 'boolean',
				'default' => false,
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function updateArgs(): array {
		return array(
			'title'         => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'slug'          => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
			),
			'content'       => array(
				'type'              => 'string',
				'sanitize_callback' => array( RestContentSanitizer::class, 'richText' ),
			),
			'video_url'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			),
			'attachment_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'is_preview'    => array(
				'type' => 'boolean',
			),
			'available_after_days' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'featured_image_id' => array(
				'type'              => array( 'integer', 'null' ),
				'sanitize_callback' => static function ( mixed $value ): ?int {
					if ( null === $value || '' === $value || false === $value ) {
						return null;
					}

					$id = (int) $value;

					return $id > 0 ? $id : null;
				},
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

	/**
	 * Detect featured image clear vs omit. WP offsetExists() misses JSON null.
	 *
	 * @return array{0: bool, 1: ?int}
	 */
	private function featuredImageFromRequest( \WP_REST_Request $request ): array {
		$json = $request->get_json_params();

		if ( is_array( $json ) && array_key_exists( 'featured_image_id', $json ) ) {
			return array( true, $this->nullablePositiveIntOrNull( $json['featured_image_id'] ) );
		}

		if ( $request->has_param( 'featured_image_id' ) ) {
			return array( true, $this->nullablePositiveIntOrNull( $request->get_param( 'featured_image_id' ) ) );
		}

		return array( false, null );
	}

	private function nullablePositiveIntOrNull( mixed $value ): ?int {
		if ( null === $value || '' === $value || false === $value ) {
			return null;
		}

		$id = (int) $value;

		return $id > 0 ? $id : null;
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

	private function nullableNonNegativeInt( mixed $value ): ?int {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return max( 0, (int) $value );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function lessonPayload( \MintLMS\Application\Lesson\Dto\LessonDto $lesson ): array {
		$data             = $lesson->toArray();
		$data['settings'] = $this->readLessonSettings( $lesson->id );

		return $data;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function defaultLessonSettings(): array {
		return array(
			'materialsEnabled'  => false,
			'materialsContent'  => '',
			'videoProgression'  => false,
			'videoUrl'          => '',
			'videoTiming'       => 'before',
			'videoAutoplay'     => false,
			'videoControls'     => true,
			'videoPauseHidden'  => false,
			'videoResume'       => false,
			'assignmentUploads' => false,
			'allowedFileTypes'  => '',
			'maxFileSize'       => '',
			'awardPoints'       => false,
			'awardPointsValue'  => 10,
			'grading'           => 'auto',
			'maxFiles'          => 1,
			'allowDeleteFiles'  => false,
			'minTimeEnabled'    => false,
			'minTimeHours'      => '00',
			'minTimeMinutes'    => '00',
			'minTimeSeconds'    => '00',
			'sampleLesson'      => false,
			'releaseSchedule'   => 'immediately',
			'releaseDays'       => 0,
			'releaseDate'       => '',
			'externalLesson'    => false,
			'externalType'      => 'virtual',
			'requireAttendance' => 'no',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function readLessonSettings( int $lessonId ): array {
		$raw = get_post_meta( $lessonId, PostTypes::META_LESSON_SETTINGS, true );
		if ( ! is_array( $raw ) ) {
			return $this->defaultLessonSettings();
		}

		return array_merge( $this->defaultLessonSettings(), $raw );
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function saveLessonSettings( int $lessonId, array $settings ): void {
		$defaults = $this->defaultLessonSettings();
		$schedule = sanitize_key( (string) ( $settings['releaseSchedule'] ?? 'immediately' ) );
		if ( ! in_array( $schedule, array( 'immediately', 'enrollment', 'specific_date' ), true ) ) {
			$schedule = 'immediately';
		}
		$timing = sanitize_key( (string) ( $settings['videoTiming'] ?? 'before' ) );
		if ( ! in_array( $timing, array( 'before', 'after' ), true ) ) {
			$timing = 'before';
		}
		$grading = sanitize_key( (string) ( $settings['grading'] ?? 'auto' ) );
		if ( ! in_array( $grading, array( 'auto', 'manual' ), true ) ) {
			$grading = 'auto';
		}
		$ext_type = sanitize_key( (string) ( $settings['externalType'] ?? 'virtual' ) );
		if ( ! in_array( $ext_type, array( 'virtual', 'person' ), true ) ) {
			$ext_type = 'virtual';
		}
		$attend = sanitize_key( (string) ( $settings['requireAttendance'] ?? 'no' ) );
		if ( ! in_array( $attend, array( 'yes', 'no' ), true ) ) {
			$attend = 'no';
		}

		$clean = array(
			'materialsEnabled'  => ! empty( $settings['materialsEnabled'] ),
			'materialsContent'  => wp_kses_post( (string) ( $settings['materialsContent'] ?? '' ) ),
			'videoProgression'  => ! empty( $settings['videoProgression'] ),
			'videoUrl'          => sanitize_text_field( (string) ( $settings['videoUrl'] ?? '' ) ),
			'videoTiming'       => $timing,
			'videoAutoplay'     => ! empty( $settings['videoAutoplay'] ),
			'videoControls'     => ! empty( $settings['videoControls'] ),
			'videoPauseHidden'  => ! empty( $settings['videoPauseHidden'] ),
			'videoResume'       => ! empty( $settings['videoResume'] ),
			'assignmentUploads' => ! empty( $settings['assignmentUploads'] ),
			'allowedFileTypes'  => sanitize_text_field( (string) ( $settings['allowedFileTypes'] ?? '' ) ),
			'maxFileSize'       => sanitize_text_field( (string) ( $settings['maxFileSize'] ?? '' ) ),
			'awardPoints'       => ! empty( $settings['awardPoints'] ),
			'awardPointsValue'  => absint( $settings['awardPointsValue'] ?? 10 ),
			'grading'           => $grading,
			'maxFiles'          => max( 1, absint( $settings['maxFiles'] ?? 1 ) ),
			'allowDeleteFiles'  => ! empty( $settings['allowDeleteFiles'] ),
			'minTimeEnabled'    => ! empty( $settings['minTimeEnabled'] ),
			'minTimeHours'      => $this->sanitizeLessonTimePart( $settings['minTimeHours'] ?? '00' ),
			'minTimeMinutes'    => $this->sanitizeLessonTimePart( $settings['minTimeMinutes'] ?? '00' ),
			'minTimeSeconds'    => $this->sanitizeLessonTimePart( $settings['minTimeSeconds'] ?? '00' ),
			'sampleLesson'      => ! empty( $settings['sampleLesson'] ),
			'releaseSchedule'   => $schedule,
			'releaseDays'       => absint( $settings['releaseDays'] ?? 0 ),
			'releaseDate'       => sanitize_text_field( (string) ( $settings['releaseDate'] ?? '' ) ),
			'externalLesson'    => ! empty( $settings['externalLesson'] ),
			'externalType'      => $ext_type,
			'requireAttendance' => $attend,
		);

		update_post_meta( $lessonId, PostTypes::META_LESSON_SETTINGS, array_merge( $defaults, $clean ) );
	}

	private function sanitizeLessonTimePart( mixed $value ): string {
		$digits = preg_replace( '/\D+/', '', (string) $value );
		$digits = is_string( $digits ) ? substr( $digits, 0, 2 ) : '';
		if ( '' === $digits ) {
			return '00';
		}

		return str_pad( (string) min( 59, absint( $digits ) ), 2, '0', STR_PAD_LEFT );
	}
}
