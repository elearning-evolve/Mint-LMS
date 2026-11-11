<?php
declare(strict_types=1);

namespace MintLMS\Application\Lesson\Dto;

final readonly class UpdateLessonDto {

	public function __construct(
		public ?string $title = null,
		public ?string $slug = null,
		public ?string $content = null,
		public ?string $videoUrl = null,
		public ?int $attachmentId = null,
		public ?bool $isPreview = null,
		public ?int $availableAfterDays = null,
		public bool $hasAvailableAfterDays = false,
		public ?int $featuredImageId = null,
		public bool $updateFeaturedImage = false,
	) {
	}
}
