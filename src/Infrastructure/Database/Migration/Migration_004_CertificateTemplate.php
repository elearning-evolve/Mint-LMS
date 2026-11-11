<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

final class Migration_004_CertificateTemplate implements MigrationInterface {

	public function version(): string {
		return '004';
	}

	public function up( \wpdb $wpdb ): void {
		unset( $wpdb );

		if ( false !== get_option( 'mintlms_certificate_template', false ) ) {
			return;
		}

		$template = '<div class="mint-certificate">
	<h1 class="mint-certificate__title">' . esc_html__( 'Certificate of Completion', 'mint-lms' ) . '</h1>
	<p class="mint-certificate__body">' .
			esc_html__( 'This certifies that', 'mint-lms' ) . ' <strong>{{student_name}}</strong> ' .
			esc_html__( 'has successfully completed', 'mint-lms' ) . ' <strong>{{course_name}}</strong> ' .
			esc_html__( 'on', 'mint-lms' ) . ' <strong>{{completion_date}}</strong>.</p>
</div>';

		add_option( 'mintlms_certificate_template', $template, '', false );
	}
}
