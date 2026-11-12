<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

final readonly class UpdateQuestionDto {

	/**
	 * @param list<string>|null $options
	 */
	public function __construct(
		public ?string $type = null,
		public ?string $prompt = null,
		public ?array $options = null,
		public ?string $correctAnswer = null,
	) {
	}
}
