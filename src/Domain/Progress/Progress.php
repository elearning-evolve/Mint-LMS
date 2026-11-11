<?php
declare(strict_types=1);

namespace MintLMS\Domain\Progress;

use MintLMS\Domain\Shared\UserId;

final readonly class Progress {

	public function __construct(
		public int $id,
		public UserId $userId,
		public int $lessonId,
		public int $courseId,
		public string $status,
		public \DateTimeImmutable $completedAt,
	) {
		if ( $this->id < 0 ) {
			throw new \InvalidArgumentException( 'Progress ID must be zero or a positive integer.' );
		}

		if ( $this->lessonId <= 0 ) {
			throw new \InvalidArgumentException( 'Lesson ID must be a positive integer.' );
		}

		if ( $this->courseId <= 0 ) {
			throw new \InvalidArgumentException( 'Course ID must be a positive integer.' );
		}
	}
}
