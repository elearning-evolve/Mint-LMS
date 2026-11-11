<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

final readonly class Quiz {

	public function __construct(
		public int $id,
		public int $lessonId,
		public int $courseId,
		public string $title,
		public int $passPercent,
		public int $sortOrder,
	) {
	}
}
