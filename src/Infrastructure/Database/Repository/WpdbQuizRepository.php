<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Quiz\Quiz;
use MintLMS\Domain\Quiz\QuizAttempt;
use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Domain\Quiz\QuizRepositoryInterface;
use MintLMS\Infrastructure\Database\Schema;

final class WpdbQuizRepository implements QuizRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findById( int $id ): ?Quiz {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizzesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, lesson_id, course_id, title, pass_percent, sort_order
				FROM %i
				WHERE id = %d',
				$table,
				$id
			)
		);
		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToQuiz( $row );
	}

	public function findByLessonId( int $lessonId, bool $publishedOnly = false ): ?Quiz {
		$quizzes = $this->findAllByLessonId( $lessonId, $publishedOnly );

		return $quizzes[0] ?? null;
	}

	public function findAllByLessonId( int $lessonId, bool $publishedOnly = false ): array {
		$wpdb = $this->wpdb;
		unset( $publishedOnly );
		$table = Schema::validateTable( Schema::quizzesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$rows    = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, lesson_id, course_id, title, pass_percent, sort_order
				FROM %i
				WHERE lesson_id = %d
				ORDER BY sort_order ASC, id ASC',
				$table,
				$lessonId
			)
		);
		$quizzes = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$quizzes[] = $this->mapRowToQuiz( $row );
			}
		}

		return $quizzes;
	}

	public function nextQuizSortOrderForLesson( int $lessonId ): int {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizzesTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$max = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT MAX(sort_order) FROM %i WHERE lesson_id = %d',
				$table,
				$lessonId
			)
		);
		if ( null === $max || '' === $max ) {
			return 0;
		}

		return (int) $max + 1;
	}

	public function findQuestionsByQuizId( int $quizId, bool $publishedOnly = false ): array {
		$wpdb = $this->wpdb;
		unset( $publishedOnly );
		$table = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$rows      = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, quiz_id, type, prompt, options_json, correct_answer, sort_order
				FROM %i
				WHERE quiz_id = %d
				ORDER BY sort_order ASC, id ASC',
				$table,
				$quizId
			)
		);
		$questions = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$questions[] = $this->mapRowToQuestion( $row );
			}
		}

		return $questions;
	}

	public function findQuestionById( int $questionId ): ?QuizQuestion {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, quiz_id, type, prompt, options_json, correct_answer, sort_order
				FROM %i
				WHERE id = %d',
				$table,
				$questionId
			)
		);
		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToQuestion( $row );
	}

	public function saveQuiz( Quiz $quiz ): Quiz {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizzesTable( $wpdb->prefix ), $wpdb->prefix );

		if ( 0 === $quiz->id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table,
				array(
					'lesson_id'    => $quiz->lessonId,
					'course_id'    => $quiz->courseId,
					'title'        => $quiz->title,
					'pass_percent' => $quiz->passPercent,
					'sort_order'   => $quiz->sortOrder,
				),
				array( '%d', '%d', '%s', '%d', '%d' )
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert quiz.' );
			}

			return new Quiz(
				(int) $wpdb->insert_id,
				$quiz->lessonId,
				$quiz->courseId,
				$quiz->title,
				$quiz->passPercent,
				$quiz->sortOrder,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table,
			array(
				'title'        => $quiz->title,
				'pass_percent' => $quiz->passPercent,
				'sort_order'   => $quiz->sortOrder,
			),
			array( 'id' => $quiz->id ),
			array( '%s', '%d', '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update quiz.' );
		}

		return $quiz;
	}

	public function saveQuestion( QuizQuestion $question ): QuizQuestion {
		$wpdb        = $this->wpdb;
		$table       = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );
		$optionsJson = wp_json_encode( $question->options );
		$optionsJson = is_string( $optionsJson ) ? $optionsJson : '[]';

		if ( 0 === $question->id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$table,
				array(
					'quiz_id'        => $question->quizId,
					'type'           => $question->type,
					'prompt'         => $question->prompt,
					'options_json'   => $optionsJson,
					'correct_answer' => $question->correctAnswer,
					'sort_order'     => $question->sortOrder,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d' )
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'Failed to insert quiz question.' );
			}

			return new QuizQuestion(
				(int) $wpdb->insert_id,
				$question->quizId,
				$question->type,
				$question->prompt,
				$question->options,
				$question->correctAnswer,
				$question->sortOrder,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table,
			array(
				'type'           => $question->type,
				'prompt'         => $question->prompt,
				'options_json'   => $optionsJson,
				'correct_answer' => $question->correctAnswer,
				'sort_order'     => $question->sortOrder,
			),
			array( 'id' => $question->id ),
			array( '%s', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update quiz question.' );
		}

		return $question;
	}

	public function deleteQuiz( int $id ): bool {
		$wpdb = $this->wpdb;
		$this->deleteQuestionsByQuizId( $id );

		$attemptsTable = Schema::validateTable( Schema::quizAttemptsTable( $wpdb->prefix ), $wpdb->prefix );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $attemptsTable, array( 'quiz_id' => $id ), array( '%d' ) );

		$table = Schema::validateTable( Schema::quizzesTable( $wpdb->prefix ), $wpdb->prefix );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		return false !== $deleted && $deleted > 0;
	}

	public function deleteAttemptsByQuizId( int $quizId ): void {
		$wpdb = $this->wpdb;
		if ( $quizId <= 0 ) {
			return;
		}
		$attemptsTable = Schema::validateTable( Schema::quizAttemptsTable( $wpdb->prefix ), $wpdb->prefix );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $attemptsTable, array( 'quiz_id' => $quizId ), array( '%d' ) );
	}

	public function deleteQuestion( int $id ): bool {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		return false !== $deleted && $deleted > 0;
	}

	public function deleteQuestionsByQuizId( int $quizId ): void {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'quiz_id' => $quizId ), array( '%d' ) );
	}

	public function deleteByLessonId( int $lessonId ): void {
		foreach ( $this->findAllByLessonId( $lessonId ) as $quiz ) {
			$this->deleteQuiz( $quiz->id );
		}
	}

	public function nextQuestionSortOrder( int $quizId ): int {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$max = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT MAX(sort_order) FROM %i WHERE quiz_id = %d',
				$table,
				$quizId
			)
		);
		if ( null === $max || '' === $max ) {
			return 0;
		}

		return (int) $max + 1;
	}

	/**
	 * @param list<int> $quizIds
	 */
	public function reorderQuizzes( int $lessonId, array $quizIds ): void {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizzesTable( $wpdb->prefix ), $wpdb->prefix );

		foreach ( $quizIds as $index => $quizId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'sort_order' => (int) $index ),
				array(
					'id'        => (int) $quizId,
					'lesson_id' => $lessonId,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}
	}

	/**
	 * @param list<int> $questionIds
	 */
	public function reorderQuestions( int $quizId, array $questionIds ): void {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );

		foreach ( $questionIds as $index => $questionId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'sort_order' => (int) $index ),
				array(
					'id'      => (int) $questionId,
					'quiz_id' => $quizId,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}
	}

	public function saveAttempt( QuizAttempt $attempt ): QuizAttempt {
		$wpdb        = $this->wpdb;
		$table       = Schema::validateTable( Schema::quizAttemptsTable( $wpdb->prefix ), $wpdb->prefix );
		$answersJson = wp_json_encode( $attempt->answers );
		$answersJson = is_string( $answersJson ) ? $answersJson : '{}';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'       => $attempt->userId,
				'quiz_id'       => $attempt->quizId,
				'score_percent' => $attempt->scorePercent,
				'passed'        => $attempt->passed ? 1 : 0,
				'answers_json'  => $answersJson,
				'completed_at'  => $attempt->completedAt->format( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%f', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to insert quiz attempt.' );
		}

		return new QuizAttempt(
			(int) $wpdb->insert_id,
			$attempt->userId,
			$attempt->quizId,
			$attempt->scorePercent,
			$attempt->passed,
			$attempt->answers,
			$attempt->completedAt,
		);
	}

	public function findAttemptsByQuizId( int $quizId ): array {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizAttemptsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$rows     = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, user_id, quiz_id, score_percent, passed, answers_json, completed_at
				FROM %i
				WHERE quiz_id = %d
				ORDER BY completed_at DESC',
				$table,
				$quizId
			)
		);
		$attempts = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$attempts[] = $this->mapRowToAttempt( $row );
			}
		}

		return $attempts;
	}

	public function findBestPassedAttempt( int $userId, int $quizId ): ?QuizAttempt {
		$wpdb  = $this->wpdb;
		$table = Schema::validateTable( Schema::quizAttemptsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom LMS table; request-scoped.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, user_id, quiz_id, score_percent, passed, answers_json, completed_at
				FROM %i
				WHERE user_id = %d AND quiz_id = %d AND passed = 1
				ORDER BY score_percent DESC, completed_at DESC
				LIMIT 1',
				$table,
				$userId,
				$quizId
			)
		);
		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToAttempt( $row );
	}

	public function hasPassed( int $userId, int $quizId ): bool {
		return null !== $this->findBestPassedAttempt( $userId, $quizId );
	}

	private function mapRowToQuiz( object $row ): Quiz {
		return new Quiz(
			(int) $row->id,
			(int) $row->lesson_id,
			(int) $row->course_id,
			(string) $row->title,
			(int) $row->pass_percent,
			(int) $row->sort_order,
		);
	}

	private function mapRowToQuestion( object $row ): QuizQuestion {
		$decoded = json_decode( (string) ( $row->options_json ?? '[]' ), true );
		$options = is_array( $decoded ) ? array_values( array_map( 'strval', $decoded ) ) : array();

		return new QuizQuestion(
			(int) $row->id,
			(int) $row->quiz_id,
			(string) $row->type,
			(string) $row->prompt,
			$options,
			(string) $row->correct_answer,
			(int) $row->sort_order,
		);
	}

	private function mapRowToAttempt( object $row ): QuizAttempt {
		$decoded = json_decode( (string) ( $row->answers_json ?? '{}' ), true );
		$answers = array();

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $questionId => $answer ) {
				$answers[ (int) $questionId ] = (string) $answer;
			}
		}

		return new QuizAttempt(
			(int) $row->id,
			(int) $row->user_id,
			(int) $row->quiz_id,
			(float) $row->score_percent,
			(bool) (int) $row->passed,
			$answers,
			new \DateTimeImmutable( (string) $row->completed_at ),
		);
	}
}
