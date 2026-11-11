<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;

final readonly class UpdateCourseDto {

	/**
	 * @param list<int>|null $prerequisiteCourseIds
	 */
	public function __construct(
		public ?string $title = null,
		public ?string $slug = null,
		public ?string $description = null,
		public bool $updateFeaturedImage = false,
		public ?int $featuredImageId = null,
		public ?EnrollmentType $enrollmentType = null,
		public ?CourseStatus $status = null,
		public ?bool $emailOnPublish = null,
		public ?bool $studentComplete = null,
		public ?bool $certificate = null,
		public ?string $progression = null,
		public ?bool $expireAccess = null,
		public ?int $expireAccessDays = null,
		public ?bool $prerequisitesEnabled = null,
		public ?array $prerequisiteCourseIds = null,
		public ?string $prerequisiteCompare = null,
		public ?string $accessStartAt = null,
		public bool $clearAccessStartAt = false,
		public ?string $accessEndAt = null,
		public bool $clearAccessEndAt = false,
		public ?int $seatLimit = null,
		public ?bool $materialsEnabled = null,
		public ?string $materialsContent = null,
		public ?string $lessonListVisibility = null,
		public ?bool $paginationEnabled = null,
		public ?int $lessonsPerPage = null,
		public ?int $groupsPerPage = null,
		public ?string $completionRedirect = null,
		public ?int $pointsOnComplete = null,
	) {
	}
}
