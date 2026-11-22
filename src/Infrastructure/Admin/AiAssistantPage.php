<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Ai\AiSecretStore;
use MintLMS\Infrastructure\Ai\AiSettings;

/**
 * Dedicated Mint LMS → AI Assistant admin page (BYOK free + paid providers).
 */
final class AiAssistantPage {

	public const PAGE_SLUG    = 'mint-lms-ai-assistant';
	public const OPTION_GROUP = 'mintlms_ai_settings';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
	}

	public function registerMenu(): void {
		add_submenu_page(
			'mint-lms',
			__( 'AI Assistant', 'mint-lms' ),
			MenuIcons::labeled( 'ai', __( 'AI Assistant', 'mint-lms' ) ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	public function registerSettings(): void {
		register_setting(
			self::OPTION_GROUP,
			AiSettings::OPTION_ENABLED,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitizeCheckbox' ),
				'default'           => false,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			AiSettings::OPTION_PROVIDER,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitizeAiProvider' ),
				'default'           => AiSettings::PROVIDER_GROQ,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			AiSettings::OPTION_MODEL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			AiSettings::OPTION_API_KEY_ENC,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitizeAiApiKey' ),
				'default'           => '',
			)
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Mint LMS AI settings.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/ai-assistant.php';
	}

	public function sanitizeCheckbox( mixed $value ): bool {
		return ! empty( $value );
	}

	public function sanitizeAiProvider( mixed $value ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, AiSettings::allowedProviders(), true ) ? $value : AiSettings::PROVIDER_GROQ;
	}

	/**
	 * Encrypt a new key; keep existing ciphertext when the masked placeholder is posted.
	 */
	public function sanitizeAiApiKey( mixed $value ): string {
		$value = is_string( $value ) ? trim( $value ) : '';

		$clear = isset( $_POST['mintlms_ai_clear_key'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_POST['mintlms_ai_clear_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Handled by options.php settings nonce.
		if ( $clear ) {
			update_option( AiSettings::OPTION_API_KEY_HINT, '', false );
			return '';
		}

		if ( '' === $value || AiSettings::MASK_PLACEHOLDER === $value ) {
			return (string) get_option( AiSettings::OPTION_API_KEY_ENC, '' );
		}

		update_option( AiSettings::OPTION_API_KEY_HINT, AiSecretStore::hint( $value ), false );

		return AiSecretStore::encrypt( $value );
	}

	public static function url(): string {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}
}
