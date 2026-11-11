<?php
declare(strict_types=1);

namespace MintLMS\Domain\Shared;

interface Clock {

	public function now(): \DateTimeImmutable;
}
