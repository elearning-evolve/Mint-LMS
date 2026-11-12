<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

final readonly class CourseStructureSectionDto {

	/**
	 * @param list<CourseStructureLessonDto> $lessons
	 */
	public function __construct(
		public int $id,
		public string $title,
		public int $sortOrder,
		public string $createdAt,
		public array $lessons,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'        => $this->id,
			'title'     => $this->title,
			'sortOrder' => $this->sortOrder,
			'createdAt' => $this->createdAt,
			'lessons'   => array_map(
				static fn( CourseStructureLessonDto $lesson ): array => $lesson->toArray(),
				$this->lessons
			),
		);
	}
}
