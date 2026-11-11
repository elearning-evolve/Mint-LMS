<?php
declare(strict_types=1);

namespace MintLMS\Domain\Enrollment;

final readonly class Enrollment {

	public function __construct(
		public int $id,
		public int $userId,
		public int $courseId,
		public EnrollmentStatus $status,
		public \DateTimeImmutable $enrolledAt,
		public ?\DateTimeImmutable $expiresAt,
		public ?\DateTimeImmutable $completedAt,
	) {
	}
}
