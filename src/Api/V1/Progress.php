<?php
declare(strict_types=1);

namespace MintLMS\Api\V1;

use MintLMS\Application\Progress\Dto\ProgressDto;

/**
 * Frozen public API for learner progress.
 */
final class Progress {

	/** @var callable(int, int): ProgressDto|null */
	private static $progressReader = null;

	/** @var callable(int, int): void|null */
	private static $lessonCompleter = null;

	/**
	 * @param callable(int, int): ProgressDto $progressReader
	 * @param callable(int, int): void $lessonCompleter
	 */
	public static function bind( callable $progressReader, callable $lessonCompleter ): void {
		self::$progressReader  = $progressReader;
		self::$lessonCompleter = $lessonCompleter;
	}

	public static function forUser( int $userId, int $courseId ): ProgressDto {
		self::requireBinding();

		return ( self::$progressReader )( $userId, $courseId );
	}

	public static function completeLesson( int $userId, int $lessonId ): void {
		self::requireBinding();

		( self::$lessonCompleter )( $userId, $lessonId );
	}

	private static function requireBinding(): void {
		if ( null === self::$progressReader || null === self::$lessonCompleter ) {
			throw new \RuntimeException(
				'Mint LMS progress service is not registered. Call Progress::bind() during plugin bootstrap.'
			);
		}
	}
}
