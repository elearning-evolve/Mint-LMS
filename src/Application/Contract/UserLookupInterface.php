<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface UserLookupInterface {

	/**
	 * @return list<array{id: int, email: string, display_name: string}>
	 */
	public function searchUsers( string $query, int $limit = 10 ): array;

	public function getDisplayName( int $userId ): ?string;
}
