<?php
declare(strict_types=1);

namespace MintLMS\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Base for WordPress-backed integration tests.
 *
 * These tests are intentionally NOT part of GitHub CI (`composer arch` /
 * `composer test:unit` / suite `ci`). Run locally or in a dedicated job only
 * after loading the WordPress PHPUnit scaffold:
 *
 * @link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 *
 * @group integration
 */
abstract class IntegrationTestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! $this->isWordPressTestEnvironment() ) {
			$this->markTestSkipped(
				'Integration suite is not in GitHub CI. Requires a WordPress PHPUnit '
				. 'scaffold (ABSPATH + $wpdb). See tests/README.md — composer test:integ.'
			);
		}
	}

	/**
	 * True when a real WP test bootstrap is loaded (not the unit stub bootstrap).
	 */
	protected function isWordPressTestEnvironment(): bool {
		if ( ! defined( 'ABSPATH' ) ) {
			return false;
		}

		// Unit bootstrap defines ABSPATH as a stub; real WP has wp_insert_post + $wpdb.
		if ( ! function_exists( 'wp_insert_post' ) ) {
			return false;
		}

		global $wpdb;

		return $wpdb instanceof \wpdb;
	}
}
