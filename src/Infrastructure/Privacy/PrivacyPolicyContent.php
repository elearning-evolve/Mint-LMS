<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Privacy;

defined( 'ABSPATH' ) || exit;

/**
 * Suggests privacy policy text for Mint LMS (incl. optional Claude Desktop / MCP).
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
		$content .= '<p>' . esc_html__( 'If the site administrator connects Claude Desktop (or another MCP client) through the WordPress MCP Adapter, prompts you send in that client may cause draft Mint LMS courses, lessons, quizzes, or questions to be created on this site. Those items are saved as drafts for review. Mint LMS does not send prompts to third-party AI APIs from this WordPress admin for that flow — the AI client talks to this site’s MCP endpoint using an Application Password.', 'mint-lms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Review your AI client’s terms and privacy policy. You can disconnect the MCP client or revoke the Application Password under your WordPress profile.', 'mint-lms' ) . '</p>';

		wp_add_privacy_policy_content(
			'Mint LMS',
			wp_kses_post( $content )
		);
	}
}
