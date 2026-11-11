<?php
declare(strict_types=1);

namespace MintLMS\Application\Admin\Dto;

final class CreatorDashboardDto {

	/**
	 * @param list<DashboardCourseRowDto> $courses Visible course rows (capped for the dashboard table).
	 * @param list<DashboardActivityItemDto> $activity
	 */
	public function __construct(
		public readonly bool $isEmpty,
		public readonly DashboardMetricsDto $metrics,
		public readonly array $courses,
		public readonly ?NeedsYouCourseDto $needsYou,
		public readonly array $activity,
		public readonly string $signalLine,
		public readonly int $coursesTotal = 0,
	) {
	}
}
