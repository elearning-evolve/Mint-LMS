<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Domain\Shared\Clock;

/**
 * Server-side quiz time-limit deadlines (user meta), so reload cannot reset the clock.
 */
final class QuizTimerService {

	public const META_PREFIX = 'mintlms_quiz_started_';

	/** Grace seconds for client/server clock skew. */
	public const GRACE_SECONDS = 15;

	public function __construct(
		private Clock $clock,
	) {
	}

	/**
	 * @param array<string, mixed> $settings Quiz runtime settings.
	 * @return array{startedAt:int, deadlineAt:int, remainingSeconds:int, limitSeconds:int}|null
	 */
	public function start( int $userId, int $quizId, array $settings ): ?array {
		$limit = $this->limitSeconds( $settings );
		if ( $limit <= 0 || $userId <= 0 || $quizId <= 0 ) {
			return null;
		}

		$now      = $this->clock->now()->getTimestamp();
		$existing = $this->readStart( $userId, $quizId );

		if ( null === $existing ) {
			update_user_meta( $userId, self::META_PREFIX . $quizId, (string) $now );
			$started = $now;
		} else {
			$started = $existing;
		}

		$deadline  = $started + $limit;
		$remaining = max( 0, $deadline - $now );

		return array(
			'startedAt'        => $started,
			'deadlineAt'       => $deadline,
			'remainingSeconds' => $remaining,
			'limitSeconds'     => $limit,
		);
	}

	/**
	 * @param array<string, mixed> $settings Quiz runtime settings.
	 */
	public function assertWithinLimit( int $userId, int $quizId, array $settings ): void {
		$limit = $this->limitSeconds( $settings );
		if ( $limit <= 0 || $userId <= 0 ) {
			return;
		}

		$started = $this->readStart( $userId, $quizId );
		if ( null === $started ) {
			throw new ForbiddenException( 'Quiz timer was not started.' );
		}

		$elapsed = $this->clock->now()->getTimestamp() - $started;
		if ( $elapsed > ( $limit + self::GRACE_SECONDS ) ) {
			throw new ForbiddenException( 'Quiz time limit has expired.' );
		}
	}

	public function clear( int $userId, int $quizId ): void {
		if ( $userId <= 0 || $quizId <= 0 ) {
			return;
		}

		delete_user_meta( $userId, self::META_PREFIX . $quizId );
	}

	/**
	 * @param array<string, mixed> $settings Quiz runtime settings.
	 */
	public function limitSeconds( array $settings ): int {
		if ( empty( $settings['timeLimitEnabled'] ) ) {
			return 0;
		}

		$hours   = max( 0, (int) ( $settings['timeLimitHours'] ?? 0 ) );
		$minutes = max( 0, (int) ( $settings['timeLimitMinutes'] ?? 0 ) );
		$seconds = max( 0, (int) ( $settings['timeLimitSeconds'] ?? 0 ) );

		return ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;
	}

	private function readStart( int $userId, int $quizId ): ?int {
		$raw = get_user_meta( $userId, self::META_PREFIX . $quizId, true );
		if ( '' === $raw || null === $raw || false === $raw ) {
			return null;
		}

		$ts = (int) $raw;

		return $ts > 0 ? $ts : null;
	}
}
