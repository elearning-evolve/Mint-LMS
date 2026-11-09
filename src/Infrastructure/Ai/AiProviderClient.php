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
		if ( ! $this->settings->isReady() ) {
			throw new ValidationException(
				'AI is not enabled or an API key is missing.',
				array( 'ai' => 'Enable AI under Mint LMS → Settings and add an API key (or define MINTLMS_AI_API_KEY).' )
			);
		}

		$resolved = $this->settings->resolveApiKey();
		$provider = $this->settings->getProvider();
		$model    = $this->settings->getModel();

		if ( AiSettings::PROVIDER_ANTHROPIC === $provider ) {
			return $this->chatAnthropic( $resolved['key'], $model, $messages, $maxTokens );
		}

		return $this->chatOpenAi( $resolved['key'], $model, $messages, $maxTokens );
	}

	/**
	 * @param list<array{role: string, content: string}> $messages
	 */
	private function chatOpenAi( string $apiKey, string $model, array $messages, int $maxTokens ): string {
		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $apiKey,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $model,
						'messages'    => $messages,
						'max_tokens'  => $maxTokens,
						'temperature' => 0.4,
					)
				),
			)
		);

		return $this->extractOpenAiContent( $response );
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
	 * @param array<string, mixed>|\WP_Error $response
	 */
	private function extractOpenAiContent( $response ): string {
		$data    = $this->decodeResponse( $response, 'OpenAI' );
		$content = $data['choices'][0]['message']['content'] ?? '';
		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			throw new ValidationException( 'AI provider returned an empty response.', array( 'ai' => 'empty_response' ) );
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
			$message = is_array( $data ) ? (string) ( $data['error']['message'] ?? $data['error']['type'] ?? $body ) : $body;
			throw new ValidationException(
				esc_html(
					sprintf(
						/* translators: 1: AI provider label, 2: HTTP status code */
						__( '%1$s returned HTTP %2$d.', 'mint-lms' ),
						$safe_label,
						$code
					)
				),
				array(
					'ai' => esc_html( sanitize_text_field( wp_strip_all_tags( substr( $message, 0, 300 ) ) ) ),
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
