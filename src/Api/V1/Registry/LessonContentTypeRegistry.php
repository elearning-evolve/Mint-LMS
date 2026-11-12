<?php
declare(strict_types=1);

namespace MintLMS\Api\V1\Registry;

final class LessonContentTypeRegistry {

	/** @var array<string, LessonContentTypeInterface> */
	private static array $types = array();

	public static function register( LessonContentTypeInterface $type ): void {
		self::$types[ $type->slug() ] = $type;
	}

	public static function get( string $slug ): ?LessonContentTypeInterface {
		return self::$types[ $slug ] ?? null;
	}

	/**
	 * @return array<string, LessonContentTypeInterface>
	 */
	public static function all(): array {
		return self::$types;
	}

	public static function has( string $slug ): bool {
		return isset( self::$types[ $slug ] );
	}
}
