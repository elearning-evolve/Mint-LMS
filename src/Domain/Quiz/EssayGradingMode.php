<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

/**
 * LearnDash-compatible essay grading modes (stored as display strings in question settings).
 */
final class EssayGradingMode {

	public const NOT_GRADED_NO_POINTS   = 'Not Graded, No Points Awarded';
	public const NOT_GRADED_FULL_POINTS = 'Not Graded, Full Points Awarded';
	public const GRADED_FULL_POINTS     = 'Graded, Full Points Awarded';

	/**
	 * @return list<string>
	 */
	public static function all(): array {
		return array(
			self::NOT_GRADED_NO_POINTS,
			self::NOT_GRADED_FULL_POINTS,
			self::GRADED_FULL_POINTS,
		);
	}

	public static function normalize( string $mode ): string {
		return in_array( $mode, self::all(), true ) ? $mode : self::NOT_GRADED_NO_POINTS;
	}

	public static function awardsPointsOnSubmit( string $mode ): bool {
		$mode = self::normalize( $mode );

		return self::NOT_GRADED_FULL_POINTS === $mode || self::GRADED_FULL_POINTS === $mode;
	}

	public static function requiresManualGrade( string $mode ): bool {
		$mode = self::normalize( $mode );

		return self::GRADED_FULL_POINTS !== $mode;
	}
}
