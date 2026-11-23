<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native wp-admin CPT lists: instructors only see their own Mint posts.
 *
 * - Rows: `pre_get_posts` author scope (LearnDash-style).
 * - Status tabs: `wp_count_posts` scoped the same way so All/Publish/Draft/Trash
 *   counts match the table (fixes site-wide tab totals for instructors).
 */
final class AdminContentListScope {

	public function register(): void {
		add_action( 'pre_get_posts', array( $this, 'restrictMintAdminLists' ) );
		add_filter( 'wp_count_posts', array( $this, 'scopeMintPostCounts' ), 10, 3 );

		foreach ( PostTypes::all() as $postType ) {
			add_filter( 'views_edit-' . $postType, array( $this, 'scopeMintListViews' ) );
		}
	}

	public function restrictMintAdminLists( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// REST content lists are scoped in ContentPostListController.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( ! ContentOwnership::currentUserMustOwnContent() ) {
			return;
		}

		$postType = $query->get( 'post_type' );
		if ( ! $this->isMintListPostType( $postType ) ) {
			return;
		}

		$userId = get_current_user_id();
		if ( $userId <= 0 ) {
			return;
		}

		$query->set( 'author', $userId );
	}

	/**
	 * Scope native list-table status counts to the current instructor.
	 *
	 * WP_Posts_List_Table::get_views() reads wp_count_posts(); without this,
	 * tabs stay site-wide while rows are author-scoped.
	 *
	 * @param \stdClass $counts Post status counts.
	 * @param string    $type   Post type.
	 * @param string    $perm   Permission context from core (readable|editable|'').
	 */
	public function scopeMintPostCounts( object $counts, string $type, string $perm = '' ): object { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $counts;
		}

		if ( ! PostTypes::isContentType( $type ) || ! ContentOwnership::currentUserMustOwnContent() ) {
			return $counts;
		}

		$userId = get_current_user_id();
		if ( $userId <= 0 ) {
			return $counts;
		}

		$scoped = $this->countPostsByAuthor( $type, $userId );
		if ( null === $scoped ) {
			return $counts;
		}

		foreach ( get_object_vars( $counts ) as $status => $_num ) {
			$counts->{$status} = 0;
		}

		foreach ( $scoped as $status => $num ) {
			$counts->{$status} = (int) $num;
		}

		return $counts;
	}

	/**
	 * Belt-and-suspenders: rebuild visible tab labels from scoped counts.
	 *
	 * @param array<string, string> $views Status view links HTML.
	 * @return array<string, string>
	 */
	public function scopeMintListViews( array $views ): array {
		if ( ! ContentOwnership::currentUserMustOwnContent() ) {
			return $views;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$postType = ( null !== $screen && isset( $screen->post_type ) ) ? (string) $screen->post_type : '';

		if ( ! PostTypes::isContentType( $postType ) ) {
			return $views;
		}

		$userId = get_current_user_id();
		if ( $userId <= 0 ) {
			return $views;
		}

		$byStatus = $this->countPostsByAuthor( $postType, $userId );
		if ( null === $byStatus ) {
			return $views;
		}

		$all = 0;
		foreach ( $byStatus as $status => $num ) {
			if ( in_array( (string) $status, array( 'trash', 'auto-draft', 'inherit' ), true ) ) {
				continue;
			}
			$all += (int) $num;
		}

		$map = array(
			'all'     => $all,
			'mine'    => $all,
			'publish' => (int) ( $byStatus['publish'] ?? 0 ),
			'draft'   => (int) ( $byStatus['draft'] ?? 0 ),
			'pending' => (int) ( $byStatus['pending'] ?? 0 ),
			'private' => (int) ( $byStatus['private'] ?? 0 ),
			'future'  => (int) ( $byStatus['future'] ?? 0 ),
			'trash'   => (int) ( $byStatus['trash'] ?? 0 ),
		);

		if ( isset( $byStatus[ PostTypes::STATUS_ARCHIVED ] ) ) {
			$map[ PostTypes::STATUS_ARCHIVED ] = (int) $byStatus[ PostTypes::STATUS_ARCHIVED ];
		}

		foreach ( $views as $key => $html ) {
			if ( ! isset( $map[ $key ] ) ) {
				continue;
			}
			$views[ $key ] = $this->replaceViewCount( (string) $html, (int) $map[ $key ] );
		}

		return $views;
	}

	/**
	 * @return array<string, int>|null status => count, or null on DB failure.
	 */
	private function countPostsByAuthor( string $postType, int $authorId ): ?array {
		$wpdb = mintlms_wpdb();
		if ( null === $wpdb ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Author-scoped admin counts; mirrors wp_count_posts.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_status, COUNT(ID) AS num FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d GROUP BY post_status",
				$postType,
				$authorId
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return null;
		}

		$out = array();
		foreach ( $rows as $row ) {
			if ( ! isset( $row['post_status'] ) ) {
				continue;
			}
			$out[ (string) $row['post_status'] ] = (int) ( $row['num'] ?? 0 );
		}

		return $out;
	}

	private function replaceViewCount( string $html, int $count ): string {
		$formatted = number_format_i18n( $count );
		$replaced  = preg_replace(
			'/<span class="count">\([\d.,\s]+\)<\/span>/',
			'<span class="count">(' . esc_html( $formatted ) . ')</span>',
			$html,
			1
		);

		return is_string( $replaced ) ? $replaced : $html;
	}

	/**
	 * @param mixed $postType Query post_type value.
	 */
	private function isMintListPostType( mixed $postType ): bool {
		if ( is_string( $postType ) ) {
			return PostTypes::isContentType( $postType );
		}

		if ( ! is_array( $postType ) || array() === $postType ) {
			return false;
		}

		foreach ( $postType as $type ) {
			if ( ! is_string( $type ) || ! PostTypes::isContentType( $type ) ) {
				return false;
			}
		}

		return true;
	}
}
