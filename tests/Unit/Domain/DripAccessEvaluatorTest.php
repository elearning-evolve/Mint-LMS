<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Domain;

use MintLMS\Domain\Drip\DripAccessEvaluator;
use PHPUnit\Framework\TestCase;

final class DripAccessEvaluatorTest extends TestCase
{
    public function test_not_locked_without_enrollment(): void
    {
        $now = new \DateTimeImmutable('2026-01-05 00:00:00');
        $enrolledAt = new \DateTimeImmutable('2026-01-01 00:00:00');

        $this->assertFalse(DripAccessEvaluator::isLocked(3, $enrolledAt, $now, false));
    }

    public function test_locked_before_available_date(): void
    {
        $now = new \DateTimeImmutable('2026-01-02 00:00:00');
        $enrolledAt = new \DateTimeImmutable('2026-01-01 00:00:00');

        $this->assertTrue(DripAccessEvaluator::isLocked(3, $enrolledAt, $now, true));
        $this->assertSame(2, DripAccessEvaluator::daysUntilAvailable(3, $enrolledAt, $now, true));
    }

    public function test_unlocked_after_available_date(): void
    {
        $now = new \DateTimeImmutable('2026-01-10 00:00:00');
        $enrolledAt = new \DateTimeImmutable('2026-01-01 00:00:00');

        $this->assertFalse(DripAccessEvaluator::isLocked(3, $enrolledAt, $now, true));
    }
}
