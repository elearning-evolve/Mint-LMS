<?php
declare(strict_types=1);

namespace MintLMS\Application\Student\Dto;

defined( 'ABSPATH' ) || exit;

final readonly class StudentCatalogCourseDto {

	public function __construct(
		public int $courseId,
		public string $title,
		public string $description,
		public ?int $featuredImageId,
		public string $enrollmentType,
	) {
	}
}
