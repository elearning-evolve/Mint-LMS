<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface MediaInterface {

	public function getUrl( int $attachmentId ): string;

	/**
	 * @param array<string, mixed> $file Upload payload (typically a single $_FILES entry).
	 */
	public function validateUpload( array $file ): bool;
}
