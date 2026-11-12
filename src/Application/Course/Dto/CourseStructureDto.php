<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

final readonly class CourseStructureDto {

	/**
	 * @param list<CourseStructureSectionDto> $sections
	 */
	public function __construct(
		public int $courseId,
		public string $title,
		public string $slug,
		public string $status,
		public array $sections,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'courseId' => $this->courseId,
			'title'    => $this->title,
			'slug'     => $this->slug,
			'status'   => $this->status,
			'sections' => array_map(
				static fn( CourseStructureSectionDto $section ): array => $section->toArray(),
				$this->sections
			),
		);
	}
}
