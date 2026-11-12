<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

final readonly class QuizAttempt {

	/**
	 * @param array<int, string> $answers
	 */
	public function __construct(
		public int $id,
		public int $userId,
		public int $quizId,
		public float $scorePercent,
		public bool $passed,
		public array $answers,
		public \DateTimeImmutable $completedAt,
	) {
	}
}
