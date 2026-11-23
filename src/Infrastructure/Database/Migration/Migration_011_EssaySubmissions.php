<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;

final class Migration_011_EssaySubmissions implements MigrationInterface {

	public function version(): string {
		return '011';
	}

	public function up( \wpdb $wpdb ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$table   = Schema::validateTable( Schema::essaySubmissionsTable( $wpdb->prefix ), $wpdb->prefix );

		dbDelta(
			"CREATE TABLE {$table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                attempt_id bigint(20) unsigned NOT NULL,
                question_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                quiz_id bigint(20) unsigned NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                points_possible int(10) unsigned NOT NULL DEFAULT 1,
                points_awarded decimal(8,2) NOT NULL DEFAULT 0.00,
                answer_text longtext NULL,
                attachment_id bigint(20) unsigned NULL,
                graded_by bigint(20) unsigned NULL,
                graded_at datetime NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY attempt_question (attempt_id, question_id),
                KEY status_quiz (status, quiz_id),
                KEY user_id (user_id),
                KEY quiz_id (quiz_id)
            ) {$charset};"
		);
	}
}
