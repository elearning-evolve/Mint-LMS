<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz\Dto;

final readonly class UpdateQuizDto {

	public function __construct(
		public ?string $title = null,
		public ?int $passPercent = null,
	) {
	}
}
