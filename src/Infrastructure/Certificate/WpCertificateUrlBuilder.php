<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Certificate;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\CertificateUrlBuilderInterface;

final class WpCertificateUrlBuilder implements CertificateUrlBuilderInterface {

	public function buildDownloadUrl( int $userId, int $courseId ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'    => 'mintlms_certificate',
					'course_id' => (string) $courseId,
					'user_id'   => (string) $userId,
				),
				admin_url( 'admin-ajax.php' )
			),
			'mintlms_certificate_' . $userId . '_' . $courseId
		);
	}
}
