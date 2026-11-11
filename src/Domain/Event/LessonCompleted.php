<?php
declare(strict_types=1);

namespace MintLMS\Domain\Event;

use MintLMS\Domain\Shared\UserId;

final readonly class LessonCompleted implements DomainEvent {

	public function __construct(
		public UserId $userId,
		public int $lessonId,
		public int $courseId,
		private \DateTimeImmutable $occurredAt,
	) {
		if ( $this->lessonId <= 0 ) {
			throw new \InvalidArgumentException( 'Lesson ID must be a positive integer.' );
		}

		if ( $this->courseId <= 0 ) {
			throw new \InvalidArgumentException( 'Course ID must be a positive integer.' );
		}
	}

	public function name(): string {
		return 'lesson.completed';
	}

	public function occurredAt(): \DateTimeImmutable {
		return $this->occurredAt;
	}
}
