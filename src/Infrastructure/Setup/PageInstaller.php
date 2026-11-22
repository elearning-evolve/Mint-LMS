<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Installs Mint student pages (dashboard / catalog / player).
 *
 * Uses Mint-owned slugs so Tutor/LearnDash/Lifter rewrite bases (e.g. "courses")
 * cannot steal View/Preview URLs. Existing installs are migrated off conflict slugs.
 */
final class PageInstaller {

	private const META_ROLE           = '_mintlms_page_role';
	private const OPTION_FLUSH_NEEDED = 'mintlms_student_pages_flush_rewrites';

	/**
	 * Slugs commonly claimed by other LMS plugins or course archives.
	 *
	 * @var list<string>
	 */
	private const CONFLICT_SLUGS = array(
		'courses',
		'course',
		'lessons',
		'lesson',
		'quizzes',
		'quiz',
	);

	/**
	 * @var array<string, array{title: string, shortcode: string, slug: string, option: string}>
	 */
	private const PAGES = array(
		'dashboard' => array(
			'title'     => 'My Learning',
			'shortcode' => '[mint_lms_dashboard]',
			'slug'      => 'mint-lms-learning',
			'option'    => PageSettings::OPTION_DASHBOARD,
		),
		'catalog'   => array(
			'title'     => 'Courses',
			'shortcode' => '[mint_lms_catalog]',
			'slug'      => 'mint-lms-courses',
			'option'    => PageSettings::OPTION_CATALOG,
		),
		'player'    => array(
			'title'     => 'Course Player',
			'shortcode' => '[mint_lms_player]',
			'slug'      => 'mint-lms-player',
			'option'    => PageSettings::OPTION_PLAYER,
		),
	);

	public function register(): void {
		add_action( 'init', array( $this, 'maybeRepairStudentPages' ), 30 );
	}

	public function install(): void {
		foreach ( self::PAGES as $role => $config ) {
			$this->ensurePage( $role, $config );
		}

		$this->ensureConflictFreeSlugs();
	}

	/**
	 * Existing sites: keep pages, move off colliding slugs without user action.
	 */
	public function maybeRepairStudentPages(): void {
		if ( wp_installing() ) {
			return;
		}

		$this->install();
		$this->maybeFlushRewrites();
	}

	/**
	 * @param array{title: string, shortcode: string, slug: string, option: string} $config
	 */
	private function ensurePage( string $role, array $config ): void {
		$storedId = (int) get_option( $config['option'], 0 );

		if ( $storedId > 0 && $this->isUsablePage( $storedId, $config['shortcode'] ) ) {
			update_post_meta( $storedId, self::META_ROLE, $role );

			return;
		}

		$existingId = $this->findPageByRole( $role );

		if ( null !== $existingId && $this->isUsablePage( $existingId, $config['shortcode'] ) ) {
			update_option( $config['option'], $existingId, false );
			update_post_meta( $existingId, self::META_ROLE, $role );

			return;
		}

		$pageId = wp_insert_post(
			array(
				'post_title'   => $config['title'],
				'post_name'    => $config['slug'],
				'post_content' => $config['shortcode'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => $this->authorId(),
			),
			true
		);

		if ( is_wp_error( $pageId ) || ! is_int( $pageId ) || $pageId <= 0 ) {
			return;
		}

		update_post_meta( $pageId, self::META_ROLE, $role );
		update_option( $config['option'], $pageId, false );
		$this->markFlushNeeded();
	}

	private function ensureConflictFreeSlugs(): void {
		foreach ( self::PAGES as $role => $config ) {
			$pageId = (int) get_option( $config['option'], 0 );

			if ( $pageId <= 0 || ! $this->isUsablePage( $pageId, $config['shortcode'] ) ) {
				continue;
			}

			$post = get_post( $pageId );

			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$current = (string) $post->post_name;
			$desired = $config['slug'];

			if ( $current === $desired || ! $this->slugNeedsMigration( $current ) ) {
				continue;
			}

			$unique = wp_unique_post_slug( $desired, $pageId, $post->post_status, 'page', $post->post_parent );
			$result = wp_update_post(
				array(
					'ID'        => $pageId,
					'post_name' => $unique,
				),
				true
			);

			if ( ! is_wp_error( $result ) ) {
				$this->markFlushNeeded();
			}
		}
	}

	private function slugNeedsMigration( string $current ): bool {
		$current = sanitize_title( $current );

		return in_array( $current, self::CONFLICT_SLUGS, true );
	}

	private function markFlushNeeded(): void {
		update_option( self::OPTION_FLUSH_NEEDED, '1', false );
	}

	private function maybeFlushRewrites(): void {
		if ( '1' !== (string) get_option( self::OPTION_FLUSH_NEEDED, '' ) ) {
			return;
		}

		flush_rewrite_rules( false );
		delete_option( self::OPTION_FLUSH_NEEDED );
	}

	private function findPageByRole( string $role ): ?int {
		$query = new \WP_Query(
			array(
				'post_type'              => 'page',
				'post_status'            => array( 'publish', 'draft', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_ROLE,
						'value' => $role,
					),
				),
			)
		);

		if ( empty( $query->posts ) ) {
			return null;
		}

		return (int) $query->posts[0];
	}

	private function isUsablePage( int $pageId, string $expectedShortcode ): bool {
		$post = get_post( $pageId );

		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
			return false;
		}

		if ( ! in_array( $post->post_status, array( 'publish', 'draft', 'private' ), true ) ) {
			return false;
		}

		return str_contains( (string) $post->post_content, $expectedShortcode );
	}

	private function authorId(): int {
		$userId = get_current_user_id();

		if ( $userId > 0 ) {
			return $userId;
		}

		return 1;
	}
}
