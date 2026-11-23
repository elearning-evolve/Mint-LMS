<?php
declare(strict_types=1);

namespace MintLMS\Domain\Event;

defined( 'ABSPATH' ) || exit;

final readonly class CoursePublished implements DomainEvent {

	public function __construct(
		public int $courseId,
		private \DateTimeImmutable $occurredAt,
	) {
		if ( $this->courseId <= 0 ) {
			throw new \InvalidArgumentException( 'Course ID must be a positive integer.' );
		}
	}

	public function name(): string {
		return 'course.published';
	}

	public function occurredAt(): \DateTimeImmutable {
		return $this->occurredAt;
	}
}
