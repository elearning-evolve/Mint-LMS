<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

final readonly class Course {

	public function __construct(
		public int $id,
		public string $title,
		public string $slug,
		public string $description,
		public ?int $featuredImageId,
		public CourseStatus $status,
		public EnrollmentType $enrollmentType,
		public int $authorId,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $updatedAt,
		public CourseSettings $settings = new CourseSettings(),
	) {
	}
}
