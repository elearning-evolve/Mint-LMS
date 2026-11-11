<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

final class MigrationRunner {

	private const OPTION_KEY = 'mintlms_db_version';

	/**
	 * @param list<MigrationInterface> $migrations
	 */
	public function __construct(
		private readonly \wpdb $wpdb,
		private readonly array $migrations
	) {
	}

	public function run(): void {
		$current = (string) get_option( self::OPTION_KEY, '0' );

		$migrations = $this->migrations;

		usort(
			$migrations,
			static fn ( MigrationInterface $a, MigrationInterface $b ): int => strcmp( $a->version(), $b->version() )
		);

		foreach ( $migrations as $migration ) {
			if ( strcmp( $migration->version(), $current ) <= 0 ) {
				continue;
			}

			$migration->up( $this->wpdb );
			update_option( self::OPTION_KEY, $migration->version(), false );
			$current = $migration->version();
		}
	}
}
