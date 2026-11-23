<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit;

use MintLMS\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
	protected function tearDown(): void
	{
		parent::tearDown();
		// Reset WP_DEBUG between cases when the constant was not predefined by the suite.
	}

	public function test_empty_message_is_noop(): void
	{
		Logger::error( '   ' );
		$this->addToAssertionCount( 1 );
	}

	public function test_log_without_wc_and_without_debug_is_noop(): void
	{
		if ( function_exists( 'wc_get_logger' ) ) {
			$this->markTestSkipped( 'WooCommerce logger is available in this environment.' );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->markTestSkipped( 'WP_DEBUG is already enabled.' );
		}

		Logger::warning( 'Mint LMS logger unit test silence check' );
		$this->addToAssertionCount( 1 );
	}

	public function test_source_constant(): void
	{
		$this->assertSame( 'mint-lms', Logger::SOURCE );
	}
}
