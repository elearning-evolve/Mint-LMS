<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Ai\AiSecretStore;
use MintLMS\Infrastructure\Ai\AiSettings;
use MintLMS\Infrastructure\Setup\PageInstaller;
use MintLMS\Infrastructure\Setup\PageSettings;

final class SettingsPage {

	private const PAGE_SLUG    = 'mint-lms-settings';
	private const OPTION_GROUP = 'mintlms_settings';
	private const CERT_OPTION  = 'mintlms_certificate_template';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
		add_action( 'admin_post_mintlms_create_pages', array( $this, 'handleCreatePages' ) );
	}

	public function registerMenu(): void {
		add_submenu_page(
			'mint-lms',
			__( 'Settings', 'mint-lms' ),
			__( 'Settings', 'mint-lms' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	public function registerSettings(): void {
		register_setting(
			self::OPTION_GROUP,
			PageSettings::OPTION_DASHBOARD,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitizePageId' ),
				'default'           => 0,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			PageSettings::OPTION_CATALOG,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitizePageId' ),
				'default'           => 0,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			PageSettings::OPTION_PLAYER,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitizePageId' ),
				'default'           => 0,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			PageSettings::OPTION_DEFAULT_ENROLLMENT,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitizeDefaultEnrollment' ),
				'default'           => 'open',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			PageSettings::OPTION_EMAIL_ENROLL,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitizeCheckbox' ),
				'default'           => true,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			PageSettings::OPTION_EMAIL_COMPLETE,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitizeCheckbox' ),
				'default'           => true,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::CERT_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitizeCertificateTemplate' ),
				'default'           => '',
			)
		);

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
				'default'           => AiSettings::PROVIDER_OPENAI,
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

	public function handleCreatePages(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Mint LMS settings.', 'mint-lms' ) );
		}

		check_admin_referer( 'mintlms_create_pages' );

		( new PageInstaller() )->install();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => self::PAGE_SLUG,
					'mintlms_pages' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Mint LMS settings.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/settings.php';
	}

	public function sanitizePageId( mixed $value ): int {
		return max( 0, absint( $value ) );
	}

	public function sanitizeDefaultEnrollment( mixed $value ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, array( 'open', 'free', 'manual', 'paid' ), true ) ? $value : 'free';
	}

	public function sanitizeCheckbox( mixed $value ): bool {
		return ! empty( $value );
	}

	public function sanitizeCertificateTemplate( mixed $value ): string {
		$value = (string) $value;

		return wp_kses_post( $value );
	}

	public function sanitizeAiProvider( mixed $value ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, AiSettings::allowedProviders(), true ) ? $value : AiSettings::PROVIDER_OPENAI;
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
}
