<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Setup;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Database\Schema;
use MintLMS\Infrastructure\PostType\PostTypeRegistrar;
use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\WooCommerce\EnrollmentBridge;

/**
 * One-shot legacy custom-table → CPT content migration.
 *
 * Safe to call on boot: skips when already migrated or no legacy courses exist.
 * Failures are logged and do not fatally break plugin load.
 */
final class ContentMigrator {

	public const OPTION_MIGRATED = 'mintlms_cpt_content_migrated';

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function maybeMigrate(): void {
		if ( '1' === (string) get_option( self::OPTION_MIGRATED, '' ) ) {
			return;
		}

		try {
			$coursesTable = Schema::coursesTable( $this->wpdb->prefix );

			if ( ! $this->tableExists( $coursesTable ) || $this->tableIsEmpty( $coursesTable ) ) {
				update_option( self::OPTION_MIGRATED, '1', false );

				return;
			}

			$this->ensurePostTypesRegistered();
			$this->migrate();
			update_option( self::OPTION_MIGRATED, '1', false );
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Migration failure must not break boot.
			error_log( 'Mint LMS CPT content migration failed: ' . $e->getMessage() );
		}
	}

	private function migrate(): void {
		$this->assertHybridSchemaReady();

		$startedTxn = $this->beginTransaction();

		try {
			$this->migrateCourses();
			$this->remapSectionsCourseIds();
			$this->migrateLessons();
			$this->remapProgressIds();
			$this->remapEnrollmentsCourseIds();
			$this->migrateQuizzes();
			$this->migrateQuestions();
			$this->remapQuizAttempts();
			$this->remapWooCommerceCourseMeta();

			if ( $startedTxn ) {
				$this->commitTransaction();
			}
		} catch ( \Throwable $e ) {
			if ( $startedTxn ) {
				$this->rollbackTransaction();
			}

			throw $e;
		}
	}

	/**
	 * Migration_006 must have created id_map, question_data, and the quiz↔question junction.
	 */
	private function assertHybridSchemaReady(): void {
		$idMap = Schema::idMapTable( $this->wpdb->prefix );
		$data  = Schema::questionDataTable( $this->wpdb->prefix );
		$junction = Schema::quizQuestionsTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $idMap ) || ! $this->tableExists( $data ) ) {
			throw new \RuntimeException( 'CPT hybrid tables (id_map / question_data) missing; run DB migrations first.' );
		}

		if ( ! $this->tableExists( $junction ) || ! $this->columnExists( $junction, 'question_id' ) ) {
			throw new \RuntimeException( 'Quiz questions junction table not ready; run migration 006 first.' );
		}
	}

	private function ensurePostTypesRegistered(): void {
		// CLI / odd bootstraps can reach init without WP_Rewrite; wp_insert_post needs it for slug uniqueness.
		// WordPress requires $wp_rewrite in the global scope — this is the documented bootstrap pattern.
		global $wp_rewrite;

		if ( ! $wp_rewrite instanceof \WP_Rewrite ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required when rewrite was never bootstrapped (CLI).
			$wp_rewrite = new \WP_Rewrite();
		}

		if ( ! post_type_exists( PostTypes::COURSE ) ) {
			$registrar = new PostTypeRegistrar();
			$registrar->registerPostTypes();
			$registrar->registerStatuses();
		}
	}

	private function migrateCourses(): void {
		$table = Schema::validateTable( Schema::coursesTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC" );
		// phpcs:enable

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$legacyId = (int) $row->id;

			if ( $this->mappedPostId( 'course', $legacyId ) > 0 ) {
				continue;
			}

			$settingsJson = isset( $row->settings_json ) ? (string) $row->settings_json : '{}';
			$postId       = wp_insert_post(
				array(
					'post_type'     => PostTypes::COURSE,
					'post_status'   => $this->mapCourseStatus( (string) $row->status ),
					'post_title'    => (string) $row->title,
					'post_name'     => (string) $row->slug,
					'post_content'  => (string) ( $row->description ?? '' ),
					'post_author'   => (int) $row->author_id,
					'post_date'     => $this->normalizeMysqlDatetime( (string) $row->created_at ),
					'post_date_gmt' => get_gmt_from_date( $this->normalizeMysqlDatetime( (string) $row->created_at ) ),
				),
				true
			);

			if ( is_wp_error( $postId ) ) {
				throw new \RuntimeException( 'Course migrate failed for #' . $legacyId . ': ' . $postId->get_error_message() );
			}

			$postId = (int) $postId;

			update_post_meta( $postId, PostTypes::META_ENROLLMENT_TYPE, (string) $row->enrollment_type );
			update_post_meta( $postId, PostTypes::META_COURSE_SETTINGS, $settingsJson );
			update_post_meta( $postId, PostTypes::META_LEGACY_ID, $legacyId );

			$thumb = isset( $row->featured_image_id ) ? (int) $row->featured_image_id : 0;
			if ( $thumb > 0 ) {
				set_post_thumbnail( $postId, $thumb );
			}

			$this->writeIdMap( 'course', $legacyId, $postId );
		}
	}

	private function remapSectionsCourseIds(): void {
		$sections = Schema::sectionsTable( $this->wpdb->prefix );
		$idMap    = Schema::idMapTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $sections ) || ! $this->tableExists( $idMap ) ) {
			return;
		}

		$sections = Schema::validateTable( $sections, $this->wpdb->prefix );
		$idMap    = Schema::validateTable( $idMap, $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query(
			"UPDATE {$sections} s
			INNER JOIN {$idMap} m ON m.entity_type = 'course' AND m.legacy_id = s.course_id
			SET s.course_id = m.post_id"
		);
		// phpcs:enable
	}

	private function migrateLessons(): void {
		$table = Schema::lessonsTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $table ) ) {
			return;
		}

		$table = Schema::validateTable( $table, $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC" );
		// phpcs:enable

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$legacyId = (int) $row->id;

			if ( $this->mappedPostId( 'lesson', $legacyId ) > 0 ) {
				continue;
			}

			$mappedCourseId = $this->mappedPostId( 'course', (int) $row->course_id );
			$courseId       = $mappedCourseId > 0 ? $mappedCourseId : (int) $row->course_id;

			$postId = wp_insert_post(
				array(
					'post_type'     => PostTypes::LESSON,
					'post_status'   => 'publish',
					'post_title'    => (string) $row->title,
					'post_name'     => (string) $row->slug,
					'post_content'  => (string) ( $row->content ?? '' ),
					'post_date'     => $this->normalizeMysqlDatetime( (string) $row->created_at ),
					'post_date_gmt' => get_gmt_from_date( $this->normalizeMysqlDatetime( (string) $row->created_at ) ),
				),
				true
			);

			if ( is_wp_error( $postId ) ) {
				throw new \RuntimeException( 'Lesson migrate failed for #' . $legacyId . ': ' . $postId->get_error_message() );
			}

			$postId = (int) $postId;

			update_post_meta( $postId, PostTypes::META_SECTION_ID, (int) $row->section_id );
			update_post_meta( $postId, PostTypes::META_COURSE_ID, $courseId );
			update_post_meta( $postId, PostTypes::META_VIDEO_URL, (string) ( $row->video_url ?? '' ) );
			update_post_meta( $postId, PostTypes::META_ATTACHMENT_ID, (int) ( $row->attachment_id ?? 0 ) );
			update_post_meta( $postId, PostTypes::META_IS_PREVIEW, (int) ( $row->is_preview ?? 0 ) ? '1' : '0' );
			update_post_meta( $postId, PostTypes::META_SORT_ORDER, (int) $row->sort_order );
			update_post_meta( $postId, PostTypes::META_LEGACY_ID, $legacyId );

			if ( isset( $row->available_after_days ) && null !== $row->available_after_days && '' !== $row->available_after_days ) {
				update_post_meta( $postId, PostTypes::META_AVAILABLE_AFTER_DAYS, (int) $row->available_after_days );
			}

			$this->writeIdMap( 'lesson', $legacyId, $postId );
		}
	}

	private function remapProgressIds(): void {
		$progress = Schema::progressTable( $this->wpdb->prefix );
		$summary  = Schema::progressSummaryTable( $this->wpdb->prefix );
		$idMap    = Schema::idMapTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $idMap ) ) {
			return;
		}

		$idMap = Schema::validateTable( $idMap, $this->wpdb->prefix );

		if ( $this->tableExists( $progress ) ) {
			$progress = Schema::validateTable( $progress, $this->wpdb->prefix );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query(
				"UPDATE {$progress} p
				INNER JOIN {$idMap} m ON m.entity_type = 'lesson' AND m.legacy_id = p.lesson_id
				SET p.lesson_id = m.post_id"
			);
			$this->wpdb->query(
				"UPDATE {$progress} p
				INNER JOIN {$idMap} m ON m.entity_type = 'course' AND m.legacy_id = p.course_id
				SET p.course_id = m.post_id"
			);
			// phpcs:enable
		}

		if ( $this->tableExists( $summary ) ) {
			$summary = Schema::validateTable( $summary, $this->wpdb->prefix );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query(
				"UPDATE {$summary} s
				INNER JOIN {$idMap} m ON m.entity_type = 'course' AND m.legacy_id = s.course_id
				SET s.course_id = m.post_id"
			);
			$this->wpdb->query(
				"UPDATE {$summary} s
				INNER JOIN {$idMap} m ON m.entity_type = 'lesson' AND m.legacy_id = s.last_lesson_id
				SET s.last_lesson_id = m.post_id
				WHERE s.last_lesson_id IS NOT NULL AND s.last_lesson_id > 0"
			);
			// phpcs:enable
		}
	}

	private function remapEnrollmentsCourseIds(): void {
		$enrollments = Schema::enrollmentsTable( $this->wpdb->prefix );
		$idMap       = Schema::idMapTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $enrollments ) || ! $this->tableExists( $idMap ) ) {
			return;
		}

		$enrollments = Schema::validateTable( $enrollments, $this->wpdb->prefix );
		$idMap       = Schema::validateTable( $idMap, $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query(
			"UPDATE {$enrollments} e
			INNER JOIN {$idMap} m ON m.entity_type = 'course' AND m.legacy_id = e.course_id
			SET e.course_id = m.post_id"
		);
		// phpcs:enable
	}

	private function migrateQuizzes(): void {
		$table = Schema::quizzesTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $table ) ) {
			return;
		}

		$table = Schema::validateTable( $table, $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC" );
		// phpcs:enable

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$legacyId = (int) $row->id;

			if ( $this->mappedPostId( 'quiz', $legacyId ) > 0 ) {
				continue;
			}

			$lessonId = $this->mappedPostId( 'lesson', (int) $row->lesson_id );
			$courseId = $this->mappedPostId( 'course', (int) $row->course_id );
			$lessonId = $lessonId > 0 ? $lessonId : (int) $row->lesson_id;
			$courseId = $courseId > 0 ? $courseId : (int) $row->course_id;

			$postId = wp_insert_post(
				array(
					'post_type'   => PostTypes::QUIZ,
					'post_status' => 'publish',
					'post_title'  => (string) $row->title,
				),
				true
			);

			if ( is_wp_error( $postId ) ) {
				throw new \RuntimeException( 'Quiz migrate failed for #' . $legacyId . ': ' . $postId->get_error_message() );
			}

			$postId = (int) $postId;

			update_post_meta( $postId, PostTypes::META_LESSON_ID, $lessonId );
			update_post_meta( $postId, PostTypes::META_COURSE_ID, $courseId );
			update_post_meta( $postId, PostTypes::META_PASS_PERCENT, (int) $row->pass_percent );
			update_post_meta( $postId, PostTypes::META_SORT_ORDER, (int) $row->sort_order );
			update_post_meta( $postId, PostTypes::META_LEGACY_ID, $legacyId );

			$this->writeIdMap( 'quiz', $legacyId, $postId );
		}
	}

	private function migrateQuestions(): void {
		$source = $this->resolveLegacyQuestionsTable();

		if ( null === $source ) {
			return;
		}

		$dataTable = Schema::questionDataTable( $this->wpdb->prefix );
		$junction  = Schema::quizQuestionsTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $dataTable ) || ! $this->tableExists( $junction ) ) {
			return;
		}

		$dataTable = Schema::validateTable( $dataTable, $this->wpdb->prefix );
		$junction  = Schema::validateTable( $junction, $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->wpdb->get_results(
			"SELECT id, quiz_id, type, prompt, options_json, correct_answer, sort_order
			FROM {$source}
			ORDER BY quiz_id ASC, sort_order ASC, id ASC"
		);
		// phpcs:enable

		if ( ! is_array( $rows ) ) {
			return;
		}

		$now = current_time( 'mysql' );

		foreach ( $rows as $row ) {
			$legacyId = (int) $row->id;

			if ( $this->mappedPostId( 'question', $legacyId ) > 0 ) {
				continue;
			}

			$mappedQuizId = $this->mappedPostId( 'quiz', (int) $row->quiz_id );
			if ( $mappedQuizId <= 0 ) {
				continue;
			}

			$prompt      = (string) $row->prompt;
			$titleContent = $this->splitPromptForPost( $prompt );

			$postId = wp_insert_post(
				array(
					'post_type'    => PostTypes::QUESTION,
					'post_status'  => 'publish',
					'post_title'   => $titleContent['title'],
					'post_content' => $titleContent['content'],
				),
				true
			);

			if ( is_wp_error( $postId ) ) {
				throw new \RuntimeException( 'Question migrate failed for #' . $legacyId . ': ' . $postId->get_error_message() );
			}

			$postId = (int) $postId;

			update_post_meta( $postId, PostTypes::META_LEGACY_ID, $legacyId );

			$optionsJson = (string) ( $row->options_json ?? '[]' );
			if ( '' === $optionsJson ) {
				$optionsJson = '[]';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $this->wpdb->insert(
				$dataTable,
				array(
					'post_id'        => $postId,
					'question_type'  => (string) $row->type,
					'options_json'   => $optionsJson,
					'correct_answer' => (string) $row->correct_answer,
					'points'         => 1,
					'created_at'     => $now,
					'updated_at'     => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
			);

			if ( false === $inserted ) {
				wp_delete_post( $postId, true );
				throw new \RuntimeException( 'Question data migrate failed for #' . $legacyId );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$linked = $this->wpdb->insert(
				$junction,
				array(
					'quiz_id'     => $mappedQuizId,
					'question_id' => $postId,
					'sort_order'  => (int) $row->sort_order,
				),
				array( '%d', '%d', '%d' )
			);

			if ( false === $linked ) {
				$this->wpdb->delete( $dataTable, array( 'post_id' => $postId ), array( '%d' ) );
				wp_delete_post( $postId, true );
				throw new \RuntimeException( 'Question junction migrate failed for #' . $legacyId );
			}

			$this->writeIdMap( 'question', $legacyId, $postId );
		}
	}

	private function remapQuizAttempts(): void {
		$attempts = Schema::quizAttemptsTable( $this->wpdb->prefix );
		$idMap    = Schema::idMapTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $attempts ) || ! $this->tableExists( $idMap ) ) {
			return;
		}

		$attempts = Schema::validateTable( $attempts, $this->wpdb->prefix );
		$idMap    = Schema::validateTable( $idMap, $this->wpdb->prefix );

		// Remap quiz_id via id_map.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query(
			"UPDATE {$attempts} a
			INNER JOIN {$idMap} m ON m.entity_type = 'quiz' AND m.legacy_id = a.quiz_id
			SET a.quiz_id = m.post_id"
		);

		$rows = $this->wpdb->get_results( "SELECT id, answers_json FROM {$attempts}" );
		// phpcs:enable

		if ( ! is_array( $rows ) ) {
			return;
		}

		$questionMap = $this->loadEntityMap( 'question' );

		foreach ( $rows as $row ) {
			$decoded = json_decode( (string) $row->answers_json, true );

			if ( ! is_array( $decoded ) || array() === $decoded ) {
				continue;
			}

			$rewritten = array();
			$changed   = false;

			foreach ( $decoded as $legacyQuestionId => $answer ) {
				$key = (int) $legacyQuestionId;
				if ( isset( $questionMap[ $key ] ) ) {
					$rewritten[ (string) $questionMap[ $key ] ] = $answer;
					$changed = true;
				} else {
					$rewritten[ (string) $key ] = $answer;
				}
			}

			if ( ! $changed ) {
				continue;
			}

			$json = wp_json_encode( $rewritten );
			if ( ! is_string( $json ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->update(
				$attempts,
				array( 'answers_json' => $json ),
				array( 'id' => (int) $row->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}

	private function remapWooCommerceCourseMeta(): void {
		$metaKey = EnrollmentBridge::META_COURSE_ID;
		$idMap   = Schema::idMapTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $idMap ) ) {
			return;
		}

		$idMap    = Schema::validateTable( $idMap, $this->wpdb->prefix );
		$postmeta = $this->wpdb->postmeta;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$postmeta} pm
				INNER JOIN {$idMap} m ON m.entity_type = 'course' AND m.legacy_id = CAST(pm.meta_value AS UNSIGNED)
				SET pm.meta_value = m.post_id
				WHERE pm.meta_key = %s
				AND pm.meta_value REGEXP '^[0-9]+$'",
				$metaKey
			)
		);
		// phpcs:enable
	}

	/**
	 * Prefer renamed legacy table; fall back to junction table still holding owned-question rows.
	 */
	private function resolveLegacyQuestionsTable(): ?string {
		$legacy = Schema::quizQuestionsLegacyTable( $this->wpdb->prefix );

		if ( $this->tableExists( $legacy ) ) {
			return Schema::validateTable( $legacy, $this->wpdb->prefix );
		}

		$questions = Schema::quizQuestionsTable( $this->wpdb->prefix );

		if ( $this->tableExists( $questions ) && $this->columnExists( $questions, 'prompt' ) ) {
			return Schema::validateTable( $questions, $this->wpdb->prefix );
		}

		return null;
	}

	/**
	 * @return array{title: string, content: string}
	 */
	private function splitPromptForPost( string $prompt ): array {
		if ( strlen( $prompt ) > 200 ) {
			$title = wp_trim_words( $prompt, 20, '…' );

			if ( '' === $title ) {
				$title = substr( $prompt, 0, 200 );
			}

			return array(
				'title'   => $title,
				'content' => $prompt,
			);
		}

		return array(
			'title'   => $prompt,
			'content' => '',
		);
	}

	private function mapCourseStatus( string $status ): string {
		return match ( $status ) {
			'published' => 'publish',
			'archived'  => PostTypes::STATUS_ARCHIVED,
			'trashed'   => 'trash',
			default     => 'draft',
		};
	}

	private function mappedPostId( string $entityType, int $legacyId ): int {
		if ( $legacyId <= 0 ) {
			return 0;
		}

		$idMap = Schema::idMapTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $idMap ) ) {
			return 0;
		}

		$idMap = Schema::validateTable( $idMap, $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$postId = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT post_id FROM {$idMap} WHERE entity_type = %s AND legacy_id = %d",
				$entityType,
				$legacyId
			)
		);
		// phpcs:enable

		return (int) $postId;
	}

	private function writeIdMap( string $entityType, int $legacyId, int $postId ): void {
		$idMap = Schema::validateTable( Schema::idMapTable( $this->wpdb->prefix ), $this->wpdb->prefix );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $this->wpdb->insert(
			$idMap,
			array(
				'entity_type' => $entityType,
				'legacy_id'   => $legacyId,
				'post_id'     => $postId,
			),
			array( '%s', '%d', '%d' )
		);

		if ( false === $inserted ) {
			throw new \RuntimeException(
				sprintf( 'Failed to write id_map for %s #%d → %d', $entityType, $legacyId, $postId )
			);
		}
	}

	/**
	 * @return array<int, int> legacy_id => post_id
	 */
	private function loadEntityMap( string $entityType ): array {
		$idMap = Schema::idMapTable( $this->wpdb->prefix );

		if ( ! $this->tableExists( $idMap ) ) {
			return array();
		}

		$idMap = Schema::validateTable( $idMap, $this->wpdb->prefix );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT legacy_id, post_id FROM {$idMap} WHERE entity_type = %s",
				$entityType
			)
		);
		// phpcs:enable

		$map = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$map[ (int) $row->legacy_id ] = (int) $row->post_id;
			}
		}

		return $map;
	}

	private function tableExists( string $table ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		return is_string( $found ) && $found === $table;
	}

	private function tableIsEmpty( string $table ): bool {
		if ( ! $this->tableExists( $table ) ) {
			return true;
		}

		// Only call for schema-known tables.
		try {
			$table = Schema::validateTable( $table, $this->wpdb->prefix );
		} catch ( \InvalidArgumentException $e ) {
			return true;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		// phpcs:enable

		return (int) $count === 0;
	}

	private function columnExists( string $table, string $column ): bool {
		if ( ! $this->tableExists( $table ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cols = $this->wpdb->get_col( $this->wpdb->prepare( 'DESC %i', $table ), 0 );

		return is_array( $cols ) && in_array( $column, $cols, true );
	}

	private function normalizeMysqlDatetime( string $value ): string {
		$value = trim( $value );

		if ( '' === $value || '0000-00-00 00:00:00' === $value ) {
			return current_time( 'mysql' );
		}

		return $value;
	}

	private function beginTransaction(): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $this->wpdb->query( 'START TRANSACTION' );

		return false !== $result;
	}

	private function commitTransaction(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->query( 'COMMIT' );
	}

	private function rollbackTransaction(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->wpdb->query( 'ROLLBACK' );
	}
}
