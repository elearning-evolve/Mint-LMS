<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

final readonly class CreateQuestionDto {

	/**
	 * @param list<string> $options
	 */
	public function __construct(
		public string $type,
		public string $prompt,
		public array $options,
		public string $correctAnswer,
	) {
	}
}
