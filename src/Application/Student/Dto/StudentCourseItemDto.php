<?php
declare(strict_types=1);

namespace MintLMS\Application\Student\Dto;

final readonly class StudentCourseItemDto {

	public function __construct(
		public int $courseId,
		public string $title,
		public string $slug,
		public float $progressPct,
		public ?int $lastLessonId,
		public ?int $firstLessonId,
		public bool $isComplete,
		public ?int $featuredImageId,
		public string $enrollmentType,
		public int $totalLessons = 0,
		public int $completedLessons = 0,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'courseId'        => $this->courseId,
			'title'           => $this->title,
			'slug'            => $this->slug,
			'progressPct'     => $this->progressPct,
			'lastLessonId'    => $this->lastLessonId,
			'firstLessonId'   => $this->firstLessonId,
			'isComplete'      => $this->isComplete,
			'featuredImageId' => $this->featuredImageId,
			'enrollmentType'  => $this->enrollmentType,
			'totalLessons'    => $this->totalLessons,
			'completedLessons' => $this->completedLessons,
		);
	}
}
