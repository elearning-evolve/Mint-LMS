<?php
declare(strict_types=1);

namespace MintLMS\Domain\Lesson;

interface LessonRepositoryInterface {

	public function findById( int $id ): ?Lesson;

	public function findBySlugAndCourseId( string $slug, int $courseId ): ?Lesson;

	/**
	 * @return list<Lesson>
	 */
	public function findBySectionId( int $sectionId, bool $publishedOnly = false ): array;

	/**
	 * Lessons for a course bucket (sectionId 0 = ungrouped on the course).
	 *
	 * @return list<Lesson>
	 */
	public function findByCourseIdAndSectionId( int $courseId, int $sectionId, bool $publishedOnly = false ): array;

	public function save( Lesson $lesson ): Lesson;

	public function delete( int $id ): bool;

	public function deleteBySectionId( int $sectionId ): void;

	/**
	 * @param list<int> $lessonIds Ordered lesson IDs for the section.
	 */
	public function reorder( int $sectionId, array $lessonIds ): void;

	/**
	 * Next sort order within a section bucket.
	 * Pass $courseId when sectionId is 0 so library lessons are not mixed in.
	 */
	public function nextSortOrder( int $sectionId, ?int $courseId = null ): int;
}
