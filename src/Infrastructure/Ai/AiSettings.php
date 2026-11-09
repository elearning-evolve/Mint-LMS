<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

/**
 * Site-level AI connector settings (opt-in SaaS). Consent = admin enables AI + configures a provider.
 *
 * Credential precedence (WP Connectors-style): constant → env → encrypted option.
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

	public const MASK_PLACEHOLDER = '••••••••••••';

	/** @return list<string> */
	public static function allowedProviders(): array {
		return array( self::PROVIDER_OPENAI, self::PROVIDER_ANTHROPIC );
	}

	public function isEnabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	public function getProvider(): string {
		$provider = sanitize_key( (string) get_option( self::OPTION_PROVIDER, self::PROVIDER_OPENAI ) );

		return in_array( $provider, self::allowedProviders(), true ) ? $provider : self::PROVIDER_OPENAI;
	}

	public function getModel(): string {
		$model = sanitize_text_field( (string) get_option( self::OPTION_MODEL, '' ) );
		if ( '' !== $model ) {
			return $model;
		}

		return self::PROVIDER_ANTHROPIC === $this->getProvider()
			? 'claude-sonnet-4-20250514'
			: 'gpt-4o-mini';
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
	 * Public status for admin UI / REST (never includes the raw key).
	 *
	 * @return array<string, mixed>
	 */
	public function status(): array {
		$resolved = $this->resolveApiKey();

		return array(
			'enabled'    => $this->isEnabled(),
			'ready'      => $this->isReady(),
			'provider'   => $this->getProvider(),
			'model'      => $this->getModel(),
			'key_source' => $resolved['source'],
			'key_hint'   => $this->getApiKeyHint(),
			'key_masked' => $this->hasApiKey() ? self::MASK_PLACEHOLDER : '',
			'providers'  => self::allowedProviders(),
		);
	}
}
