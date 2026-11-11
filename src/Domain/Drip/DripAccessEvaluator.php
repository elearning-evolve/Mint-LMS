<?php
declare(strict_types=1);

namespace MintLMS\Domain\Drip;

final class DripAccessEvaluator {

	public static function isLocked(
		?int $availableAfterDays,
		?\DateTimeImmutable $enrolledAt,
		\DateTimeImmutable $now,
		bool $isEnrolled,
	): bool {
		if ( ! $isEnrolled ) {
			return false;
		}

		if ( null === $availableAfterDays || $availableAfterDays <= 0 || null === $enrolledAt ) {
			return false;
		}

		$availableAt = $enrolledAt->modify( '+' . $availableAfterDays . ' days' );

		return $now < $availableAt;
	}

	public static function daysUntilAvailable(
		?int $availableAfterDays,
		?\DateTimeImmutable $enrolledAt,
		\DateTimeImmutable $now,
		bool $isEnrolled,
	): ?int {
		if ( ! self::isLocked( $availableAfterDays, $enrolledAt, $now, $isEnrolled ) || null === $enrolledAt || null === $availableAfterDays ) {
			return null;
		}

		$availableAt        = $enrolledAt->modify( '+' . $availableAfterDays . ' days' );
		$secondsRemaining   = $availableAt->getTimestamp() - $now->getTimestamp();

		return max( 1, (int) ceil( $secondsRemaining / 86400 ) );
	}
}
