<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

interface QuizRepositoryInterface {

	public function findById( int $id ): ?Quiz;

	public function findByLessonId( int $lessonId, bool $publishedOnly = false ): ?Quiz;

	/**
	 * All quizzes linked to a lesson, ordered by sort_order.
	 *
	 * @return list<Quiz>
	 */
	public function findAllByLessonId( int $lessonId, bool $publishedOnly = false ): array;

	/**
	 * Next sort_order value for a new quiz on this lesson.
	 */
	public function nextQuizSortOrderForLesson( int $lessonId ): int;

	/**
	 * @return list<QuizQuestion>
	 */
	public function findQuestionsByQuizId( int $quizId, bool $publishedOnly = false ): array;

	public function findQuestionById( int $questionId ): ?QuizQuestion;

	public function saveQuiz( Quiz $quiz ): Quiz;

	public function saveQuestion( QuizQuestion $question ): QuizQuestion;

	public function deleteQuiz( int $id ): bool;

	public function deleteQuestion( int $id ): bool;

	public function deleteQuestionsByQuizId( int $quizId ): void;

	public function deleteByLessonId( int $lessonId ): void;

	public function nextQuestionSortOrder( int $quizId ): int;

	/**
	 * Persist quiz order within a lesson (index = sort_order).
	 *
	 * @param list<int> $quizIds
	 */
	public function reorderQuizzes( int $lessonId, array $quizIds ): void;

	/**
	 * Persist question order within a quiz (index = sort_order).
	 *
	 * @param list<int> $questionIds
	 */
	public function reorderQuestions( int $quizId, array $questionIds ): void;

	public function saveAttempt( QuizAttempt $attempt ): QuizAttempt;

	/**
	 * @return list<QuizAttempt>
	 */
	public function findAttemptsByQuizId( int $quizId ): array;

	public function deleteAttemptsByQuizId( int $quizId ): void;

	public function findBestPassedAttempt( int $userId, int $quizId ): ?QuizAttempt;

	public function hasPassed( int $userId, int $quizId ): bool;
}
