<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

use MintLMS\Domain\Quiz\QuizAttempt;

final readonly class QuizAttemptDto {

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
		public string $completedAt,
		public ?string $userDisplayName = null,
		public ?QuizGradeDto $grade = null,
	) {
	}

	public static function fromAttempt( QuizAttempt $attempt, ?string $userDisplayName = null, ?QuizGradeDto $grade = null ): self {
		return new self(
			$attempt->id,
			$attempt->userId,
			$attempt->quizId,
			$attempt->scorePercent,
			$attempt->passed,
			$attempt->answers,
			$attempt->completedAt->format( 'c' ),
			$userDisplayName,
			$grade,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$data = array(
			'id'            => $this->id,
			'userId'        => $this->userId,
			'quizId'        => $this->quizId,
			'scorePercent'  => $this->scorePercent,
			'passed'        => $this->passed,
			'answers'       => $this->answers,
			'completedAt'   => $this->completedAt,
		);

		if ( null !== $this->userDisplayName ) {
			$data['userDisplayName'] = $this->userDisplayName;
		}

		if ( null !== $this->grade ) {
			$data['review'] = $this->grade->toArray();
		}

		return $data;
	}
}
