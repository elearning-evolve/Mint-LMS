<?php
declare(strict_types=1);

namespace MintLMS\Api\V1;

use MintLMS\Application\Course\Dto\CourseDto;
use MintLMS\Application\Course\Dto\CourseStructureDto;

/**
 * Frozen public API for course queries.
 */
final class Courses {

	/** @var callable(int): ?CourseDto|null */
	private static $getter = null;

	/** @var callable(int): CourseStructureDto|null */
	private static $structureLoader = null;

	/**
	 * @param callable(int): ?CourseDto $getter
	 * @param callable(int): CourseStructureDto $structureLoader
	 */
	public static function bind( callable $getter, callable $structureLoader ): void {
		self::$getter          = $getter;
		self::$structureLoader = $structureLoader;
	}

	public static function get( int $id ): ?CourseDto {
		self::requireBinding();

		return ( self::$getter )( $id );
	}

	public static function structure( int $id ): CourseStructureDto {
		self::requireBinding();

		return ( self::$structureLoader )( $id );
	}

	private static function requireBinding(): void {
		if ( null === self::$getter || null === self::$structureLoader ) {
			throw new \RuntimeException(
				'Mint LMS course service is not registered. Call Courses::bind() during plugin bootstrap.'
			);
		}
	}
}
