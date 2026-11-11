<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

use MintLMS\Domain\Quiz\Quiz;
use MintLMS\Domain\Quiz\QuizQuestion;

final readonly class QuizDto {

	/**
	 * @param list<QuizQuestionDto> $questions
	 */
	public function __construct(
		public int $id,
		public int $lessonId,
		public int $courseId,
		public string $title,
		public int $passPercent,
		public int $sortOrder,
		public array $questions,
	) {
	}

	/**
	 * @param list<QuizQuestion> $questions
	 */
	public static function fromQuiz( Quiz $quiz, array $questions, bool $includeAnswers = true ): self {
		$questionDtos = array();

		foreach ( $questions as $question ) {
			$questionDtos[] = QuizQuestionDto::fromQuestion( $question, $includeAnswers );
		}

		return new self(
			$quiz->id,
			$quiz->lessonId,
			$quiz->courseId,
			$quiz->title,
			$quiz->passPercent,
			$quiz->sortOrder,
			$questionDtos,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'          => $this->id,
			'lessonId'    => $this->lessonId,
			'courseId'    => $this->courseId,
			'title'       => $this->title,
			'passPercent' => $this->passPercent,
			'sortOrder'   => $this->sortOrder,
			'questions'   => array_map(
				static fn( QuizQuestionDto $question ): array => $question->toArray(),
				$this->questions
			),
		);
	}
}
