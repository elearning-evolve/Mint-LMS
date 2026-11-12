<?php
declare(strict_types=1);

namespace MintLMS\Application\Lesson\Dto;

final readonly class CreateLessonDto {

	public function __construct(
		public string $title,
		public ?string $slug,
		public string $content,
		public ?string $videoUrl,
		public ?int $attachmentId,
		public bool $isPreview,
	) {
	}
}
