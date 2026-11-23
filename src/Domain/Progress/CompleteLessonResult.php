<?php
declare(strict_types=1);

namespace MintLMS\Domain\Progress;

defined( 'ABSPATH' ) || exit;

final readonly class CompleteLessonResult {

	public function __construct(
		public bool $wasNewlyCompleted,
		public ProgressSummary $summary,
		public bool $courseJustCompleted,
	) {
	}
}
