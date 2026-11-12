<?php
declare(strict_types=1);

namespace MintLMS\Application\Admin\Dto;

final class DashboardMetricsDto {

	public function __construct(
		public readonly int $studentsTotal,
		public readonly ?float $finishedPct,
		public readonly int $finishedCount,
		public readonly int $startedCount,
		public readonly int $publishedCount,
		public readonly int $draftCount,
		public readonly int $lessonsDone7d,
		public readonly int $studentsDelta,
		public readonly int $finishedDelta,
	) {
	}
}
