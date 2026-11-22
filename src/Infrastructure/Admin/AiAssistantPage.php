<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Mint LMS → AI Assistant admin page (Claude Desktop / MCP onboarding).
 */
final class AiAssistantPage {

	public const PAGE_SLUG = 'mint-lms-ai-assistant';

	/** @var list<string> */
	private const LEGACY_BYOK_OPTIONS = array(
		'mintlms_ai_enabled',
		'mintlms_ai_provider',
		'mintlms_ai_model',
		'mintlms_ai_api_key_enc',
		'mintlms_ai_api_key_hint',
	);

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_action( 'admin_init', array( $this, 'cleanupLegacyByokOptions' ) );
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

	/**
	 * Remove leftover BYOK options from older releases (idempotent).
	 */
	public function cleanupLegacyByokOptions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( self::LEGACY_BYOK_OPTIONS as $option ) {
			delete_option( $option );
		}
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Mint LMS AI settings.', 'mint-lms' ) );
		}

		include MINTLMS_PATH . 'views/admin/ai-assistant.php';
	}

	public static function url(): string {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}
}
