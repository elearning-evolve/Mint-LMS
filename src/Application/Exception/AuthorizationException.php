<?php
declare(strict_types=1);

namespace MintLMS\Application\Exception;

final class AuthorizationException extends \RuntimeException {

	public function __construct(
		int $userId,
		string $capability,
	) {
		parent::__construct(
			sprintf(
				'User %d lacks the required capability: %s.',
				$userId,
				$capability
			)
		);
	}
}
