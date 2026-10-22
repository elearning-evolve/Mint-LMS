<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

final readonly class ReorderQuestionsDto {

	/**
	 * @param list<int> $questionIds
	 */
	public function __construct(
		public array $questionIds,
	) {
	}
}
