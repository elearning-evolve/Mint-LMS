<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Certificate;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Certificate\CertificateService;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Frontend\TemplateLoader;

final class CertificateDownloadHandler {

	public function __construct(
		private CertificateService $certificateService,
		private TemplateLoader $templateLoader,
	) {
	}

	public function register(): void {
		add_action( 'wp_ajax_mintlms_certificate', array( $this, 'handle' ) );
	}

	public function handle(): void {
		$courseId = isset( $_GET['course_id'] ) ? absint( wp_unslash( (string) $_GET['course_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$userId   = isset( $_GET['user_id'] ) ? absint( wp_unslash( (string) $_GET['user_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $courseId <= 0 || $userId <= 0 ) {
			wp_die( esc_html__( 'Invalid certificate request.', 'mint-lms' ), '', array( 'response' => 400 ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ), 'mintlms_certificate_' . $userId . '_' . $courseId ) ) {
			wp_die( esc_html__( 'Invalid security token.', 'mint-lms' ), '', array( 'response' => 403 ) );
		}

		$currentUserId = get_current_user_id();

		if ( $currentUserId !== $userId && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this certificate.', 'mint-lms' ), '', array( 'response' => 403 ) );
		}

		try {
			$html = $this->certificateService->generateCertificate( $userId, $courseId );
		} catch ( ForbiddenException $exception ) {
			wp_die( esc_html( $exception->getMessage() ), '', array( 'response' => 403 ) );
		}

		$output = $this->templateLoader->render(
			'student/certificate.php',
			array(
				'certificateHtml' => $html,
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Certificate HTML is generated from trusted admin template.
		echo $output;
		exit;
	}
}
