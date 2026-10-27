<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\PostType;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Quiz\QuizQuestion;
use MintLMS\Infrastructure\Database\Schema;

/**
 * Resolves question ↔ quiz ↔ course/lesson links for admin list / builder entry.
 */
final class QuestionLinkResolver {

	/**
	 * Read-only links for A10 Questions list.
	 *
	 * @return array{quizId: int, courseId: int, lessonId: int}
	 */
	public function readForList( int $questionId ): array {
		$quizId   = $this->findQuizId( $questionId );
		$courseId = 0;
		$lessonId = 0;

		if ( $quizId > 0 ) {
			$links    = ( new QuizLinkResolver() )->readForList( $quizId );
			$courseId = $links['courseId'];
			$lessonId = $links['lessonId'];
		}

		return array(
			'quizId'   => $quizId,
			'courseId' => $courseId,
			'lessonId' => $lessonId,
		);
	}

	public function findQuizId( int $questionId ): int {
		if ( $questionId <= 0 ) {
			return 0;
		}

		$wpdb = mintlms_wpdb();
		if ( null === $wpdb ) {
			return 0;
		}

		$junction = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$quizId = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT quiz_id FROM {$junction} WHERE question_id = %d ORDER BY id ASC LIMIT 1",
				$questionId
			)
		);
		// phpcs:enable

		return (int) $quizId;
	}

	/**
	 * Ensure a WP-native (or orphan) question can open in the Mint builder.
	 * Creates a disposable host quiz when the question is not linked yet.
	 * Does not overwrite the question post_title.
	 *
	 * @param callable(): array{course_id: int, lesson_id: int, quiz_id: int} $hostQuizFactory
	 */
	public function ensureForBuilder( int $questionId, callable $hostQuizFactory ): int {
		$post = get_post( $questionId );
		if ( ! $post instanceof \WP_Post || PostTypes::QUESTION !== $post->post_type ) {
			return 0;
		}

		$quizId = $this->findQuizId( $questionId );
		if ( $quizId > 0 ) {
			$this->seedDisplayTitleIfMissing( $questionId, (string) $post->post_title );
			return $quizId;
		}

		$created = $hostQuizFactory();
		$quizId  = (int) ( $created['quiz_id'] ?? 0 );
		if ( $quizId <= 0 ) {
			return 0;
		}

		update_post_meta( $quizId, PostTypes::META_QUESTION_SHELL, 1 );

		$this->ensureQuestionDataAndLink( $questionId, $quizId );
		$this->seedDisplayTitleIfMissing( $questionId, (string) $post->post_title );

		return $quizId;
	}

	/**
	 * Insert question_data + junction without rewriting post_title / post_content.
	 */
	private function ensureQuestionDataAndLink( int $questionId, int $quizId ): void {
		$wpdb = mintlms_wpdb();
		if ( null === $wpdb || $questionId <= 0 || $quizId <= 0 ) {
			return;
		}

		$dataTable = Schema::validateTable( Schema::questionDataTable( $wpdb->prefix ), $wpdb->prefix );
		$junction  = Schema::validateTable( Schema::quizQuestionsTable( $wpdb->prefix ), $wpdb->prefix );
		$now       = current_time( 'mysql' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$hasData = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$dataTable} WHERE post_id = %d",
				$questionId
			)
		);
		// phpcs:enable

		if ( null === $hasData ) {
			$optionsJson = wp_json_encode( array() );
			$optionsJson = is_string( $optionsJson ) ? $optionsJson : '[]';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$dataTable,
				array(
					'post_id'        => $questionId,
					'question_type'  => QuizQuestion::TYPE_ESSAY,
					'options_json'   => $optionsJson,
					'correct_answer' => '',
					'points'         => 1,
					'created_at'     => $now,
					'updated_at'     => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
			);
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existingJunction = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$junction} WHERE question_id = %d ORDER BY id ASC LIMIT 1",
				$questionId
			)
		);
		// phpcs:enable

		if ( null !== $existingJunction && '' !== $existingJunction ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$junction,
				array(
					'quiz_id'    => $quizId,
					'sort_order' => 0,
				),
				array( 'id' => (int) $existingJunction ),
				array( '%d', '%d' ),
				array( '%d' )
			);
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$junction,
			array(
				'quiz_id'     => $quizId,
				'question_id' => $questionId,
				'sort_order'  => 0,
			),
			array( '%d', '%d', '%d' )
		);
	}

	private function seedDisplayTitleIfMissing( int $questionId, string $postTitle ): void {
		$title = trim( $postTitle );
		if ( '' === $title ) {
			$title = __( 'New Question', 'mint-lms' );
		}

		$raw      = get_post_meta( $questionId, PostTypes::META_QUESTION_SETTINGS, true );
		$settings = is_array( $raw ) ? $raw : array();
		$existing = trim( (string) ( $settings['displayTitle'] ?? '' ) );
		$changed  = false;

		if ( '' === $existing ) {
			$settings['displayTitle'] = $title;
			$changed                  = true;
		}

		// Fresh WP-native questions have no settings yet — mark type pending so builder asks for a type.
		if ( ! is_array( $raw ) ) {
			$settings['answerTypePending'] = true;
			$changed                       = true;
		}

		if ( $changed ) {
			update_post_meta( $questionId, PostTypes::META_QUESTION_SETTINGS, $settings );
		}
	}
}
