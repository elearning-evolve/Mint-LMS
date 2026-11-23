<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

use MintLMS\Application\Quiz\Dto\QuizDto;

/**
 * Student / player read surface for quizzes.
 *
 * Separated from {@see QuizLifecycleInterface} (delete/trash hooks) so
 * StudentExperienceService can depend on an interface PHPUnit can double
 * while QuizService stays final.
 */
interface QuizStudentQueryInterface {

	public function get( int $quizId, int $userId, bool $forStudent = false ): QuizDto;

	public function getByLessonId(
		int $lessonId,
		int $userId,
		bool $forStudent = false,
		bool $editorReview = false
	): ?QuizDto;

	/**
	 * @return list<QuizDto>
	 */
	public function listByLessonId(
		int $lessonId,
		int $userId,
		bool $forStudent = false,
		bool $editorReview = false
	): array;

	public function hasPassed( int $userId, int $quizId ): bool;

	public function countUserAttempts( int $quizId, int $userId ): int;
}
