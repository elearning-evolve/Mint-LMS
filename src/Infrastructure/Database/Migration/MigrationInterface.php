<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

interface MigrationInterface {

	public function version(): string;

	public function up( \wpdb $wpdb ): void;
}
