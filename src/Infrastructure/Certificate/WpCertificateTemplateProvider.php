<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Certificate;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\CertificateTemplateProviderInterface;

final class WpCertificateTemplateProvider implements CertificateTemplateProviderInterface {

	public function getTemplate(): string {
		return (string) get_option( 'mintlms_certificate_template', $this->getDefaultTemplate() );
	}

	public function getDefaultTemplate(): string {
		return '<div class="mint-certificate"><h1>' . esc_html__( 'Certificate of Completion', 'mint-lms' ) . '</h1><p>' .
			esc_html__( 'This certifies that', 'mint-lms' ) . ' {{student_name}} ' .
			esc_html__( 'has successfully completed', 'mint-lms' ) . ' {{course_name}} ' .
			esc_html__( 'on', 'mint-lms' ) . ' {{completion_date}}.</p></div>';
	}

	public function getDateFormat(): string {
		$format = get_option( 'date_format' );

		return is_string( $format ) && '' !== $format ? $format : 'F j, Y';
	}
}
