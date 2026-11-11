<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface CacheInterface {

	public function get( string $key ): mixed;

	public function set( string $key, mixed $value, ?int $ttl = null ): bool;

	public function delete( string $key ): bool;
}
