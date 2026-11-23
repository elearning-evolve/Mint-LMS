<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

defined( 'ABSPATH' ) || exit;

interface CertificateUrlBuilderInterface {

	public function buildDownloadUrl( int $userId, int $courseId ): string;
}
