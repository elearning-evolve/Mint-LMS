<?php
declare(strict_types=1);

namespace MintLMS\Domain\Progress;

use MintLMS\Domain\Shared\UserId;

final readonly class ProgressSummary {

	public function __construct(
		public UserId $userId,
		public int $courseId,
		public int $lessonsDone,
		public int $lessonsTotal,
		public float $pctComplete,
		public ?int $lastLessonId,
		public \DateTimeImmutable $updatedAt,
	) {
		if ( $this->courseId <= 0 ) {
			throw new \InvalidArgumentException( 'Course ID must be a positive integer.' );
		}

		if ( $this->lessonsDone < 0 ) {
			throw new \InvalidArgumentException( 'Lessons done cannot be negative.' );
		}

		if ( $this->lessonsTotal < 0 ) {
			throw new \InvalidArgumentException( 'Lessons total cannot be negative.' );
		}

		if ( $this->pctComplete < 0.0 || $this->pctComplete > 100.0 ) {
			throw new \InvalidArgumentException( 'Percent complete must be between 0 and 100.' );
		}
	}

	public function isCourseComplete(): bool {
		return $this->lessonsTotal > 0 && $this->lessonsDone >= $this->lessonsTotal;
	}
}
