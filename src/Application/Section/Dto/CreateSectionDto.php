<?php
declare(strict_types=1);

namespace MintLMS\Application\Section\Dto;

defined( 'ABSPATH' ) || exit;

final readonly class CreateSectionDto {

	public function __construct(
		public string $title,
	) {
	}
}
