<?php
declare(strict_types=1);

namespace MintLMS\Domain\Event;

use MintLMS\Domain\Shared\UserId;

final readonly class EnrollmentCreated implements DomainEvent {

	public function __construct(
		public UserId $userId,
		public int $courseId,
		public int $enrollmentId,
		private \DateTimeImmutable $occurredAt,
	) {
		if ( $this->courseId <= 0 ) {
			throw new \InvalidArgumentException( 'Course ID must be a positive integer.' );
		}

		if ( $this->enrollmentId <= 0 ) {
			throw new \InvalidArgumentException( 'Enrollment ID must be a positive integer.' );
		}
	}

	public function name(): string {
		return 'enrollment.created';
	}

	public function occurredAt(): \DateTimeImmutable {
		return $this->occurredAt;
	}
}
