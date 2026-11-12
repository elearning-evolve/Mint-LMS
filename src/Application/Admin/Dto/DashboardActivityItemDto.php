<?php
declare(strict_types=1);

namespace MintLMS\Application\Admin\Dto;

final class DashboardActivityItemDto {

	public function __construct(
		public readonly string $initials,
		public readonly string $text,
		public readonly string $when,
		public readonly string $avatarBg,
		public readonly string $avatarInk,
	) {
	}
}
