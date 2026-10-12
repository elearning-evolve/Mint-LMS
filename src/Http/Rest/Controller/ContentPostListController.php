<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Http\Rest\Response\ApiResponse;
use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\PostType\QuestionLinkResolver;
use MintLMS\Infrastructure\PostType\QuizLinkResolver;

/**
 * Admin list API for lesson / quiz / question CPT screens (A8 / A9 / A10).
 */
final class ContentPostListController {

	private const NAMESPACE = 'mintlms/v1';

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/content/(?P<type>lessons|quizzes|questions)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list' ),
				'permission_callback' => array( $this, 'canManage' ),
				'args'                => $this->listArgs(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/content/(?P<type>lessons|quizzes|questions)/(?P<id>\d+)/trash',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'trash' ),
				'permission_callback' => array( $this, 'canManage' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/content/(?P<type>lessons|quizzes|questions)/(?P<id>\d+)/restore',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'restore' ),
				'permission_callback' => array( $this, 'canManage' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/content/(?P<type>lessons|quizzes|questions)/(?P<id>\d+)/publish',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'publish' ),
				'permission_callback' => array( $this, 'canManage' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/content/(?P<type>lessons|quizzes|questions)/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => array( $this, 'canManage' ),
			)
		);
	}

	public function canManage(): bool {
		return current_user_can( 'edit_mintlms_courses' );
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function list( \WP_REST_Request $request ): \WP_REST_Response {
		$type     = (string) $request['type'];
		$postType = $this->postTypeFor( $type );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$perPage  = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$search   = trim( (string) $request->get_param( 'search' ) );
		$status   = (string) $request->get_param( 'status' );

		$queryArgs = array(
			'post_type'              => $postType,
			'post_status'            => $this->wpStatusesForFilter( $status ),
			'posts_per_page'         => $perPage,
			'paged'                  => $page,
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		);

		if ( '' !== $search ) {
			$queryArgs['s'] = $search;
		}

		$query = new \WP_Query( $queryArgs );
		$items = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$items[] = $this->serialize( $post, $type );
		}

		$counts = $this->statusTotals( $postType );

		return ApiResponse::success(
			array(
				'items'          => $items,
				'total'          => (int) $query->found_posts,
				'trashTotal'     => $counts['trashTotal'],
				'allTotal'       => $counts['allTotal'],
				'publishedTotal' => $counts['publishedTotal'],
				'draftTotal'     => $counts['draftTotal'],
				'archivedTotal'  => $counts['archivedTotal'],
			)
		);
	}

	/**
	 * @return array{allTotal: int, publishedTotal: int, draftTotal: int, archivedTotal: int, trashTotal: int}
	 */
	private function statusTotals( string $postType ): array {
		$base = array(
			'post_type'              => $postType,
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$all = new \WP_Query(
			array_merge(
				$base,
				array( 'post_status' => $this->wpStatusesForFilter( 'all' ) )
			)
		);
		$published = new \WP_Query(
			array_merge(
				$base,
				array( 'post_status' => $this->wpStatusesForFilter( 'published' ) )
			)
		);
		$draft = new \WP_Query(
			array_merge(
				$base,
				array( 'post_status' => $this->wpStatusesForFilter( 'draft' ) )
			)
		);
		$archived = new \WP_Query(
			array_merge(
				$base,
				array( 'post_status' => $this->wpStatusesForFilter( 'archived' ) )
			)
		);
		$trash = new \WP_Query(
			array_merge(
				$base,
				array( 'post_status' => 'trash' )
			)
		);

		return array(
			'allTotal'       => (int) $all->found_posts,
			'publishedTotal' => (int) $published->found_posts,
			'draftTotal'     => (int) $draft->found_posts,
			'archivedTotal'  => (int) $archived->found_posts,
			'trashTotal'     => (int) $trash->found_posts,
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function publish( \WP_REST_Request $request ): \WP_REST_Response {
		$post = $this->findOwnedPost( $request );
		if ( $post instanceof \WP_REST_Response ) {
			return $post;
		}

		if ( 'publish' !== $post->post_status ) {
			$result = wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'publish',
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				return ApiResponse::error( 'publish_failed', __( 'Could not make this live.', 'mint-lms' ), 500 );
			}
		}

		$fresh = get_post( $post->ID );
		if ( ! $fresh instanceof \WP_Post ) {
			return ApiResponse::error( 'not_found', __( 'Not found.', 'mint-lms' ), 404 );
		}

		return ApiResponse::success( $this->serialize( $fresh, (string) $request['type'] ) );
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function trash( \WP_REST_Request $request ): \WP_REST_Response {
		$post = $this->findOwnedPost( $request );
		if ( $post instanceof \WP_REST_Response ) {
			return $post;
		}

		$result = wp_trash_post( $post->ID );
		if ( ! $result ) {
			return ApiResponse::error( 'trash_failed', __( 'Could not move to trash.', 'mint-lms' ), 500 );
		}

		return ApiResponse::success( $this->serialize( get_post( $post->ID ), (string) $request['type'] ) );
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function restore( \WP_REST_Request $request ): \WP_REST_Response {
		$post = $this->findOwnedPost( $request, true );
		if ( $post instanceof \WP_REST_Response ) {
			return $post;
		}

		$result = wp_untrash_post( $post->ID );
		if ( ! $result ) {
			return ApiResponse::error( 'restore_failed', __( 'Could not restore.', 'mint-lms' ), 500 );
		}

		return ApiResponse::success( $this->serialize( get_post( $post->ID ), (string) $request['type'] ) );
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function delete( \WP_REST_Request $request ): \WP_REST_Response {
		$post = $this->findOwnedPost( $request, true );
		if ( $post instanceof \WP_REST_Response ) {
			return $post;
		}

		$result = wp_delete_post( $post->ID, true );
		if ( ! $result ) {
			return ApiResponse::error( 'delete_failed', __( 'Could not delete permanently.', 'mint-lms' ), 500 );
		}

		return ApiResponse::success( array( 'id' => (int) $post->ID ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function listArgs(): array {
		return array(
			'page'     => array(
				'type'    => 'integer',
				'default' => 1,
			),
			'per_page' => array(
				'type'    => 'integer',
				'default' => 20,
			),
			'search'   => array(
				'type'    => 'string',
				'default' => '',
			),
			'status'   => array(
				'type'    => 'string',
				'default' => 'all',
				'enum'    => array( 'all', 'published', 'draft', 'archived', 'trashed' ),
			),
		);
	}

	private function postTypeFor( string $type ): string {
		return match ( $type ) {
			'quizzes'   => PostTypes::QUIZ,
			'questions' => PostTypes::QUESTION,
			default     => PostTypes::LESSON,
		};
	}

	/**
	 * @return list<string>
	 */
	private function wpStatusesForFilter( string $status ): array {
		return match ( $status ) {
			'published' => array( 'publish' ),
			'draft'     => array( 'draft' ),
			'archived'  => array( 'private', PostTypes::STATUS_ARCHIVED ),
			'trashed'   => array( 'trash' ),
			default     => array( 'publish', 'draft', 'private', PostTypes::STATUS_ARCHIVED ),
		};
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return \WP_Post|\WP_REST_Response
	 */
	private function findOwnedPost( \WP_REST_Request $request, bool $allowTrash = false ): \WP_Post|\WP_REST_Response {
		$type     = (string) $request['type'];
		$postType = $this->postTypeFor( $type );
		$id       = (int) $request['id'];
		$post     = get_post( $id );

		if ( ! $post instanceof \WP_Post || $post->post_type !== $postType ) {
			return ApiResponse::error( 'not_found', __( 'Not found.', 'mint-lms' ), 404 );
		}

		if ( ! $allowTrash && 'trash' === $post->post_status ) {
			return ApiResponse::error( 'not_found', __( 'Not found.', 'mint-lms' ), 404 );
		}

		if ( ! current_user_can( 'delete_post', $post->ID ) && ! current_user_can( 'edit_post', $post->ID ) ) {
			return ApiResponse::error( 'forbidden', __( 'Forbidden.', 'mint-lms' ), 403 );
		}

		return $post;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function serialize( \WP_Post $post, string $type ): array {
		$author = get_userdata( (int) $post->post_author );
		$status = $this->mintStatus( $post->post_status );

		$quizId = 0;

		if ( 'quizzes' === $type ) {
			$links    = ( new QuizLinkResolver() )->readForList( (int) $post->ID );
			$courseId = $links['courseId'];
			$lessonId = $links['lessonId'];
			$quizId   = (int) $post->ID;
		} elseif ( 'questions' === $type ) {
			$links    = ( new QuestionLinkResolver() )->readForList( (int) $post->ID );
			$courseId = $links['courseId'];
			$lessonId = $links['lessonId'];
			$quizId   = $links['quizId'];
		} else {
			$courseId = (int) get_post_meta( $post->ID, PostTypes::META_COURSE_ID, true );
			$lessonId = (int) $post->ID;
		}

		$modified = get_post_modified_time( 'c', true, $post );
		if ( ! is_string( $modified ) || '' === $modified ) {
			$created = get_post_time( 'c', true, $post );
			$modified = is_string( $created ) && '' !== $created ? $created : '';
		}

		$title = $post->post_title;
		if ( 'questions' === $type && '' === $title && is_string( $post->post_content ) && '' !== trim( $post->post_content ) ) {
			$title = wp_trim_words( wp_strip_all_tags( $post->post_content ), 12, '…' );
		}

		return array(
			'id'              => (int) $post->ID,
			'title'           => '' !== $title ? $title : __( '(no title)', 'mint-lms' ),
			'authorName'      => $author instanceof \WP_User ? $author->display_name : '—',
			'status'          => $status,
			'date'            => $this->formatListDate( $modified ),
			'updatedAt'       => $modified,
			'courseId'        => $courseId,
			'lessonId'        => $lessonId,
			'quizId'          => $quizId,
			'disposableHost'  => 'lessons' === $type && PostTypes::isDisposableHostLesson( (int) $post->ID ),
		);
	}

	private function mintStatus( string $wpStatus ): string {
		return match ( $wpStatus ) {
			'publish'                   => 'published',
			'draft'                     => 'draft',
			'private', PostTypes::STATUS_ARCHIVED => 'archived',
			'trash'                     => 'trashed',
			default                     => 'draft',
		};
	}

	private function formatListDate( string $iso ): string {
		if ( '' === $iso ) {
			return '—';
		}

		$ts = strtotime( $iso );
		if ( false === $ts ) {
			return '—';
		}

		return wp_date( 'M j, Y', $ts );
	}
}
