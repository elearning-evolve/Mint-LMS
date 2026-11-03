<?php
declare(strict_types=1);

namespace MintLMS\Application\Admin\Dto;

final class NeedsYouCourseDto {

	public function __construct(
		public readonly int $courseId,
		public readonly string $title,
		public readonly string $blocker,
		public readonly string $editedLabel,
		public readonly string $statusLabel = 'draft',
	) {
	}
}
