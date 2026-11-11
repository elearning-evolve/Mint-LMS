<?php
declare(strict_types=1);

namespace MintLMS\Domain\Section;

interface SectionRepositoryInterface {

	public function findById( int $id ): ?Section;

	/**
	 * @return list<Section>
	 */
	public function findByCourseId( int $courseId ): array;

	public function save( Section $section ): Section;

	public function delete( int $id ): bool;

	/**
	 * @param list<int> $sectionIds Ordered section IDs for the course.
	 */
	public function reorder( int $courseId, array $sectionIds ): void;

	public function nextSortOrder( int $courseId ): int;

	/**
	 * Loads all sections and lessons for a course in a single query.
	 *
	 * @return list<array{section: Section, lesson: ?\MintLMS\Domain\Lesson\Lesson}>
	 */
	public function loadStructureRows( int $courseId, bool $publishedOnly = false ): array;
}
