<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

use MintLMS\Application\Quiz\QuestionAnswerCodec;
use MintLMS\Domain\Quiz\QuizQuestion;

final readonly class QuizQuestionDto {

	public function __construct(
		public int $id,
		public string $type,
		public string $prompt,
		/** @var list<string> */
		public array $options,
		public ?string $correctAnswer,
		public int $sortOrder,
	) {
	}

	public static function fromQuestion( QuizQuestion $question, bool $includeAnswer = true ): self {
		return new self(
			$question->id,
			$question->type,
			$question->prompt,
			$question->options,
			$includeAnswer ? $question->correctAnswer : null,
			$question->sortOrder,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$data = array(
			'id'        => $this->id,
			'type'      => $this->type,
			'prompt'    => $this->prompt,
			'options'   => $this->options,
			'sortOrder' => $this->sortOrder,
		);

		if ( null !== $this->correctAnswer ) {
			$data['correctAnswer'] = $this->correctAnswer;

			if ( QuizQuestion::TYPE_MCQ_MULTI === $this->type ) {
				$data['correctAnswers'] = QuestionAnswerCodec::decodeMulti( $this->correctAnswer );
			}
		}

		return $data;
	}
}
