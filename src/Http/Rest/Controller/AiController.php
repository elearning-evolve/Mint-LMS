<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Ai\AiAssistantService;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Http\Rest\Response\ApiResponse;
use MintLMS\Infrastructure\Ai\AiSettings;

/**
 * Server-side AI proxy. API keys never leave PHP / never localize to JS.
 */
final class AiController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private AiSettings $settings,
		private AiAssistantService $assistant,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/ai/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'status' ),
				'permission_callback' => array( $this, 'canUseAi' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/ai/test',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'test' ),
				'permission_callback' => array( $this, 'canManageAi' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/ai/generate-quiz-draft',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generateQuizDraft' ),
				'permission_callback' => array( $this, 'canUseAi' ),
			)
		);
	}

	public function canUseAi(): bool {
		return current_user_can( 'edit_mintlms_courses' );
	}

	public function canManageAi(): bool {
		return current_user_can( 'manage_options' );
	}

	public function status(): \WP_REST_Response {
		return ApiResponse::success( $this->settings->status() );
	}

	public function test(): \WP_REST_Response {
		try {
			return ApiResponse::success( $this->assistant->testConnection() );
		} catch ( ValidationException $e ) {
			return ApiResponse::error( 'ai_error', $e->getMessage(), 400, $e->errors() );
		} catch ( \Throwable $e ) {
			return ApiResponse::error( 'ai_error', 'AI request failed.', 500 );
		}
	}

	public function generateQuizDraft( \WP_REST_Request $request ): \WP_REST_Response {
		$topic = sanitize_text_field( (string) $request->get_param( 'topic' ) );
		$count = absint( $request->get_param( 'count' ) );
		if ( $count <= 0 ) {
			$count = 5;
		}

		try {
			$draft = $this->assistant->generateQuizDraft( $topic, $count );

			return ApiResponse::success(
				array_merge(
					$draft,
					array(
						'persisted' => false,
						'note'      => __( 'Draft only — review and save questions in the quiz builder.', 'mint-lms' ),
					)
				)
			);
		} catch ( ValidationException $e ) {
			return ApiResponse::error( 'ai_error', $e->getMessage(), 400, $e->errors() );
		} catch ( \Throwable $e ) {
			return ApiResponse::error( 'ai_error', 'AI request failed.', 500 );
		}
	}
}
