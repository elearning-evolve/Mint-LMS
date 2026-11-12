<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface CertificateUrlBuilderInterface {

	public function buildDownloadUrl( int $userId, int $courseId ): string;
}
