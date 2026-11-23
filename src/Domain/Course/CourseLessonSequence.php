<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Section\Section;

/**
 * Single source of truth for linear progression / curriculum lesson order.
 *
 * Ungrouped lessons (section id 0) come first, then sections by sort_order,
 * then lessons within each section by sort_order.
 */
final class CourseLessonSequence {

	/**
	 * @param list<array{section: Section, lesson: ?Lesson}> $rows
	 * @return list<int>
	 */
	public static function orderedLessonIds( array $rows ): array {
		/** @var array<int, list<Lesson>> $bySection */
		$bySection   = array();
		$sectionMeta = array();

		foreach ( $rows as $row ) {
			$section                     = $row['section'];
			$lesson                      = $row['lesson'] ?? null;
			$sectionMeta[ $section->id ] = $section;
			if ( ! isset( $bySection[ $section->id ] ) ) {
				$bySection[ $section->id ] = array();
			}
			if ( null !== $lesson ) {
				$bySection[ $section->id ][] = $lesson;
			}
		}

		$sectionIds = array_keys( $bySection );
		usort(
			$sectionIds,
			static function ( int $a, int $b ) use ( $sectionMeta ): int {
				if ( 0 === $a && 0 !== $b ) {
					return -1;
				}
				if ( 0 !== $a && 0 === $b ) {
					return 1;
				}
				$sa = $sectionMeta[ $a ]->sortOrder ?? 0;
				$sb = $sectionMeta[ $b ]->sortOrder ?? 0;

				return $sa <=> $sb;
			}
		);

		$ids = array();
		foreach ( $sectionIds as $sectionId ) {
			$lessons = $bySection[ $sectionId ];
			usort(
				$lessons,
				static fn( Lesson $a, Lesson $b ): int => $a->sortOrder <=> $b->sortOrder
			);
			foreach ( $lessons as $lesson ) {
				$ids[] = (int) $lesson->id;
			}
		}

		return $ids;
	}
}
