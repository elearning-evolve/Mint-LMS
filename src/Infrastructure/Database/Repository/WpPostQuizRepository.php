<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Quiz\Quiz;
use MintLMS\Domain\Quiz\QuizAttempt;
use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Domain\Quiz\QuizRepositoryInterface;
use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * CPT-backed quiz repository (mint-quiz + mint-question + question_data + junction).
 */
final class WpPostQuizRepository implements QuizRepositoryInterface {

	private const PROMPT_TITLE_LIMIT = 200;

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function findById( int $id ): ?Quiz {
		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post || PostTypes::QUIZ !== $post->post_type ) {
			return null;
		}

		return $this->mapPostToQuiz( $post );
	}

	public function findByLessonId( int $lessonId, bool $publishedOnly = false ): ?Quiz {
		$quizzes = $this->findAllByLessonId( $lessonId, $publishedOnly );

		return $quizzes[0] ?? null;
	}

	public function findAllByLessonId( int $lessonId, bool $publishedOnly = false ): array {
		$query = new \WP_Query(
			array(
				'post_type'              => PostTypes::QUIZ,
				'post_status'            => $publishedOnly
					? 'publish'
					: array( 'publish', 'draft', 'private' ),
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'orderby'                => 'meta_value_num',
				'meta_key'               => PostTypes::META_SORT_ORDER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Ordered lesson quizzes.
				'order'                  => 'ASC',
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Lesson-scoped quiz lookup.
					array(
						'key'     => PostTypes::META_LESSON_ID,
						'value'   => $lessonId,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$quizzes = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$quizzes[] = $this->mapPostToQuiz( $post );
			}
		}

		return $quizzes;
	}

	public function nextQuizSortOrderForLesson( int $lessonId ): int {
		$max = -1;

		foreach ( $this->findAllByLessonId( $lessonId ) as $quiz ) {
			if ( $quiz->sortOrder > $max ) {
				$max = $quiz->sortOrder;
			}
		}

		return $max + 1;
	}

	public function findQuestionsByQuizId( int $quizId, bool $publishedOnly = false ): array {
		$junction = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$data     = Schema::validateTable( Schema::questionDataTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$posts    = $this->wpdb->posts;
		$statusSql = $publishedOnly ? "p.post_status = 'publish'" : "p.post_status NOT IN ('trash','auto-draft')";

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT p.ID AS id, j.quiz_id, qd.question_type AS type, p.post_title, p.post_content,
					qd.options_json, qd.correct_answer, j.sort_order
				FROM {$junction} j
				INNER JOIN {$posts} p ON p.ID = j.question_id AND p.post_type = %s AND {$statusSql}
				INNER JOIN {$data} qd ON qd.post_id = j.question_id
				WHERE j.quiz_id = %d
				ORDER BY j.sort_order ASC, j.question_id ASC",
				PostTypes::QUESTION,
				$quizId
			)
		);
		// phpcs:enable

		$questions = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$questions[] = $this->mapRowToQuestion( $row );
			}
		}

		return $questions;
	}

	public function findQuestionById( int $questionId ): ?QuizQuestion {
		$junction = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$data     = Schema::validateTable( Schema::questionDataTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$posts    = $this->wpdb->posts;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT p.ID AS id, j.quiz_id, qd.question_type AS type, p.post_title, p.post_content,
					qd.options_json, qd.correct_answer, j.sort_order
				FROM {$data} qd
				INNER JOIN {$posts} p ON p.ID = qd.post_id AND p.post_type = %s AND p.post_status != 'trash'
				LEFT JOIN {$junction} j ON j.question_id = qd.post_id
				WHERE qd.post_id = %d
				ORDER BY j.sort_order ASC, j.id ASC
				LIMIT 1",
				PostTypes::QUESTION,
				$questionId
			)
		);
		// phpcs:enable

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToQuestion( $row );
	}

	public function saveQuiz( Quiz $quiz ): Quiz {
		$postarr = array(
			'post_type'  => PostTypes::QUIZ,
			'post_title' => $quiz->title,
		);

		if ( 0 === $quiz->id ) {
			$postarr['post_status'] = 'draft';
			$postId                 = wp_insert_post( $postarr, true );

			if ( is_wp_error( $postId ) ) {
				throw new \RuntimeException( 'Failed to insert quiz: ' . $postId->get_error_message() );
			}

			$this->updateQuizMeta( (int) $postId, $quiz );

			return new Quiz(
				(int) $postId,
				$quiz->lessonId,
				$quiz->courseId,
				$quiz->title,
				$quiz->passPercent,
				$quiz->sortOrder,
			);
		}

		$existing = get_post( $quiz->id );

		if ( ! $existing instanceof \WP_Post || PostTypes::QUIZ !== $existing->post_type ) {
			throw new \RuntimeException( 'Quiz post not found.' );
		}

		$postarr['ID'] = $quiz->id;
		$result          = wp_update_post( $postarr, true );

		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( 'Failed to update quiz: ' . $result->get_error_message() );
		}

		$this->updateQuizMeta( $quiz->id, $quiz );

		return $quiz;
	}

	public function saveQuestion( QuizQuestion $question ): QuizQuestion {
		$titleContent = $this->splitPromptForPost( $question->prompt );
		$optionsJson  = wp_json_encode( $question->options );
		$optionsJson  = is_string( $optionsJson ) ? $optionsJson : '[]';
		$now          = current_time( 'mysql' );
		$dataTable    = Schema::validateTable( Schema::questionDataTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$junction     = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		if ( 0 === $question->id ) {
			$postId = wp_insert_post(
				array(
					'post_type'    => PostTypes::QUESTION,
					'post_status'  => 'draft',
					'post_title'   => $titleContent['title'],
					'post_content' => $titleContent['content'],
				),
				true
			);

			if ( is_wp_error( $postId ) ) {
				throw new \RuntimeException( 'Failed to insert question: ' . $postId->get_error_message() );
			}

			$postId = (int) $postId;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $this->wpdb->insert(
				$dataTable,
				array(
					'post_id'        => $postId,
					'question_type'  => $question->type,
					'options_json'   => $optionsJson,
					'correct_answer' => $question->correctAnswer,
					'points'         => 1,
					'created_at'     => $now,
					'updated_at'     => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
			);

			if ( false === $inserted ) {
				wp_delete_post( $postId, true );
				throw new \RuntimeException( 'Failed to insert question data.' );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$linked = $this->wpdb->insert(
				$junction,
				array(
					'quiz_id'     => $question->quizId,
					'question_id' => $postId,
					'sort_order'  => $question->sortOrder,
				),
				array( '%d', '%d', '%d' )
			);

			if ( false === $linked ) {
				$this->wpdb->delete( $dataTable, array( 'post_id' => $postId ), array( '%d' ) );
				wp_delete_post( $postId, true );
				throw new \RuntimeException( 'Failed to link question to quiz.' );
			}

			return new QuizQuestion(
				$postId,
				$question->quizId,
				$question->type,
				$question->prompt,
				$question->options,
				$question->correctAnswer,
				$question->sortOrder,
			);
		}

		$existing = get_post( $question->id );

		if ( ! $existing instanceof \WP_Post || PostTypes::QUESTION !== $existing->post_type ) {
			throw new \RuntimeException( 'Question post not found.' );
		}

		$result = wp_update_post(
			array(
				'ID'           => $question->id,
				'post_title'   => $titleContent['title'],
				'post_content' => $titleContent['content'],
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( 'Failed to update question: ' . $result->get_error_message() );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $this->wpdb->update(
			$dataTable,
			array(
				'question_type'  => $question->type,
				'options_json'   => $optionsJson,
				'correct_answer' => $question->correctAnswer,
				'updated_at'     => $now,
			),
			array( 'post_id' => $question->id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update question data.' );
		}

		// No row yet (orphan / race): insert question_data.
		if ( 0 === $updated ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT post_id FROM {$dataTable} WHERE post_id = %d",
					$question->id
				)
			);
			// phpcs:enable

			if ( null === $exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$this->wpdb->insert(
					$dataTable,
					array(
						'post_id'        => $question->id,
						'question_type'  => $question->type,
						'options_json'   => $optionsJson,
						'correct_answer' => $question->correctAnswer,
						'points'         => 1,
						'created_at'     => $now,
						'updated_at'     => $now,
					),
					array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
				);
			}
		}

		$this->upsertJunction( $question->quizId, $question->id, $question->sortOrder );

		return $question;
	}

	public function deleteQuiz( int $id ): bool {
		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post || PostTypes::QUIZ !== $post->post_type ) {
			return false;
		}

		$junction = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$questionIds = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT question_id FROM {$junction} WHERE quiz_id = %d",
				$id
			)
		);
		// phpcs:enable

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->delete( $junction, array( 'quiz_id' => $id ), array( '%d' ) );

		if ( is_array( $questionIds ) ) {
			foreach ( $questionIds as $questionId ) {
				$this->deleteOrphanQuestion( (int) $questionId );
			}
		}

		$attemptsTable = Schema::validateTable( Schema::quizAttemptsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->delete( $attemptsTable, array( 'quiz_id' => $id ), array( '%d' ) );

		$deleted = wp_delete_post( $id, true );

		return $deleted instanceof \WP_Post;
	}

	public function deleteAttemptsByQuizId( int $quizId ): void {
		if ( $quizId <= 0 ) {
			return;
		}
		$attemptsTable = Schema::validateTable( Schema::quizAttemptsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->delete( $attemptsTable, array( 'quiz_id' => $quizId ), array( '%d' ) );
	}

	public function deleteQuestion( int $id ): bool {
		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post || PostTypes::QUESTION !== $post->post_type ) {
			return false;
		}

		$junction = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$data     = Schema::validateTable( Schema::questionDataTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->delete( $junction, array( 'question_id' => $id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->delete( $data, array( 'post_id' => $id ), array( '%d' ) );

		$deleted = wp_delete_post( $id, true );

		return $deleted instanceof \WP_Post;
	}

	public function deleteQuestionsByQuizId( int $quizId ): void {
		$junction = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$questionIds = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT question_id FROM {$junction} WHERE quiz_id = %d",
				$quizId
			)
		);
		// phpcs:enable

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->delete( $junction, array( 'quiz_id' => $quizId ), array( '%d' ) );

		if ( is_array( $questionIds ) ) {
			foreach ( $questionIds as $questionId ) {
				$this->deleteOrphanQuestion( (int) $questionId );
			}
		}
	}

	public function deleteByLessonId( int $lessonId ): void {
		foreach ( $this->findAllByLessonId( $lessonId ) as $quiz ) {
			$this->deleteQuiz( $quiz->id );
		}
	}

	public function nextQuestionSortOrder( int $quizId ): int {
		$junction = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$max = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT MAX(sort_order) FROM {$junction} WHERE quiz_id = %d",
				$quizId
			)
		);
		// phpcs:enable

		if ( null === $max || '' === $max ) {
			return 0;
		}

		return (int) $max + 1;
	}

	public function saveAttempt( QuizAttempt $attempt ): QuizAttempt {
		$table       = Schema::validateTable( Schema::quizAttemptsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$answersJson = wp_json_encode( $attempt->answers );
		$answersJson = is_string( $answersJson ) ? $answersJson : '{}';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $this->wpdb->insert(
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
			(int) $this->wpdb->insert_id,
			$attempt->userId,
			$attempt->quizId,
			$attempt->scorePercent,
			$attempt->passed,
			$attempt->answers,
			$attempt->completedAt,
		);
	}

	public function findAttemptsByQuizId( int $quizId ): array {
		$table = Schema::validateTable( Schema::quizAttemptsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT id, user_id, quiz_id, score_percent, passed, answers_json, completed_at
				FROM {$table}
				WHERE quiz_id = %d
				ORDER BY completed_at DESC",
				$quizId
			)
		);
		// phpcs:enable

		$attempts = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$attempts[] = $this->mapRowToAttempt( $row );
			}
		}

		return $attempts;
	}

	public function findBestPassedAttempt( int $userId, int $quizId ): ?QuizAttempt {
		$table = Schema::validateTable( Schema::quizAttemptsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id, user_id, quiz_id, score_percent, passed, answers_json, completed_at
				FROM {$table}
				WHERE user_id = %d AND quiz_id = %d AND passed = 1
				ORDER BY score_percent DESC, completed_at DESC
				LIMIT 1",
				$userId,
				$quizId
			)
		);
		// phpcs:enable

		if ( null === $row ) {
			return null;
		}

		return $this->mapRowToAttempt( $row );
	}

	public function hasPassed( int $userId, int $quizId ): bool {
		return null !== $this->findBestPassedAttempt( $userId, $quizId );
	}

	private function updateQuizMeta( int $postId, Quiz $quiz ): void {
		update_post_meta( $postId, PostTypes::META_LESSON_ID, $quiz->lessonId );
		update_post_meta( $postId, PostTypes::META_COURSE_ID, $quiz->courseId );
		update_post_meta( $postId, PostTypes::META_PASS_PERCENT, $quiz->passPercent );
		update_post_meta( $postId, PostTypes::META_SORT_ORDER, $quiz->sortOrder );
	}

	/**
	 * @return array{title: string, content: string}
	 */
	private function splitPromptForPost( string $prompt ): array {
		if ( strlen( $prompt ) > self::PROMPT_TITLE_LIMIT ) {
			$title = wp_trim_words( $prompt, 20, '…' );

			if ( '' === $title ) {
				$title = substr( $prompt, 0, self::PROMPT_TITLE_LIMIT );
			}

			return array(
				'title'   => $title,
				'content' => $prompt,
			);
		}

		return array(
			'title'   => $prompt,
			'content' => '',
		);
	}

	private function resolvePrompt( string $postTitle, string $postContent ): string {
		$content = trim( $postContent );

		if ( '' !== $content ) {
			return $postContent;
		}

		return $postTitle;
	}

	private function upsertJunction( int $quizId, int $questionId, int $sortOrder ): void {
		$junction = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// Prefer reassigning by question_id so a question stays on exactly one quiz.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existingId = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT id FROM {$junction} WHERE question_id = %d ORDER BY id ASC LIMIT 1",
				$questionId
			)
		);
		// phpcs:enable

		if ( null !== $existingId && '' !== $existingId ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->update(
				$junction,
				array(
					'quiz_id'    => $quizId,
					'sort_order' => $sortOrder,
				),
				array( 'id' => (int) $existingId ),
				array( '%d', '%d' ),
				array( '%d' )
			);

			// Drop any duplicate links for this question.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query(
				$this->wpdb->prepare(
					"DELETE FROM {$junction} WHERE question_id = %d AND id <> %d",
					$questionId,
					(int) $existingId
				)
			);
			// phpcs:enable

			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$this->wpdb->insert(
			$junction,
			array(
				'quiz_id'     => $quizId,
				'question_id' => $questionId,
				'sort_order'  => $sortOrder,
			),
			array( '%d', '%d', '%d' )
		);
	}

	/**
	 * Delete a question post if it is no longer linked to any quiz.
	 */
	private function deleteOrphanQuestion( int $questionId ): void {
		if ( $questionId <= 0 ) {
			return;
		}

		$junction = Schema::validateTable( Schema::quizQuestionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remaining = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$junction} WHERE question_id = %d",
				$questionId
			)
		);
		// phpcs:enable

		if ( $remaining > 0 ) {
			return;
		}

		$data = Schema::validateTable( Schema::questionDataTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->delete( $data, array( 'post_id' => $questionId ), array( '%d' ) );
		wp_delete_post( $questionId, true );
	}

	private function mapPostToQuiz( \WP_Post $post ): Quiz {
		return new Quiz(
			(int) $post->ID,
			(int) get_post_meta( $post->ID, PostTypes::META_LESSON_ID, true ),
			(int) get_post_meta( $post->ID, PostTypes::META_COURSE_ID, true ),
			(string) $post->post_title,
			(int) get_post_meta( $post->ID, PostTypes::META_PASS_PERCENT, true ),
			(int) get_post_meta( $post->ID, PostTypes::META_SORT_ORDER, true ),
		);
	}

	private function mapRowToQuestion( object $row ): QuizQuestion {
		$decoded = json_decode( (string) ( $row->options_json ?? '[]' ), true );
		$options = is_array( $decoded ) ? array_values( array_map( 'strval', $decoded ) ) : array();
		$prompt  = $this->resolvePrompt(
			(string) ( $row->post_title ?? '' ),
			(string) ( $row->post_content ?? '' )
		);

		return new QuizQuestion(
			(int) $row->id,
			(int) ( $row->quiz_id ?? 0 ),
			(string) $row->type,
			$prompt,
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
