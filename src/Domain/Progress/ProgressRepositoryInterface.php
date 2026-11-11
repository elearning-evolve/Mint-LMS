<?php
declare(strict_types=1);

namespace MintLMS\Domain\Progress;

interface ProgressRepositoryInterface {

	public function findLessonCourseId( int $lessonId ): ?int;

	public function isUserEnrolled( int $userId, int $courseId ): bool;

	public function isLessonComplete( int $userId, int $lessonId ): bool;

	public function completeLesson(
		int $userId,
		int $lessonId,
		int $courseId,
		\DateTimeImmutable $completedAt,
	): CompleteLessonResult;

	public function uncompleteLesson(
		int $userId,
		int $lessonId,
		\DateTimeImmutable $updatedAt,
	): void;

	public function getSummary( int $userId, int $courseId ): ?ProgressSummary;

	public function recalculateSummary(
		int $userId,
		int $courseId,
		\DateTimeImmutable $updatedAt,
		?int $lastLessonId = null,
	): ProgressSummary;

	public function countLessonsInCourse( int $courseId ): int;

	/**
	 * @return list<int>
	 */
	public function getCompletedLessonIds( int $userId, int $courseId ): array;

	public function onLessonDeleted( int $lessonId, \DateTimeImmutable $updatedAt ): void;
}
