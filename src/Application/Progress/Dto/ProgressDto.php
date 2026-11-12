<?php
declare(strict_types=1);

namespace MintLMS\Application\Progress\Dto;

use MintLMS\Domain\Progress\ProgressSummary;

final readonly class ProgressDto {

	public function __construct(
		public int $courseId,
		public int $lessonsDone,
		public int $lessonsTotal,
		public float $pctComplete,
		public ?int $lastLessonId,
		public bool $isCourseComplete,
		public string $updatedAt,
	) {
	}

	public static function fromSummary( ProgressSummary $summary ): self {
		return new self(
			$summary->courseId,
			$summary->lessonsDone,
			$summary->lessonsTotal,
			$summary->pctComplete,
			$summary->lastLessonId,
			$summary->isCourseComplete(),
			$summary->updatedAt->format( 'c' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'course_id'          => $this->courseId,
			'lessons_done'       => $this->lessonsDone,
			'lessons_total'      => $this->lessonsTotal,
			'pct_complete'       => $this->pctComplete,
			'last_lesson_id'     => $this->lastLessonId,
			'is_course_complete' => $this->isCourseComplete,
			'updated_at'         => $this->updatedAt,
		);
	}
}
