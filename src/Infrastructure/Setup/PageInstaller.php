<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Setup;

defined( 'ABSPATH' ) || exit;

final class PageInstaller {

	private const META_ROLE = '_mintlms_page_role';

	/**
	 * @var array<string, array{title: string, shortcode: string, slug: string, option: string}>
	 */
	private const PAGES = array(
		'dashboard' => array(
			'title'     => 'My Learning',
			'shortcode' => '[mint_lms_dashboard]',
			'slug'      => 'my-learning',
			'option'    => PageSettings::OPTION_DASHBOARD,
		),
		'catalog'   => array(
			'title'     => 'Courses',
			'shortcode' => '[mint_lms_catalog]',
			'slug'      => 'courses',
			'option'    => PageSettings::OPTION_CATALOG,
		),
		'player'    => array(
			'title'     => 'Course Player',
			'shortcode' => '[mint_lms_player]',
			'slug'      => 'course-player',
			'option'    => PageSettings::OPTION_PLAYER,
		),
	);

	public function install(): void {
		foreach ( self::PAGES as $role => $config ) {
			$this->ensurePage( $role, $config );
		}
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
			update_option( $config['option'], $existingId );

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
		update_option( $config['option'], $pageId );
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
