<?php
declare(strict_types=1);

namespace MintLMS\Application\Enrollment\Dto;

final readonly class StudentListDto {

	/**
	 * @param list<StudentDto> $students
	 */
	public function __construct(
		public array $students,
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
				static fn( StudentDto $student ): array => $student->toArray(),
				$this->students
			),
			'total'   => $this->total,
			'page'    => $this->page,
			'perPage' => $this->perPage,
		);
	}
}
