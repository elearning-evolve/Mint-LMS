<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Domain\Course;

use MintLMS\Domain\Course\CourseLessonSequence;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Section\Section;
use PHPUnit\Framework\TestCase;

final class CourseLessonSequenceTest extends TestCase {

	public function test_ungrouped_lessons_come_before_section_lessons(): void {
		$now = new \DateTimeImmutable( '2026-01-01 00:00:00' );

		$groupedSection = new Section( 5, 10, 'Group A', 0, $now );
		$ungrouped      = new Section( 0, 10, '', -1, $now );

		$rows = array(
			array(
				'section' => $groupedSection,
				'lesson'  => $this->lesson( 20, 5, 0, $now ),
			),
			array(
				'section' => $groupedSection,
				'lesson'  => $this->lesson( 21, 5, 1, $now ),
			),
			array(
				'section' => $ungrouped,
				'lesson'  => $this->lesson( 10, 0, 0, $now ),
			),
			array(
				'section' => $ungrouped,
				'lesson'  => $this->lesson( 11, 0, 1, $now ),
			),
		);

		$this->assertSame(
			array( 10, 11, 20, 21 ),
			CourseLessonSequence::orderedLessonIds( $rows )
		);
	}

	public function test_sections_and_lessons_respect_sort_order(): void {
		$now = new \DateTimeImmutable( '2026-01-01 00:00:00' );

		$sectionB = new Section( 2, 10, 'B', 1, $now );
		$sectionA = new Section( 1, 10, 'A', 0, $now );

		$rows = array(
			array(
				'section' => $sectionB,
				'lesson'  => $this->lesson( 30, 2, 1, $now ),
			),
			array(
				'section' => $sectionB,
				'lesson'  => $this->lesson( 29, 2, 0, $now ),
			),
			array(
				'section' => $sectionA,
				'lesson'  => $this->lesson( 12, 1, 0, $now ),
			),
		);

		$this->assertSame(
			array( 12, 29, 30 ),
			CourseLessonSequence::orderedLessonIds( $rows )
		);
	}

	private function lesson( int $id, int $sectionId, int $sortOrder, \DateTimeImmutable $now ): Lesson {
		return new Lesson(
			$id,
			$sectionId,
			10,
			'Lesson ' . $id,
			'lesson-' . $id,
			'',
			null,
			null,
			false,
			null,
			$sortOrder,
			$now,
			$now,
		);
	}
}
