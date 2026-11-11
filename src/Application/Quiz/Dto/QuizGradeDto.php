<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

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
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'scorePercent'  => $this->scorePercent,
			'passed'        => $this->passed,
			'pendingEssay'  => $this->pendingEssay,
			'gradedCount'   => $this->gradedCount,
			'correctCount'  => $this->correctCount,
			'questions'     => $this->questions,
			'persisted'     => $this->persisted,
		);
	}
}
