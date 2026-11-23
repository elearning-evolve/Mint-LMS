<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

defined( 'ABSPATH' ) || exit;

interface EssaySubmissionRepositoryInterface {

	public function save( EssaySubmission $submission ): EssaySubmission;

	public function findById( int $id ): ?EssaySubmission;

	public function findByAttemptAndQuestion( int $attemptId, int $questionId ): ?EssaySubmission;

	/**
	 * @return list<EssaySubmission>
	 */
	public function findByAttemptId( int $attemptId ): array;

	/**
	 * @return list<EssaySubmission>
	 */
	public function listForGrading( ?string $status = null, ?int $quizId = null, ?int $authorId = null, int $limit = 100 ): array;

	public function updateGrade(
		int $id,
		string $status,
		float $pointsAwarded,
		int $gradedBy,
		\DateTimeImmutable $gradedAt,
	): EssaySubmission;
}
