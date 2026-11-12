<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Clock;

use MintLMS\Domain\Shared\Clock;

final class SystemClock implements Clock {

	public function now(): \DateTimeImmutable {
		return new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
	}
}
