<?php
declare(strict_types=1);

namespace MintLMS\Api\V1\Registry;

final class QuestionTypeRegistry {

	/** @var array<string, QuestionTypeInterface> */
	private static array $types = array();

	public static function register( QuestionTypeInterface $type ): void {
		self::$types[ $type->slug() ] = $type;
	}

	public static function get( string $slug ): ?QuestionTypeInterface {
		return self::$types[ $slug ] ?? null;
	}

	/**
	 * @return array<string, QuestionTypeInterface>
	 */
	public static function all(): array {
		return self::$types;
	}

	public static function has( string $slug ): bool {
		return isset( self::$types[ $slug ] );
	}
}
