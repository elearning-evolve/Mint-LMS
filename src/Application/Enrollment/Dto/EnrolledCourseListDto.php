<?php
declare(strict_types=1);

namespace MintLMS\Application\Enrollment\Dto;

final readonly class EnrolledCourseListDto {

	/**
	 * @param list<EnrolledCourseDto> $courses
	 */
	public function __construct(
		public array $courses,
		public int $total,
		public int $page,
		public int $perPage,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'items'   => array_map(
				static fn( EnrolledCourseDto $course ): array => $course->toArray(),
				$this->courses
			),
			'total'   => $this->total,
			'page'    => $this->page,
			'perPage' => $this->perPage,
		);
	}
}
