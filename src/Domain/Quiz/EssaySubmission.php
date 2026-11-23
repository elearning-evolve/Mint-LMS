<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

defined( 'ABSPATH' ) || exit;

final readonly class EssaySubmission {

	public const STATUS_PENDING = 'pending';
	public const STATUS_GRADED  = 'graded';

	public function __construct(
		public int $id,
		public int $attemptId,
		public int $questionId,
		public int $userId,
		public int $quizId,
		public string $status,
		public int $pointsPossible,
		public float $pointsAwarded,
		public string $answerText,
		public ?int $attachmentId,
		public ?int $gradedBy,
		public ?\DateTimeImmutable $gradedAt,
		public \DateTimeImmutable $createdAt,
	) {
	}

	public function isPending(): bool {
		return self::STATUS_PENDING === $this->status;
	}
}
