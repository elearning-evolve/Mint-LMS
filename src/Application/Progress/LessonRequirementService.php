<?php
declare(strict_types=1);

namespace MintLMS\Application\Progress;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\LessonVideoProgressInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Quiz\QuizService;

/**
 * Shared lesson completion gates (video watch attestation, quiz pass).
 */
final class LessonRequirementService {

	public function __construct(
		private LessonVideoProgressInterface $videoProgress,
		private ?QuizService $quizService = null,
	) {
	}

	public function isVideoProgressionRequired( int $lessonId ): bool {
		return $this->videoProgress->isRequired( $lessonId );
	}

	public function hasWatchedVideo( int $userId, int $lessonId ): bool {
		return $this->videoProgress->hasWatched( $userId, $lessonId );
	}

	public function markVideoWatched( int $userId, int $lessonId ): void {
		$this->videoProgress->markWatched( $userId, $lessonId );
	}

	/**
	 * @throws ForbiddenException When a required gate is not satisfied.
	 */
	public function assertCanComplete( int $userId, int $lessonId ): void {
		if ( $this->isVideoProgressionRequired( $lessonId ) && ! $this->hasWatchedVideo( $userId, $lessonId ) ) {
			throw new ForbiddenException( 'Watch the lesson video first.' );
		}

		if ( null !== $this->quizService && ! $this->quizService->hasPassedLessonQuiz( $userId, $lessonId ) ) {
			throw new ForbiddenException( 'You must pass the lesson quiz before marking it complete.' );
		}
	}

	public function requirementsMet( int $userId, int $lessonId ): bool {
		try {
			$this->assertCanComplete( $userId, $lessonId );

			return true;
		} catch ( ForbiddenException ) {
			return false;
		}
	}
}
