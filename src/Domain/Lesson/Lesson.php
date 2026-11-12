<?php
declare(strict_types=1);

namespace MintLMS\Domain\Lesson;

final readonly class Lesson {

	public function __construct(
		public int $id,
		public int $sectionId,
		public int $courseId,
		public string $title,
		public string $slug,
		public string $content,
		public ?string $videoUrl,
		public ?int $attachmentId,
		public bool $isPreview,
		public ?int $availableAfterDays,
		public int $sortOrder,
		public \DateTimeImmutable $createdAt,
		public \DateTimeImmutable $updatedAt,
		public ?int $featuredImageId = null,
	) {
	}
}
