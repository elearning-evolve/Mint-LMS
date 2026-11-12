<?php
declare(strict_types=1);

namespace MintLMS\Application\Student\Dto;

use MintLMS\Application\Course\Dto\CourseStructureDto;
use MintLMS\Application\Course\Dto\CourseStructureLessonDto;
use MintLMS\Application\Quiz\Dto\QuizDto;

final readonly class StudentPlayerContextDto {

	/**
	 * @param list<int> $completedLessonIds
	 */
	public function __construct(
		public int $courseId,
		public string $courseTitle,
		public float $progressPct,
		public bool $isEnrolled,
		public bool $isCourseComplete,
		public CourseStructureDto $structure,
		public CourseStructureLessonDto $currentLesson,
		public ?int $previousLessonId,
		public ?int $nextLessonId,
		public array $completedLessonIds,
		public bool $isCurrentLessonComplete,
		public ?QuizDto $quiz = null,
		public bool $quizRequired = false,
		public bool $hasPassedQuiz = true,
		public bool $canMarkComplete = true,
		public bool $isReviewMode = false,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'courseId'                => $this->courseId,
			'courseTitle'             => $this->courseTitle,
			'progressPct'             => $this->progressPct,
			'isEnrolled'              => $this->isEnrolled,
			'isCourseComplete'        => $this->isCourseComplete,
			'structure'               => $this->structure->toArray(),
			'currentLesson'           => $this->currentLesson->toArray(),
			'previousLessonId'        => $this->previousLessonId,
			'nextLessonId'            => $this->nextLessonId,
			'completedLessonIds'      => $this->completedLessonIds,
			'isCurrentLessonComplete' => $this->isCurrentLessonComplete,
			'quiz'                    => null !== $this->quiz ? $this->quiz->toArray() : null,
			'quizRequired'            => $this->quizRequired,
			'hasPassedQuiz'           => $this->hasPassedQuiz,
			'canMarkComplete'         => $this->canMarkComplete,
			'isReviewMode'            => $this->isReviewMode,
		);
	}
}
