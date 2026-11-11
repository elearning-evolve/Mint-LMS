<?php
declare(strict_types=1);

namespace MintLMS\Application\Section\Dto;

final readonly class ReorderSectionsDto {

	/**
	 * @param list<int> $sectionIds
	 */
	public function __construct(
		public array $sectionIds,
	) {
	}
}
