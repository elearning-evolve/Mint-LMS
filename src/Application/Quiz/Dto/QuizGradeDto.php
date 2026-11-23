<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

defined( 'ABSPATH' ) || exit;

final readonly class QuizGradeDto {

	/**
	 * @param list<array<string, mixed>> $questions
	 */
	public function __construct(
		public float $scorePercent,
		public bool $passed,
		public bool $pendingEssay,
		public int $gradedCount,
		public int $correctCount,
		public array $questions,
		public bool $persisted = false,
		public float $pointsEarned = 0.0,
		public float $pointsPossible = 0.0,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'scorePercent'   => $this->scorePercent,
			'passed'         => $this->passed,
			'pendingEssay'   => $this->pendingEssay,
			'gradedCount'    => $this->gradedCount,
			'correctCount'   => $this->correctCount,
			'questions'      => $this->questions,
			'persisted'      => $this->persisted,
			'pointsEarned'   => $this->pointsEarned,
			'pointsPossible' => $this->pointsPossible,
		);
	}
}
