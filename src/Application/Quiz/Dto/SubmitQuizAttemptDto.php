<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

final readonly class SubmitQuizAttemptDto {

	/**
	 * @param array<int, string> $answers questionId => answer
	 */
	public function __construct(
		public array $answers,
	) {
	}
}
