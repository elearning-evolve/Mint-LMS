<?php
declare(strict_types=1);

namespace MintLMS\Api\V1\Registry;

final class ProgressRuleRegistry {

	/** @var array<string, ProgressRuleInterface> */
	private static array $rules = array();

	public static function register( ProgressRuleInterface $rule ): void {
		self::$rules[ $rule->slug() ] = $rule;
	}

	public static function get( string $slug ): ?ProgressRuleInterface {
		return self::$rules[ $slug ] ?? null;
	}

	/**
	 * @return array<string, ProgressRuleInterface>
	 */
	public static function all(): array {
		return self::$rules;
	}

	public static function has( string $slug ): bool {
		return isset( self::$rules[ $slug ] );
	}
}
