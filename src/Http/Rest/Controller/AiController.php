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
			'/ai/save-key',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'saveKey' ),
				'permission_callback' => array( $this, 'canManageAi' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/ai/reveal-key',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'revealKey' ),
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

		register_rest_route(
			self::NAMESPACE,
			'/ai/generate-structure-draft',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generateStructureDraft' ),
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

	/**
	 * Reveal the stored API key to a manage_options admin (eye toggle).
	 * Key is not localized on page load — only fetched when the admin asks.
	 */
	public function revealKey(): \WP_REST_Response {
		$resolved = $this->settings->resolveApiKey();
		if ( '' === $resolved['key'] ) {
			return ApiResponse::error( 'ai_key_missing', __( 'No API key is saved yet.', 'mint-lms' ), 404 );
		}

		return ApiResponse::success(
			array(
				'api_key'    => $resolved['key'],
				'key_hint'   => $this->settings->getApiKeyHint(),
				'key_source' => $resolved['source'],
			)
		);
	}

	/**
	 * Save or clear the API key only (does not touch enabled / provider / model).
	 */
	public function saveKey( \WP_REST_Request $request ): \WP_REST_Response {
		$api_key = is_string( $request->get_param( 'api_key' ) ) ? trim( (string) $request->get_param( 'api_key' ) ) : '';
		$clear   = (bool) $request->get_param( 'clear' );

		$status = $this->settings->status();
		if ( in_array( (string) $status['key_source'], array( 'constant', 'env' ), true ) ) {
			return ApiResponse::success(
				array_merge(
					$this->settings->status(),
					array(
						'message' => __( 'API key is managed by constant/env — database key ignored.', 'mint-lms' ),
					)
				)
			);
		}

		if ( $clear ) {
			$this->settings->clearStoredApiKey();

			return ApiResponse::success(
				array_merge(
					$this->settings->status(),
					array(
						'message' => __( 'API key removed.', 'mint-lms' ),
					)
				)
			);
		}

		$is_mask = '' === $api_key || AiSettings::MASK_PLACEHOLDER === $api_key;
		if ( $is_mask ) {
			if ( ! $this->settings->hasApiKey() ) {
				return ApiResponse::error( 'ai_key_missing', __( 'Paste an API key first.', 'mint-lms' ), 400 );
			}

			return ApiResponse::success(
				array_merge(
					$this->settings->status(),
					array(
						'message' => __( 'API key already saved.', 'mint-lms' ),
					)
				)
			);
		}

		if ( ! $this->settings->saveApiKey( $api_key ) ) {
			return ApiResponse::error( 'ai_key_save_failed', __( 'Could not save API key.', 'mint-lms' ), 400 );
		}

		return ApiResponse::success(
			array_merge(
				$this->settings->status(),
				array(
					'message' => __( 'API key saved.', 'mint-lms' ),
				)
			)
		);
	}

	public function generateQuizDraft( \WP_REST_Request $request ): \WP_REST_Response {
		$not_ready = $this->rejectIfAiNotReady();
		if ( null !== $not_ready ) {
			return $not_ready;
		}

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
						'scope'     => 'mintlms_only',
						'note'      => __( 'Draft only — review and save questions in the quiz builder. WordPress pages/posts were not touched.', 'mint-lms' ),
					)
				)
			);
		} catch ( ValidationException $e ) {
			return ApiResponse::error( 'ai_error', $e->getMessage(), 400, $e->errors() );
		} catch ( \Throwable $e ) {
			return ApiResponse::error( 'ai_error', 'AI request failed.', 500 );
		}
	}

	/**
	 * Prompt → Mint LMS hierarchy draft. Never writes posts/pages or Mint CPTs.
	 */
	public function generateStructureDraft( \WP_REST_Request $request ): \WP_REST_Response {
		$not_ready = $this->rejectIfAiNotReady();
		if ( null !== $not_ready ) {
			return $not_ready;
		}

		$prompt = sanitize_textarea_field( (string) $request->get_param( 'prompt' ) );

		try {
			return ApiResponse::success( $this->assistant->generateStructureDraft( $prompt ) );
		} catch ( ValidationException $e ) {
			return ApiResponse::error( 'ai_error', $e->getMessage(), 400, $e->errors() );
		} catch ( \Throwable $e ) {
			return ApiResponse::error( 'ai_error', 'AI request failed.', 500 );
		}
	}

	private function rejectIfAiNotReady(): ?\WP_REST_Response {
		if ( $this->settings->isReady() ) {
			return null;
		}

		return ApiResponse::error(
			'ai_not_ready',
			__( 'Turn on AI, save a provider, and add an API key before generating drafts.', 'mint-lms' ),
			400,
			array( 'ai' => 'not_ready' )
		);
	}
}
