<?php
declare(strict_types=1);

namespace MintLMS\Domain\Event;

use MintLMS\Domain\Shared\UserId;

final readonly class CourseCompleted implements DomainEvent {

	public function __construct(
		public UserId $userId,
		public int $courseId,
		private \DateTimeImmutable $occurredAt,
	) {
		if ( $this->courseId <= 0 ) {
			throw new \InvalidArgumentException( 'Course ID must be a positive integer.' );
		}
	}

	public function name(): string {
		return 'course.completed';
	}

	public function occurredAt(): \DateTimeImmutable {
		return $this->occurredAt;
	}
}
