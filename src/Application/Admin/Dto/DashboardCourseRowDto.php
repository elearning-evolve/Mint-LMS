<?php
declare(strict_types=1);

namespace MintLMS\Application\Admin\Dto;

final class DashboardCourseRowDto {

	public function __construct(
		public readonly int $id,
		public readonly string $title,
		public readonly string $status,
		public readonly string $studentsLabel,
		public readonly ?float $completionPct,
		public readonly string $editedLabel,
		public readonly string $metaLine,
		public readonly string $tileBg,
		public readonly string $tileInk,
		public readonly string $initial,
	) {
	}
}
