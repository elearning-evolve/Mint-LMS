<?php
declare(strict_types=1);

namespace MintLMS\Domain\Section;

final readonly class Section {

	public function __construct(
		public int $id,
		public int $courseId,
		public string $title,
		public int $sortOrder,
		public \DateTimeImmutable $createdAt,
	) {
	}
}
