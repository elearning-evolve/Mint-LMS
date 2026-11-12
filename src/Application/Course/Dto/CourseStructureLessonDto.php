<?php
declare(strict_types=1);

namespace MintLMS\Application\Course\Dto;

final readonly class CourseStructureLessonDto {

	public function __construct(
		public int $id,
		public int $sectionId,
		public string $title,
		public string $slug,
		public string $content,
		public ?string $videoUrl,
		public ?int $attachmentId,
		public bool $isPreview,
		public ?int $availableAfterDays,
		public bool $isDripLocked,
		public ?string $dripMessage,
		public int $sortOrder,
		public string $createdAt,
		public string $updatedAt,
		public ?int $featuredImageId = null,
		public string $featuredImageUrl = '',
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'                 => $this->id,
			'sectionId'          => $this->sectionId,
			'title'              => $this->title,
			'slug'               => $this->slug,
			'content'            => $this->content,
			'videoUrl'           => $this->videoUrl,
			'attachmentId'       => $this->attachmentId,
			'featuredImageId'    => $this->featuredImageId,
			'featuredImageUrl'   => $this->featuredImageUrl,
			'isPreview'          => $this->isPreview,
			'availableAfterDays' => $this->availableAfterDays,
			'isDripLocked'       => $this->isDripLocked,
			'dripMessage'        => $this->dripMessage,
			'sortOrder'          => $this->sortOrder,
			'createdAt'          => $this->createdAt,
			'updatedAt'          => $this->updatedAt,
		);
	}
}
