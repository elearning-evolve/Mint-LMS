<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Privacy;

defined( 'ABSPATH' ) || exit;

/**
 * Suggests privacy policy text for Mint LMS (incl. optional AI SaaS).
 *
 * @see https://developer.wordpress.org/plugins/privacy/
 */
final class PrivacyPolicyContent {

	public function register(): void {
		add_action( 'admin_init', array( $this, 'addPrivacyPolicyContent' ) );
	}

	public function addPrivacyPolicyContent(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p>' . esc_html__( 'Mint LMS stores course content, enrollments, and learning progress on this WordPress site.', 'mint-lms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If the site administrator enables Mint LMS AI features and connects an AI provider (for example OpenAI or Anthropic), prompts and related course/quiz drafting text you submit may be sent to that provider’s servers to generate draft content. API keys are stored on this site (or supplied via server configuration) and are not exposed to browsers. Mint LMS does not send AI traffic until AI is enabled and configured.', 'mint-lms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Review your AI provider’s terms and privacy policy. You can disable AI or remove the API key under Mint LMS → Settings.', 'mint-lms' ) . '</p>';

		wp_add_privacy_policy_content(
			'Mint LMS',
			wp_kses_post( $content )
		);
	}
}
