<?php
declare(strict_types=1);

namespace MintLMS\Application\Enrollment\Dto;

use MintLMS\Domain\Enrollment\Enrollment;

final readonly class EnrollmentDto {

	public function __construct(
		public int $id,
		public int $userId,
		public int $courseId,
		public string $status,
		public string $enrolledAt,
		public ?string $expiresAt,
		public ?string $completedAt,
	) {
	}

	public static function fromEnrollment( Enrollment $enrollment ): self {
		return new self(
			$enrollment->id,
			$enrollment->userId,
			$enrollment->courseId,
			$enrollment->status->value,
			$enrollment->enrolledAt->format( 'c' ),
			null !== $enrollment->expiresAt ? $enrollment->expiresAt->format( 'c' ) : null,
			null !== $enrollment->completedAt ? $enrollment->completedAt->format( 'c' ) : null,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'          => $this->id,
			'userId'      => $this->userId,
			'courseId'    => $this->courseId,
			'status'      => $this->status,
			'enrolledAt'  => $this->enrolledAt,
			'expiresAt'   => $this->expiresAt,
			'completedAt' => $this->completedAt,
		);
	}
}
