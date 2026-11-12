<?php
declare(strict_types=1);

namespace MintLMS\Api\V1;

use MintLMS\Application\Enrollment\Dto\EnrollmentDto;

/**
 * Frozen public API for enrollment operations.
 */
final class Enrollment {

	/** @var callable(int, int): EnrollmentDto|null */
	private static $enroller = null;

	/** @var callable(int, int): bool|null */
	private static $checker = null;

	/**
	 * @param callable(int, int): EnrollmentDto $enroller
	 * @param callable(int, int): bool $checker
	 */
	public static function bind( callable $enroller, callable $checker ): void {
		self::$enroller = $enroller;
		self::$checker  = $checker;
	}

	public static function enroll( int $userId, int $courseId ): EnrollmentDto {
		self::requireBinding();

		return ( self::$enroller )( $userId, $courseId );
	}

	public static function isEnrolled( int $userId, int $courseId ): bool {
		self::requireBinding();

		return ( self::$checker )( $userId, $courseId );
	}

	private static function requireBinding(): void {
		if ( null === self::$enroller || null === self::$checker ) {
			throw new \RuntimeException(
				'Mint LMS enrollment service is not registered. Call Enrollment::bind() during plugin bootstrap.'
			);
		}
	}
}
