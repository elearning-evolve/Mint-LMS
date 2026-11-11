<?php
declare(strict_types=1);

namespace MintLMS\Application\Lesson\Dto;

use MintLMS\Domain\Lesson\Lesson;

final readonly class LessonDto {

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
		public string $createdAt,
		public string $updatedAt,
		public ?int $featuredImageId = null,
	) {
	}

	public static function fromLesson( Lesson $lesson ): self {
		return new self(
			$lesson->id,
			$lesson->sectionId,
			$lesson->courseId,
			$lesson->title,
			$lesson->slug,
			$lesson->content,
			$lesson->videoUrl,
			$lesson->attachmentId,
			$lesson->isPreview,
			$lesson->availableAfterDays,
			$lesson->sortOrder,
			$lesson->createdAt->format( 'c' ),
			$lesson->updatedAt->format( 'c' ),
			$lesson->featuredImageId,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'                  => $this->id,
			'sectionId'           => $this->sectionId,
			'courseId'            => $this->courseId,
			'title'               => $this->title,
			'slug'                => $this->slug,
			'content'             => $this->content,
			'videoUrl'            => $this->videoUrl,
			'attachmentId'        => $this->attachmentId,
			'featuredImageId'     => $this->featuredImageId,
			'isPreview'           => $this->isPreview,
			'availableAfterDays'  => $this->availableAfterDays,
			'sortOrder'           => $this->sortOrder,
			'createdAt'           => $this->createdAt,
			'updatedAt'           => $this->updatedAt,
		);
	}
}
