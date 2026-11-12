<?php
declare(strict_types=1);

namespace MintLMS\Application\Section\Dto;

final readonly class CreateSectionDto {

	public function __construct(
		public string $title,
	) {
	}
}
