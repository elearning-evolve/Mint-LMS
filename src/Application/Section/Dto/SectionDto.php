<?php
declare(strict_types=1);

namespace MintLMS\Application\Section\Dto;

use MintLMS\Domain\Section\Section;

final readonly class SectionDto {

	public function __construct(
		public int $id,
		public int $courseId,
		public string $title,
		public int $sortOrder,
		public string $createdAt,
	) {
	}

	public static function fromSection( Section $section ): self {
		return new self(
			$section->id,
			$section->courseId,
			$section->title,
			$section->sortOrder,
			$section->createdAt->format( 'c' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'        => $this->id,
			'courseId'  => $this->courseId,
			'title'     => $this->title,
			'sortOrder' => $this->sortOrder,
			'createdAt' => $this->createdAt,
		);
	}
}
