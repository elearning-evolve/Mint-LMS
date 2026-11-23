<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application\Quiz;

use MintLMS\Application\Quiz\QuizTimerService;
use MintLMS\Domain\Shared\Clock;
use PHPUnit\Framework\TestCase;

final class QuizTimerServiceTest extends TestCase
{
	public function test_limit_seconds_from_settings(): void
	{
		$timer = new QuizTimerService($this->clockAt(1_700_000_000));

		$this->assertSame(
			3661,
			$timer->limitSeconds(
				array(
					'timeLimitEnabled' => true,
					'timeLimitHours' => '01',
					'timeLimitMinutes' => '01',
					'timeLimitSeconds' => '01',
				)
			)
		);

		$this->assertSame(0, $timer->limitSeconds(array('timeLimitEnabled' => false)));
	}

	private function clockAt(int $timestamp): Clock
	{
		return new class ($timestamp) implements Clock {
			public function __construct(private int $timestamp)
			{
			}

			public function now(): \DateTimeImmutable
			{
				return (new \DateTimeImmutable('@' . $this->timestamp))->setTimezone(new \DateTimeZone('UTC'));
			}
		};
	}
}
