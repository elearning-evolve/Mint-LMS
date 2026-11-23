<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Quiz\EssaySubmission;
use MintLMS\Domain\Quiz\EssaySubmissionRepositoryInterface;
use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\PostType\PostTypes;

final class WpdbEssaySubmissionRepository implements EssaySubmissionRepositoryInterface {

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function save( EssaySubmission $submission ): EssaySubmission {
		$table = Schema::validateTable( Schema::essaySubmissionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		if ( $submission->id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->update(
				$table,
				array(
					'status'          => $submission->status,
					'points_possible' => $submission->pointsPossible,
					'points_awarded'  => $submission->pointsAwarded,
					'answer_text'     => $submission->answerText,
					'attachment_id'   => $submission->attachmentId,
					'graded_by'       => $submission->gradedBy,
					'graded_at'       => null !== $submission->gradedAt ? $submission->gradedAt->format( 'Y-m-d H:i:s' ) : null,
				),
				array( 'id' => $submission->id ),
				array( '%s', '%d', '%f', '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);

			$saved = $this->findById( $submission->id );
			if ( null === $saved ) {
				throw new \RuntimeException( 'Failed to reload essay submission.' );
			}

			return $saved;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $this->wpdb->insert(
			$table,
			array(
				'attempt_id'      => $submission->attemptId,
				'question_id'     => $submission->questionId,
				'user_id'         => $submission->userId,
				'quiz_id'         => $submission->quizId,
				'status'          => $submission->status,
				'points_possible' => $submission->pointsPossible,
				'points_awarded'  => $submission->pointsAwarded,
				'answer_text'     => $submission->answerText,
				'attachment_id'   => $submission->attachmentId,
				'graded_by'       => $submission->gradedBy,
				'graded_at'       => null !== $submission->gradedAt ? $submission->gradedAt->format( 'Y-m-d H:i:s' ) : null,
				'created_at'      => $submission->createdAt->format( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%d', '%d', '%s', '%d', '%f', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to insert essay submission.' );
		}

		return new EssaySubmission(
			(int) $this->wpdb->insert_id,
			$submission->attemptId,
			$submission->questionId,
			$submission->userId,
			$submission->quizId,
			$submission->status,
			$submission->pointsPossible,
			$submission->pointsAwarded,
			$submission->answerText,
			$submission->attachmentId,
			$submission->gradedBy,
			$submission->gradedAt,
			$submission->createdAt,
		);
	}

	public function findById( int $id ): ?EssaySubmission {
		$table = Schema::validateTable( Schema::essaySubmissionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d LIMIT 1',
				$table,
				$id
			)
		);

		return null !== $row ? $this->mapRow( $row ) : null;
	}

	public function findByAttemptAndQuestion( int $attemptId, int $questionId ): ?EssaySubmission {
		$table = Schema::validateTable( Schema::essaySubmissionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				'SELECT * FROM %i WHERE attempt_id = %d AND question_id = %d LIMIT 1',
				$table,
				$attemptId,
				$questionId
			)
		);

		return null !== $row ? $this->mapRow( $row ) : null;
	}

	public function findByAttemptId( int $attemptId ): array {
		$table = Schema::validateTable( Schema::essaySubmissionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT * FROM %i WHERE attempt_id = %d ORDER BY id ASC',
				$table,
				$attemptId
			)
		);

		$out = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$out[] = $this->mapRow( $row );
			}
		}

		return $out;
	}

	public function listForGrading( ?string $status = null, ?int $quizId = null, ?int $authorId = null, int $limit = 100 ): array {
		$table  = Schema::validateTable( Schema::essaySubmissionsTable( $this->wpdb->prefix ), $this->wpdb->prefix );
		$limit  = max( 1, min( 500, $limit ) );
		$where  = array( '1=1' );
		$params = array( $table );

		if ( null !== $status && '' !== $status ) {
			$where[]  = 'e.status = %s';
			$params[] = $status;
		}
		if ( null !== $quizId && $quizId > 0 ) {
			$where[]  = 'e.quiz_id = %d';
			$params[] = $quizId;
		}
		if ( null !== $authorId && $authorId > 0 ) {
			$where[]  = 'EXISTS (
				SELECT 1 FROM %i p
				WHERE p.ID = e.quiz_id AND p.post_type = %s AND p.post_author = %d
			)';
			$params[] = $this->wpdb->posts;
			$params[] = PostTypes::QUIZ;
			$params[] = $authorId;
		}

		$params[] = $limit;
		$sql      = 'SELECT e.* FROM %i e WHERE ' . implode( ' AND ', $where ) . ' ORDER BY e.created_at DESC LIMIT %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->wpdb->get_results( $this->wpdb->prepare( $sql, ...$params ) );

		$out = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$out[] = $this->mapRow( $row );
			}
		}

		return $out;
	}

	public function updateGrade(
		int $id,
		string $status,
		float $pointsAwarded,
		int $gradedBy,
		\DateTimeImmutable $gradedAt,
	): EssaySubmission {
		$existing = $this->findById( $id );
		if ( null === $existing ) {
			throw new \RuntimeException( 'Essay submission not found.' );
		}

		return $this->save(
			new EssaySubmission(
				$existing->id,
				$existing->attemptId,
				$existing->questionId,
				$existing->userId,
				$existing->quizId,
				$status,
				$existing->pointsPossible,
				max( 0.0, $pointsAwarded ),
				$existing->answerText,
				$existing->attachmentId,
				$gradedBy,
				$gradedAt,
				$existing->createdAt,
			)
		);
	}

	private function mapRow( object $row ): EssaySubmission {
		$gradedAt = null;
		if ( ! empty( $row->graded_at ) && '0000-00-00 00:00:00' !== (string) $row->graded_at ) {
			$gradedAt = new \DateTimeImmutable( (string) $row->graded_at );
		}

		$attachmentId = isset( $row->attachment_id ) ? (int) $row->attachment_id : 0;

		return new EssaySubmission(
			(int) $row->id,
			(int) $row->attempt_id,
			(int) $row->question_id,
			(int) $row->user_id,
			(int) $row->quiz_id,
			(string) $row->status,
			(int) $row->points_possible,
			(float) $row->points_awarded,
			(string) ( $row->answer_text ?? '' ),
			$attachmentId > 0 ? $attachmentId : null,
			isset( $row->graded_by ) && (int) $row->graded_by > 0 ? (int) $row->graded_by : null,
			$gradedAt,
			new \DateTimeImmutable( (string) $row->created_at ),
		);
	}
}
