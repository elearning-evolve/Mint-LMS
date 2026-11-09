<?php
declare(strict_types=1);

namespace MintLMS\Application\Ai;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Exception\ValidationException;
use MintLMS\Infrastructure\Ai\AiProviderClient;
use MintLMS\Infrastructure\Ai\AiSettings;

/**
 * Allowlisted AI actions. Never persists content — callers confirm before save.
 */
final class AiAssistantService {

	public function __construct(
		private AiSettings $settings,
		private AiProviderClient $client,
	) {
	}

	/**
	 * @return array{ok: bool, provider: string, model: string, latency_ms: int, preview: string}
	 */
	public function testConnection(): array {
		$started = microtime( true );
		$text    = $this->client->chat(
			array(
				array(
					'role'    => 'user',
					'content' => 'Reply with exactly: OK',
				),
			),
			32
		);

		return array(
			'ok'         => true,
			'provider'   => $this->settings->getProvider(),
			'model'      => $this->settings->getModel(),
			'latency_ms' => (int) round( ( microtime( true ) - $started ) * 1000 ),
			'preview'    => sanitize_text_field( substr( $text, 0, 80 ) ),
		);
	}

	/**
	 * Draft quiz questions from a topic. Returns structured data only — does not write to the DB.
	 *
	 * @return array{topic: string, questions: list<array{type: string, prompt: string, options: list<string>, correct_answer: string}>}
	 */
	public function generateQuizDraft( string $topic, int $count = 5 ): array {
		$topic = sanitize_text_field( trim( $topic ) );
		$count = max( 1, min( 10, $count ) );

		if ( '' === $topic ) {
			throw new ValidationException( 'Validation failed.', array( 'topic' => 'Topic is required.' ) );
		}

		$system = 'You are a helper for Mint LMS, a WordPress learning plugin. '
			. 'Return ONLY valid JSON (no markdown fences) with this shape: '
			. '{"questions":[{"type":"mcq"|"true_false","prompt":"...","options":["..."],"correct_answer":"..."}]} . '
			. 'For mcq provide 3-5 options and set correct_answer to the exact option text. '
			. 'For true_false options must be ["True","False"] and correct_answer True or False. '
			. 'Do not invent student personal data. Keep language clear for adult learners.';

		$user = sprintf(
			'Create %d quiz questions about: %s',
			$count,
			$topic
		);

		$raw = $this->client->chat(
			array(
				array(
					'role'    => 'system',
					'content' => $system,
				),
				array(
					'role'    => 'user',
					'content' => $user,
				),
			),
			2500
		);

		$decoded = $this->parseJsonObject( $raw );
		$items   = $decoded['questions'] ?? null;
		if ( ! is_array( $items ) || array() === $items ) {
			throw new ValidationException( 'AI did not return usable questions.', array( 'ai' => 'invalid_draft' ) );
		}

		$questions = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$normalized = $this->normalizeQuestion( $item );
			if ( null !== $normalized ) {
				$questions[] = $normalized;
			}
		}

		if ( array() === $questions ) {
			throw new ValidationException( 'AI did not return usable questions.', array( 'ai' => 'invalid_draft' ) );
		}

		return array(
			'topic'     => $topic,
			'questions' => array_slice( $questions, 0, $count ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function parseJsonObject( string $raw ): array {
		$raw = trim( $raw );
		if ( str_starts_with( $raw, '```' ) ) {
			$raw = preg_replace( '/^```(?:json)?\s*/i', '', $raw ) ?? $raw;
			$raw = preg_replace( '/\s*```$/', '', $raw ) ?? $raw;
		}

		$start = strpos( $raw, '{' );
		$end   = strrpos( $raw, '}' );
		if ( false !== $start && false !== $end && $end > $start ) {
			$raw = substr( $raw, $start, $end - $start + 1 );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			throw new ValidationException( 'AI returned invalid JSON.', array( 'ai' => 'invalid_json' ) );
		}

		return $data;
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array{type: string, prompt: string, options: list<string>, correct_answer: string}|null
	 */
	private function normalizeQuestion( array $item ): ?array {
		$type   = sanitize_key( (string) ( $item['type'] ?? 'mcq' ) );
		$prompt = sanitize_text_field( (string) ( $item['prompt'] ?? '' ) );
		if ( '' === $prompt ) {
			return null;
		}

		if ( ! in_array( $type, array( 'mcq', 'true_false' ), true ) ) {
			$type = 'mcq';
		}

		$options = array();
		if ( isset( $item['options'] ) && is_array( $item['options'] ) ) {
			foreach ( $item['options'] as $option ) {
				$clean = sanitize_text_field( (string) $option );
				if ( '' !== $clean ) {
					$options[] = $clean;
				}
			}
		}

		$correct = sanitize_text_field( (string) ( $item['correct_answer'] ?? '' ) );

		if ( 'true_false' === $type ) {
			$options = array( 'True', 'False' );
			$correct = in_array( strtolower( $correct ), array( 'true', '1', 'yes' ), true ) ? 'True' : 'False';
		} elseif ( count( $options ) < 2 ) {
			return null;
		} elseif ( '' === $correct || ! in_array( $correct, $options, true ) ) {
			$correct = $options[0];
		}

		return array(
			'type'           => $type,
			'prompt'         => $prompt,
			'options'        => $options,
			'correct_answer' => $correct,
		);
	}
}
