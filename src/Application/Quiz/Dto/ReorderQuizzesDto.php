<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

final readonly class ReorderQuizzesDto {

	/**
	 * @param list<int> $quizIds
	 */
	public function __construct(
		public array $quizIds,
	) {
	}
}
