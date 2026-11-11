<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Migration;

use MintLMS\Infrastructure\Database\Schema;

final class Migration_001_InitialSchema implements MigrationInterface {

	public function version(): string {
		return '001';
	}

	public function up( \wpdb $wpdb ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		$courses = Schema::validateTable( Schema::coursesTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$courses} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                slug varchar(255) NOT NULL,
                description longtext NULL,
                featured_image_id bigint(20) unsigned NULL,
                status varchar(20) NOT NULL DEFAULT 'draft',
                enrollment_type varchar(20) NOT NULL DEFAULT 'open',
                author_id bigint(20) unsigned NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY slug (slug),
                KEY status_author (status, author_id)
            ) {$charset};"
		);

		$sections = Schema::validateTable( Schema::sectionsTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$sections} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                course_id bigint(20) unsigned NOT NULL,
                title varchar(255) NOT NULL,
                sort_order int(10) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY course_order (course_id, sort_order)
            ) {$charset};"
		);

		$lessons = Schema::validateTable( Schema::lessonsTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$lessons} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                section_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                title varchar(255) NOT NULL,
                slug varchar(255) NOT NULL,
                content longtext NULL,
                video_url varchar(500) NULL,
                attachment_id bigint(20) unsigned NULL,
                is_preview tinyint(1) NOT NULL DEFAULT 0,
                sort_order int(10) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY section_order (section_id, sort_order),
                KEY course (course_id)
            ) {$charset};"
		);

		$enrollments = Schema::validateTable( Schema::enrollmentsTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$enrollments} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                enrolled_at datetime NOT NULL,
                expires_at datetime NULL,
                completed_at datetime NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_course (user_id, course_id),
                KEY course_status (course_id, status)
            ) {$charset};"
		);

		$progress = Schema::validateTable( Schema::progressTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$progress} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                lesson_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'completed',
                completed_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_lesson (user_id, lesson_id),
                KEY user_course (user_id, course_id),
                KEY course (course_id)
            ) {$charset};"
		);

		$summary = Schema::validateTable( Schema::progressSummaryTable( $prefix ), $prefix );
		dbDelta(
			"CREATE TABLE {$summary} (
                user_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                lessons_done int(10) unsigned NOT NULL DEFAULT 0,
                lessons_total int(10) unsigned NOT NULL DEFAULT 0,
                pct_complete decimal(5,2) NOT NULL DEFAULT 0.00,
                last_lesson_id bigint(20) unsigned NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (user_id, course_id)
            ) {$charset};"
		);
	}
}
