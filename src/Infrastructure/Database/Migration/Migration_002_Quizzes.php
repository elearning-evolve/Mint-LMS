<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;

final class Migration_002_Quizzes implements MigrationInterface {

	public function version(): string {
		return '002';
	}

	public function up( \wpdb $wpdb ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		$quizzes = Schema::validateTable( Schema::quizzesTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$quizzes} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                lesson_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                title varchar(255) NOT NULL,
                pass_percent tinyint(3) unsigned NOT NULL DEFAULT 70,
                sort_order int(10) unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                UNIQUE KEY lesson_id (lesson_id),
                KEY course_id (course_id)
            ) {$charset};"
		);

		$questions = Schema::validateTable( Schema::quizQuestionsTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$questions} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                quiz_id bigint(20) unsigned NOT NULL,
                type varchar(20) NOT NULL DEFAULT 'mcq',
                prompt text NOT NULL,
                options_json longtext NULL,
                correct_answer varchar(255) NOT NULL,
                sort_order int(10) unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                KEY quiz_order (quiz_id, sort_order)
            ) {$charset};"
		);

		$attempts = Schema::validateTable( Schema::quizAttemptsTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$attempts} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                quiz_id bigint(20) unsigned NOT NULL,
                score_percent decimal(5,2) NOT NULL DEFAULT 0.00,
                passed tinyint(1) NOT NULL DEFAULT 0,
                answers_json longtext NOT NULL,
                completed_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY user_quiz (user_id, quiz_id),
                KEY quiz_id (quiz_id)
            ) {$charset};"
		);
	}
}
