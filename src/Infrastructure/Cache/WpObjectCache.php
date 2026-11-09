<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Cache;

use MintLMS\Application\Contract\CacheInterface;

/**
 * WordPress object-cache adapter (group: mintlms).
 * Dashboard aggregates use a generation key so invalidation is O(1).
 */
final class WpObjectCache implements CacheInterface {

	public const GROUP = 'mintlms';

	public const DASHBOARD_TTL = 300;

	private const DASHBOARD_GENERATION_KEY = 'dashboard_generation';

	public function get( string $key ): mixed {
		$value = wp_cache_get( $key, self::GROUP );

		return false === $value ? null : $value;
	}

	public function set( string $key, mixed $value, ?int $ttl = null ): bool {
		return wp_cache_set( $key, $value, self::GROUP, $ttl ?? 0 );
	}

	public function delete( string $key ): bool {
		return wp_cache_delete( $key, self::GROUP );
	}

	/**
	 * Bust all dashboard stat keys by advancing the generation prefix.
	 */
	public static function invalidateDashboard(): void {
		$current = wp_cache_get( self::DASHBOARD_GENERATION_KEY, self::GROUP );
		$next    = ( false === $current ? 1 : (int) $current ) + 1;
		wp_cache_set( self::DASHBOARD_GENERATION_KEY, $next, self::GROUP, 0 );
	}

	public static function dashboardCacheKey( string $suffix ): string {
		$generation = wp_cache_get( self::DASHBOARD_GENERATION_KEY, self::GROUP );
		$generation = false === $generation ? 1 : max( 1, (int) $generation );

		return 'dashboard_' . $generation . '_' . $suffix;
	}
}
