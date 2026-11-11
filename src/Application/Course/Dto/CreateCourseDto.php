<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

use MintLMS\Domain\Course\EnrollmentType;

final readonly class CreateCourseDto {

	public function __construct(
		public string $title,
		public ?string $slug,
		public string $description,
		public ?int $featuredImageId,
		public EnrollmentType $enrollmentType,
	) {
	}
}
