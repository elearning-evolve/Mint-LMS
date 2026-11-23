<?php
declare(strict_types=1);

namespace MintLMS\Domain\Shared;

defined( 'ABSPATH' ) || exit;

interface Clock {

	public function now(): \DateTimeImmutable;
}
