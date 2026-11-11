<?php
declare(strict_types=1);

namespace MintLMS\Application\Student\Dto;

use MintLMS\Application\Course\Dto\CourseStructureDto;

final readonly class StudentCourseOverviewDto {

	/**
	 * @param list<int> $completedLessonIds
	 * @param list<array{
	 *   id: int,
	 *   title: string,
	 *   meta: string,
	 *   lessons: list<array{
	 *     id: int,
	 *     title: string,
	 *     meta: string,
	 *     accessible: bool,
	 *     playerUrl: string,
	 *     quizzes: list<array{
	 *       id: int,
	 *       title: string,
	 *       meta: string,
	 *       questions: list<array{id: int, text: string}>
	 *     }>
	 *   }>
	 * }> $contentOutline
	 */
	public function __construct(
		public int $courseId,
		public string $title,
		public string $slug,
		public string $description,
		public ?int $featuredImageId,
		public string $enrollmentType,
		public bool $isEnrolled,
		public bool $canEnroll,
		public bool $isLoggedIn,
		public ?float $progressPct,
		public ?int $lastLessonId,
		public ?int $firstLessonId,
		public array $completedLessonIds,
		public CourseStructureDto $structure,
		public string $status = 'draft',
		public string $authorName = '',
		public ?string $lastActivityLabel = null,
		public bool $isEditorPreview = false,
		public array $contentOutline = array(),
		public bool $curriculumVisible = true,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'courseId'           => $this->courseId,
			'title'              => $this->title,
			'slug'               => $this->slug,
			'description'        => $this->description,
			'featuredImageId'    => $this->featuredImageId,
			'enrollmentType'     => $this->enrollmentType,
			'isEnrolled'         => $this->isEnrolled,
			'canEnroll'          => $this->canEnroll,
			'isLoggedIn'         => $this->isLoggedIn,
			'progressPct'        => $this->progressPct,
			'lastLessonId'       => $this->lastLessonId,
			'firstLessonId'      => $this->firstLessonId,
			'completedLessonIds' => $this->completedLessonIds,
			'structure'          => $this->structure->toArray(),
			'status'             => $this->status,
			'authorName'         => $this->authorName,
			'lastActivityLabel'  => $this->lastActivityLabel,
			'isEditorPreview'    => $this->isEditorPreview,
			'contentOutline'     => $this->contentOutline,
			'curriculumVisible'  => $this->curriculumVisible,
		);
	}
}
