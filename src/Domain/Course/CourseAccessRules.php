<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

defined( 'ABSPATH' ) || exit;

/**
 * Pure helpers for course access / enrollment gates.
 */
final class CourseAccessRules {

	public static function resolveExpiresAt( CourseSettings $settings, \DateTimeImmutable $enrolledAt ): ?\DateTimeImmutable {
		if ( ! $settings->expireAccess || $settings->expireAccessDays < 1 ) {
			return null;
		}

		return $enrolledAt->modify( '+' . $settings->expireAccessDays . ' days' );
	}

	public static function isEnrollmentExpired( ?\DateTimeImmutable $expiresAt, \DateTimeImmutable $now ): bool {
		return null !== $expiresAt && $expiresAt < $now;
	}

	public static function isBeforeAccessStart( CourseSettings $settings, \DateTimeImmutable $now ): bool {
		if ( null === $settings->accessStartAt || '' === $settings->accessStartAt ) {
			return false;
		}

		$start = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $settings->accessStartAt . ' 00:00:00' );

		return false !== $start && $now < $start;
	}

	public static function isAfterAccessEnd( CourseSettings $settings, \DateTimeImmutable $now ): bool {
		if ( null === $settings->accessEndAt || '' === $settings->accessEndAt ) {
			return false;
		}

		$end = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $settings->accessEndAt . ' 23:59:59' );

		return false !== $end && $now > $end;
	}

	public static function isSeatLimitReached( CourseSettings $settings, int $activeEnrollmentCount ): bool {
		return $settings->seatLimit > 0 && $activeEnrollmentCount >= $settings->seatLimit;
	}

	/**
	 * @param callable(int): bool $isCourseComplete
	 */
	public static function prerequisitesMet( CourseSettings $settings, callable $isCourseComplete ): bool {
		if ( ! $settings->prerequisitesEnabled || array() === $settings->prerequisiteCourseIds ) {
			return true;
		}

		$results = array();
		foreach ( $settings->prerequisiteCourseIds as $courseId ) {
			$results[] = (bool) $isCourseComplete( (int) $courseId );
		}

		if ( CourseSettings::PREREQ_ALL === $settings->prerequisiteCompare ) {
			return ! in_array( false, $results, true );
		}

		return in_array( true, $results, true );
	}

	/**
	 * @param list<int> $orderedLessonIds
	 * @param list<int> $completedLessonIds
	 */
	public static function isLessonBlockedByProgression(
		CourseSettings $settings,
		int $lessonId,
		array $orderedLessonIds,
		array $completedLessonIds,
	): bool {
		if ( ! $settings->isLinear() ) {
			return false;
		}

		$index = array_search( $lessonId, $orderedLessonIds, true );
		if ( false === $index || 0 === $index ) {
			return false;
		}

		$completed = array_fill_keys( $completedLessonIds, true );
		for ( $i = 0; $i < $index; $i++ ) {
			$prevId = $orderedLessonIds[ $i ];
			if ( ! isset( $completed[ $prevId ] ) ) {
				return true;
			}
		}

		return false;
	}
}
