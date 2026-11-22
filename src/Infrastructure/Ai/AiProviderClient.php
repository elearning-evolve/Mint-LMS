<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Exception\ValidationException;

/**
 * Calls configured AI SaaS via WordPress HTTP API (server-side only).
 */
final class AiProviderClient {

	public function __construct(
		private AiSettings $settings,
	) {
	}

	/**
	 * @param list<array{role: string, content: string}> $messages
	 */
	public function chat( array $messages, int $maxTokens = 2048 ): string {
		if ( ! $this->settings->isEnabled() ) {
			throw new ValidationException(
				'AI is turned off.',
				array(
					'ai'   => 'ai_disabled',
					'hint' => 'Turn on AI features, click Save AI settings, then try again.',
				)
			);
		}

		if ( ! $this->settings->hasApiKey() ) {
			if ( $this->settings->hasStoredCiphertext() ) {
				throw new ValidationException(
					'Saved API key cannot be read on this site.',
					array(
						'ai'   => 'ai_key_unreadable',
						'hint' => 'Site security keys may have changed. Paste your API key again and click Save API key.',
					)
				);
			}

			throw new ValidationException(
				'API key is missing.',
				array(
					'ai'   => 'ai_key_missing',
					'hint' => 'Paste your API key and click Save API key (or set MINTLMS_AI_API_KEY in wp-config).',
				)
			);
		}

		$resolved = $this->settings->resolveApiKey();
		$provider = $this->settings->getProvider();
		$model    = $this->settings->getModel();

		return match ( $provider ) {
			AiSettings::PROVIDER_ANTHROPIC => $this->chatAnthropic( $resolved['key'], $model, $messages, $maxTokens ),
			AiSettings::PROVIDER_GROQ      => $this->chatOpenAiCompatible(
				'https://api.groq.com/openai/v1/chat/completions',
				$resolved['key'],
				$model,
				$messages,
				$maxTokens,
				'Groq'
			),
			AiSettings::PROVIDER_GEMINI    => $this->chatGemini( $resolved['key'], $model, $messages, $maxTokens ),
			default                        => $this->chatOpenAiCompatible(
				'https://api.openai.com/v1/chat/completions',
				$resolved['key'],
				$model,
				$messages,
				$maxTokens,
				'OpenAI'
			),
		};
	}

	/**
	 * OpenAI-compatible chat (OpenAI, Groq, etc.).
	 *
	 * @param list<array{role: string, content: string}> $messages
	 */
	private function chatOpenAiCompatible(
		string $url,
		string $apiKey,
		string $model,
		array $messages,
		int $maxTokens,
		string $label
	): string {
		// Reasoning models (e.g. Groq openai/gpt-oss-*) spend completion budget on
		// hidden thought tokens first — a tiny max_tokens yields HTTP 200 + empty content.
		$body = array(
			'model'                 => $model,
			'messages'              => $messages,
			'max_completion_tokens' => max( 1, $maxTokens ),
			'temperature'           => 0.4,
		);

		if ( self::isReasoningChatModel( $model ) ) {
			$body['reasoning_effort'] = 'low';
			// Prefer the final answer in content; keep some headroom above caller budget.
			$body['max_completion_tokens'] = max( $maxTokens, 256 );
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $apiKey,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		return $this->extractOpenAiContent( $response, $label );
	}

	/**
	 * Models that burn completion tokens on internal reasoning before content.
	 */
	private static function isReasoningChatModel( string $model ): bool {
		$m = strtolower( $model );

		return str_contains( $m, 'gpt-oss' )
			|| str_starts_with( $m, 'qwen/qwen3' )
			|| str_contains( $m, 'muse-spark' );
	}

	/**
	 * @param list<array{role: string, content: string}> $messages
	 */
	private function chatAnthropic( string $apiKey, string $model, array $messages, int $maxTokens ): string {
		$system    = '';
		$converted = array();
		foreach ( $messages as $message ) {
			$role = (string) ( $message['role'] ?? '' );
			$text = (string) ( $message['content'] ?? '' );
			if ( 'system' === $role ) {
				$system .= ( '' === $system ? '' : "\n\n" ) . $text;
				continue;
			}
			$converted[] = array(
				'role'    => 'assistant' === $role ? 'assistant' : 'user',
				'content' => $text,
			);
		}

		$body = array(
			'model'      => $model,
			'max_tokens' => $maxTokens,
			'messages'   => $converted,
		);
		if ( '' !== $system ) {
			$body['system'] = $system;
		}

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 60,
				'headers' => array(
					'x-api-key'         => $apiKey,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		return $this->extractAnthropicContent( $response );
	}

	/**
	 * Google Gemini generateContent (AI Studio / free-tier friendly).
	 *
	 * @param list<array{role: string, content: string}> $messages
	 */
	private function chatGemini( string $apiKey, string $model, array $messages, int $maxTokens ): string {
		$model  = sanitize_text_field( $model );
		$system = '';
		$parts  = array();

		foreach ( $messages as $message ) {
			$role = (string) ( $message['role'] ?? '' );
			$text = (string) ( $message['content'] ?? '' );
			if ( 'system' === $role ) {
				$system .= ( '' === $system ? '' : "\n\n" ) . $text;
				continue;
			}
			$parts[] = array(
				'role'  => 'assistant' === $role ? 'model' : 'user',
				'parts' => array(
					array( 'text' => $text ),
				),
			);
		}

		$body = array(
			'contents'         => $parts,
			'generationConfig' => array(
				'maxOutputTokens' => $maxTokens,
				'temperature'     => 0.4,
			),
		);
		if ( '' !== $system ) {
			$body['systemInstruction'] = array(
				'parts' => array(
					array( 'text' => $system ),
				),
			);
		}

		$url = sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
			rawurlencode( $model )
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $apiKey,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		return $this->extractGeminiContent( $response );
	}

	/**
	 * @param array<string, mixed>|\WP_Error $response
	 */
	private function extractOpenAiContent( $response, string $label = 'OpenAI' ): string {
		$data    = $this->decodeResponse( $response, $label );
		$message = is_array( $data['choices'][0]['message'] ?? null ) ? $data['choices'][0]['message'] : array();
		$content = $message['content'] ?? '';

		// Some providers put the visible answer in content; reasoning models may leave it empty
		// when the token budget was spent on thought tokens (finish_reason=length).
		if ( ( ! is_string( $content ) || '' === trim( $content ) ) && isset( $message['reasoning'] ) && is_string( $message['reasoning'] ) ) {
			// Do not treat reasoning as the answer — surface a clearer failure instead.
			$content = '';
		}

		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			$finish = sanitize_key( (string) ( $data['choices'][0]['finish_reason'] ?? '' ) );
			if ( 'length' === $finish ) {
				throw new ValidationException(
					esc_html__( 'AI used all tokens on reasoning and returned no answer. Try again, or pick a non-reasoning model.', 'mint-lms' ),
					array(
						'ai' => 'token_budget',
					)
				);
			}
			throw new ValidationException(
				esc_html__( 'AI provider returned an empty response.', 'mint-lms' ),
				array(
					'ai' => 'empty_response',
				)
			);
		}

		return trim( $content );
	}

	/**
	 * @param array<string, mixed>|\WP_Error $response
	 */
	private function extractAnthropicContent( $response ): string {
		$data  = $this->decodeResponse( $response, 'Anthropic' );
		$parts = $data['content'] ?? array();
		$text  = '';
		if ( is_array( $parts ) ) {
			foreach ( $parts as $part ) {
				if ( is_array( $part ) && isset( $part['text'] ) && is_string( $part['text'] ) ) {
					$text .= $part['text'];
				}
			}
		}
		if ( '' === trim( $text ) ) {
			throw new ValidationException( 'AI provider returned an empty response.', array( 'ai' => 'empty_response' ) );
		}

		return trim( $text );
	}

	/**
	 * @param array<string, mixed>|\WP_Error $response
	 */
	private function extractGeminiContent( $response ): string {
		$data  = $this->decodeResponse( $response, 'Gemini' );
		$text  = '';
		$cands = $data['candidates'] ?? array();
		if ( is_array( $cands ) ) {
			foreach ( $cands as $cand ) {
				if ( ! is_array( $cand ) ) {
					continue;
				}
				$content = $cand['content']['parts'] ?? array();
				if ( ! is_array( $content ) ) {
					continue;
				}
				foreach ( $content as $part ) {
					if ( is_array( $part ) && isset( $part['text'] ) && is_string( $part['text'] ) ) {
						$text .= $part['text'];
					}
				}
			}
		}
		if ( '' === trim( $text ) ) {
			throw new ValidationException( 'AI provider returned an empty response.', array( 'ai' => 'empty_response' ) );
		}

		return trim( $text );
	}

	/**
	 * @param array<string, mixed>|\WP_Error $response
	 * @return array<string, mixed>
	 */
	private function decodeResponse( $response, string $label ): array {
		$safe_label = sanitize_text_field( $label );

		if ( is_wp_error( $response ) ) {
			throw new ValidationException(
				esc_html(
					sprintf(
						/* translators: %s: AI provider label */
						__( '%s request failed.', 'mint-lms' ),
						$safe_label
					)
				),
				array(
					'ai' => esc_html( sanitize_text_field( $response->get_error_message() ) ),
				)
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = '';
			if ( is_array( $data ) ) {
				$message = (string) (
					$data['error']['message']
					?? $data['error']['status']
					?? $data['error']['type']
					?? $body
				);
			} else {
				$message = $body;
			}
			$detail  = sanitize_text_field( wp_strip_all_tags( substr( $message, 0, 300 ) ) );
			$summary = sprintf(
				/* translators: 1: AI provider label, 2: HTTP status code */
				__( '%1$s returned HTTP %2$d.', 'mint-lms' ),
				$safe_label,
				$code
			);
			if ( '' !== $detail ) {
				$summary .= ' ' . $detail;
			}
			throw new ValidationException(
				esc_html( $summary ),
				array(
					'ai' => esc_html( $detail ),
				)
			);
		}

		if ( ! is_array( $data ) ) {
			throw new ValidationException(
				esc_html(
					sprintf(
						/* translators: %s: AI provider label */
						__( '%s returned invalid JSON.', 'mint-lms' ),
						$safe_label
					)
				),
				array(
					'ai' => 'invalid_json',
				)
			);
		}

		return $data;
	}
}
