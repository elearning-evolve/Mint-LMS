<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

final readonly class CreateQuizDto {

	/**
	 * @param list<array<string, mixed>>|null $questions
	 */
	public function __construct(
		public string $title,
		public int $passPercent = 80,
		public ?array $questions = null,
	) {
	}
}
