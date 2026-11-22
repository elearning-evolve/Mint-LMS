<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

/**
 * Site-level AI connector settings (opt-in SaaS). Consent = admin enables AI + configures a provider.
 *
 * Credential precedence (WP Connectors-style): constant → env → encrypted option.
 * Providers include paid (OpenAI, Anthropic) and free-tier friendly (Groq, Google Gemini).
 * Keys are always BYOK — Mint LMS never ships or bills API usage.
 */
final class AiSettings {

	public const OPTION_ENABLED      = 'mintlms_ai_enabled';
	public const OPTION_PROVIDER     = 'mintlms_ai_provider';
	public const OPTION_MODEL        = 'mintlms_ai_model';
	public const OPTION_API_KEY_ENC  = 'mintlms_ai_api_key_enc';
	public const OPTION_API_KEY_HINT = 'mintlms_ai_api_key_hint';

	public const CONSTANT_API_KEY = 'MINTLMS_AI_API_KEY';

	public const PROVIDER_OPENAI    = 'openai';
	public const PROVIDER_ANTHROPIC = 'anthropic';
	public const PROVIDER_GROQ      = 'groq';
	public const PROVIDER_GEMINI    = 'gemini';

	public const MASK_PLACEHOLDER = '••••••••••••';

	/** @return list<string> */
	public static function allowedProviders(): array {
		return array(
			self::PROVIDER_OPENAI,
			self::PROVIDER_ANTHROPIC,
			self::PROVIDER_GROQ,
			self::PROVIDER_GEMINI,
		);
	}

	public function isEnabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	public function setEnabled( bool $enabled ): void {
		update_option( self::OPTION_ENABLED, $enabled, false );
	}

	/**
	 * True when the options table still holds ciphertext (even if decrypt fails).
	 */
	public function hasStoredCiphertext(): bool {
		$resolved = $this->resolveApiKey();
		if ( in_array( $resolved['source'], array( 'constant', 'env' ), true ) ) {
			return true;
		}

		return '' !== trim( (string) get_option( self::OPTION_API_KEY_ENC, '' ) );
	}

	public function getProvider(): string {
		$provider = sanitize_key( (string) get_option( self::OPTION_PROVIDER, self::PROVIDER_OPENAI ) );

		return in_array( $provider, self::allowedProviders(), true ) ? $provider : self::PROVIDER_OPENAI;
	}

	public function getModel(): string {
		$model = sanitize_text_field( (string) get_option( self::OPTION_MODEL, '' ) );
		if ( '' !== $model ) {
			return self::migrateDeprecatedModel( $model );
		}

		return self::defaultModelFor( $this->getProvider() );
	}

	public static function defaultModelFor( string $provider ): string {
		return match ( $provider ) {
			// Anthropic: claude-sonnet-4-20250514 retired 2026-06-15 → Sonnet 5.5.
			self::PROVIDER_ANTHROPIC => 'claude-sonnet-5-5',
			// Groq free/dev: llama-3.1-8b-instant shut down 2026-08-16 → gpt-oss-20b.
			self::PROVIDER_GROQ      => 'openai/gpt-oss-20b',
			// Gemini: gemini-2.0-flash shut down 2026-06-01 → 3.8 Flash (free-tier friendly).
			self::PROVIDER_GEMINI    => 'gemini-3.8-flash',
			// OpenAI: gpt-4o-mini deprecated (shutdown 2027-01-14) → gpt-6-luna.
			default                  => 'gpt-6-luna',
		};
	}

	/**
	 * Map retired provider model IDs to current replacements.
	 */
	public static function migrateDeprecatedModel( string $model ): string {
		$map = array(
			// Groq.
			'llama-3.1-8b-instant'                      => 'openai/gpt-oss-20b',
			'llama-3.3-70b-versatile'                   => 'openai/gpt-oss-120b',
			'qwen/qwen3-32b'                            => 'openai/gpt-oss-120b',
			'meta-llama/llama-4-scout-17b-16e-instruct' => 'openai/gpt-oss-120b',
			'qwen/qwen3.6-27b'                          => 'qwen/qwen3.8-27b',
			// Google Gemini.
			'gemini-2.0-flash'                          => 'gemini-3.8-flash',
			'gemini-2.0-flash-001'                      => 'gemini-3.8-flash',
			'gemini-2.0-flash-exp'                      => 'gemini-3.8-flash',
			'gemini-2.0-flash-lite'                     => 'gemini-3.1-flash-lite',
			'gemini-2.0-flash-lite-001'                 => 'gemini-3.1-flash-lite',
			'gemini-3.6-flash'                          => 'gemini-3.8-flash',
			// Anthropic.
			'claude-sonnet-4-20250514'                  => 'claude-sonnet-5-5',
			'claude-sonnet-4'                           => 'claude-sonnet-5-5',
			'claude-opus-4-20250514'                    => 'claude-opus-4-8',
			'claude-sonnet-4-5-20250929'                => 'claude-sonnet-5-5',
			'claude-sonnet-4-5'                         => 'claude-sonnet-5-5',
			// OpenAI.
			'gpt-4o-mini'                               => 'gpt-6-luna',
			'gpt-4o-mini-2024-07-18'                    => 'gpt-6-luna',
			'gpt-4o'                                    => 'gpt-6-luna',
			'gpt-4o-2024-08-06'                         => 'gpt-6-luna',
			'gpt-4o-2024-11-20'                         => 'gpt-6-luna',
			'gpt-4.1-mini'                              => 'gpt-6-luna',
			'gpt-4.1-mini-2025-04-14'                   => 'gpt-6-luna',
			'gpt-4.1'                                   => 'gpt-6-luna',
			'gpt-4.1-2025-04-14'                        => 'gpt-6-luna',
		);

		return $map[ $model ] ?? $model;
	}

	/**
	 * @return array{key: string, source: string}
	 */
	public function resolveApiKey(): array {
		if ( defined( self::CONSTANT_API_KEY ) ) {
			$constant = constant( self::CONSTANT_API_KEY );
			if ( is_string( $constant ) && '' !== trim( $constant ) ) {
				return array(
					'key'    => trim( $constant ),
					'source' => 'constant',
				);
			}
		}

		$env = getenv( 'MINTLMS_AI_API_KEY' );
		if ( is_string( $env ) && '' !== trim( $env ) ) {
			return array(
				'key'    => trim( $env ),
				'source' => 'env',
			);
		}

		$stored = AiSecretStore::decrypt( (string) get_option( self::OPTION_API_KEY_ENC, '' ) );
		if ( '' !== $stored ) {
			return array(
				'key'    => $stored,
				'source' => 'option',
			);
		}

		return array(
			'key'    => '',
			'source' => 'none',
		);
	}

	public function hasApiKey(): bool {
		return '' !== $this->resolveApiKey()['key'];
	}

	public function isReady(): bool {
		return $this->isEnabled() && $this->hasApiKey();
	}

	public function getApiKeyHint(): string {
		$resolved = $this->resolveApiKey();
		if ( 'constant' === $resolved['source'] || 'env' === $resolved['source'] ) {
			return AiSecretStore::hint( $resolved['key'] );
		}

		return sanitize_text_field( (string) get_option( self::OPTION_API_KEY_HINT, '' ) );
	}

	/**
	 * Persist a new BYOK value (encrypted). No-op for constant/env-managed keys.
	 */
	public function saveApiKey( string $plain ): bool {
		$resolved = $this->resolveApiKey();
		if ( in_array( $resolved['source'], array( 'constant', 'env' ), true ) ) {
			return false;
		}

		$plain = trim( $plain );
		if ( '' === $plain || self::MASK_PLACEHOLDER === $plain ) {
			return false;
		}

		update_option( self::OPTION_API_KEY_ENC, AiSecretStore::encrypt( $plain ), false );
		update_option( self::OPTION_API_KEY_HINT, AiSecretStore::hint( $plain ), false );

		return true;
	}

	public function clearStoredApiKey(): void {
		$resolved = $this->resolveApiKey();
		if ( in_array( $resolved['source'], array( 'constant', 'env' ), true ) ) {
			return;
		}

		update_option( self::OPTION_API_KEY_ENC, '', false );
		update_option( self::OPTION_API_KEY_HINT, '', false );
	}

	/**
	 * Public status for admin UI / REST (never includes the raw key).
	 *
	 * @return array<string, mixed>
	 */
	public function status(): array {
		$resolved = $this->resolveApiKey();

		$unreadable = $this->hasStoredCiphertext() && '' === $resolved['key']
			&& ! in_array( $resolved['source'], array( 'constant', 'env' ), true );

		return array(
			'enabled'        => $this->isEnabled(),
			'ready'          => $this->isReady(),
			'provider'       => $this->getProvider(),
			'model'          => $this->getModel(),
			'key_source'     => $resolved['source'],
			'key_hint'       => $unreadable ? '' : $this->getApiKeyHint(),
			'key_masked'     => $this->hasApiKey() ? self::MASK_PLACEHOLDER : '',
			'key_unreadable' => $unreadable,
			'providers'      => self::allowedProviders(),
		);
	}
}
