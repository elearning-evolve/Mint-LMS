<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\PostType\QuestionLinkResolver;
use MintLMS\Infrastructure\Setup\PageSettings;

/**
 * Gutenberg / list-table "View" for Mint CPTs.
 *
 * Types stay non-public (no theme singles, search, or rewrite). They are
 * publicly_queryable so WP treats them as viewable, and all permalinks map
 * to Mint student preview / player URLs.
 */
final class NativeEditorViewLinks {

	public function __construct(
		private PageSettings $pages = new PageSettings()
	) {
	}

	public function register(): void {
		add_filter( 'post_type_link', array( $this, 'filterPermalink' ), 10, 2 );
		add_filter( 'preview_post_link', array( $this, 'filterPreviewLink' ), 10, 2 );
		add_filter( 'get_sample_permalink', array( $this, 'filterSamplePermalink' ), 10, 2 );
		add_filter( 'is_post_type_viewable', array( $this, 'filterIsViewable' ), 10, 2 );
		add_filter( 'rest_prepare_post_type', array( $this, 'filterRestPostType' ), 10, 2 );

		foreach ( PostTypes::all() as $postType ) {
			add_filter( 'rest_' . $postType . '_item_schema', array( $this, 'filterRestItemSchema' ) );
			add_filter( 'rest_prepare_' . $postType, array( $this, 'filterRestPost' ), 10, 2 );
		}

		add_action( 'template_redirect', array( $this, 'redirectSingularToStudentView' ), 4 );
	}

	/**
	 * Gutenberg PostViewLink reads permalink_template from REST. Core only adds
	 * that field when the type is public, so we expose it without making CPTs public.
	 *
	 * @param array<string, mixed> $schema REST item schema.
	 * @return array<string, mixed>
	 */
	public function filterRestItemSchema( array $schema ): array {
		if ( ! isset( $schema['properties'] ) || ! is_array( $schema['properties'] ) ) {
			$schema['properties'] = array();
		}

		$schema['properties']['permalink_template'] = array(
			'description' => __( 'Permalink template for the post.', 'mint-lms' ),
			'type'        => 'string',
			'context'     => array( 'edit' ),
			'readonly'    => true,
		);
		$schema['properties']['generated_slug']     = array(
			'description' => __( 'Slug automatically generated from the post title.', 'mint-lms' ),
			'type'        => 'string',
			'context'     => array( 'edit' ),
			'readonly'    => true,
		);

		return $schema;
	}

	/**
	 * @param bool                 $isViewable Whether the type is viewable.
	 * @param mixed                $postType   Post type object or name.
	 */
	public function filterIsViewable( bool $isViewable, mixed $postType ): bool {
		$name = '';
		if ( is_object( $postType ) && isset( $postType->name ) ) {
			$name = (string) $postType->name;
		} elseif ( is_string( $postType ) ) {
			$name = $postType;
		}

		return PostTypes::isContentType( $name ) ? true : $isViewable;
	}

	public function filterRestPostType( \WP_REST_Response $response, mixed $postType ): \WP_REST_Response {
		$name = is_object( $postType ) && isset( $postType->name ) ? (string) $postType->name : '';
		if ( ! PostTypes::isContentType( $name ) ) {
			return $response;
		}

		$data             = $response->get_data();
		$data['viewable'] = true;
		$response->set_data( $data );

		return $response;
	}

	/**
	 * @param string $postLink Raw CPT permalink.
	 */
	public function filterPermalink( string $postLink, mixed $post ): string {
		$resolved = $this->postFromMixed( $post );
		$url      = $this->studentViewUrl( $resolved );

		return '' !== $url ? $url : $postLink;
	}

	/**
	 * @param string $previewLink Core preview URL.
	 */
	public function filterPreviewLink( string $previewLink, mixed $post ): string {
		$resolved = $this->postFromMixed( $post );
		$url      = $this->studentViewUrl( $resolved );

		return '' !== $url ? $url : $previewLink;
	}

	/**
	 * @param array{0?: string, 1?: string} $permalink
	 * @return array{0?: string, 1?: string}
	 */
	public function filterSamplePermalink( array $permalink, mixed $postId ): array {
		$post = $this->postFromMixed( $postId );
		$url  = $this->studentViewUrl( $post );

		if ( '' !== $url ) {
			$permalink[0] = $url;
		}

		return $permalink;
	}

	public function filterRestPost( \WP_REST_Response $response, \WP_Post $post ): \WP_REST_Response {
		$url = $this->studentViewUrl( $post );

		if ( '' === $url ) {
			return $response;
		}

		$data                         = $response->get_data();
		$data['link']                 = $url;
		$data['permalink_template']   = $url;
		if ( '' !== (string) $post->post_name ) {
			$data['generated_slug'] = (string) $post->post_name;
		}
		$response->set_data( $data );

		return $response;
	}

	public function redirectSingularToStudentView(): void {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post || ! PostTypes::isContentType( $post->post_type ) ) {
			return;
		}

		$url = $this->studentViewUrl( $post );

		if ( '' === $url ) {
			return;
		}

		$current = $this->currentRequestUrl();

		if ( '' !== $current && untrailingslashit( $current ) === untrailingslashit( $url ) ) {
			return;
		}

		wp_safe_redirect( $url, 302 );
		exit;
	}

	private function studentViewUrl( ?\WP_Post $post ): string {
		if ( ! $post instanceof \WP_Post || $post->ID <= 0 || ! PostTypes::isContentType( $post->post_type ) ) {
			return '';
		}

		$base = $this->previewBaseUrl();

		if ( '' === $base ) {
			return '';
		}

		$courseId = (int) get_post_meta( $post->ID, PostTypes::META_COURSE_ID, true );
		$lessonId = (int) get_post_meta( $post->ID, PostTypes::META_LESSON_ID, true );

		if ( PostTypes::COURSE === $post->post_type ) {
			$courseUrl = $this->pages->getCourseUrl( $post->ID );

			return '' !== $courseUrl
				? $courseUrl
				: add_query_arg( PageSettings::COURSE_QUERY_ARG, (string) $post->ID, home_url( '/' ) );
		}

		if ( PostTypes::LESSON === $post->post_type ) {
			$args = array(
				'mint_preview' => 'lesson',
				'mint_lesson'  => (string) $post->ID,
			);
			if ( $courseId > 0 ) {
				$args['mintlms_course'] = (string) $courseId;
				$args['mint_course']    = (string) $courseId;
			}

			return add_query_arg( $args, $base );
		}

		if ( PostTypes::QUIZ === $post->post_type ) {
			$args = array(
				'mint_preview' => 'quiz',
				'mint_quiz'    => (string) $post->ID,
			);
			if ( $lessonId > 0 ) {
				$args['mint_lesson'] = (string) $lessonId;
			}
			if ( $courseId > 0 ) {
				$args['mintlms_course'] = (string) $courseId;
				$args['mint_course']    = (string) $courseId;
			}

			return add_query_arg( $args, $base );
		}

		$quizId = ( new QuestionLinkResolver() )->findQuizId( $post->ID );
		$args   = array(
			'mint_preview'  => 'quiz',
			'mint_question' => (string) $post->ID,
		);
		if ( $quizId > 0 ) {
			$args['mint_quiz'] = (string) $quizId;
			$quizLesson        = (int) get_post_meta( $quizId, PostTypes::META_LESSON_ID, true );
			$quizCourse        = (int) get_post_meta( $quizId, PostTypes::META_COURSE_ID, true );
			if ( $quizLesson > 0 ) {
				$args['mint_lesson'] = (string) $quizLesson;
			}
			if ( $quizCourse > 0 ) {
				$args['mintlms_course'] = (string) $quizCourse;
				$args['mint_course']    = (string) $quizCourse;
			}
		}

		return add_query_arg( $args, $base );
	}

	private function previewBaseUrl(): string {
		$player = $this->pages->getPlayerUrl();
		if ( '' !== $player && home_url( '/' ) !== $player ) {
			return $player;
		}

		$catalog = $this->pages->getCatalogUrl();
		if ( '' !== $catalog && home_url( '/' ) !== $catalog ) {
			return $catalog;
		}

		if ( '' !== $player ) {
			return $player;
		}

		if ( '' !== $catalog ) {
			return $catalog;
		}

		return home_url( '/' );
	}

	private function postFromMixed( mixed $post ): ?\WP_Post {
		if ( $post instanceof \WP_Post ) {
			return $post;
		}

		if ( is_numeric( $post ) ) {
			$loaded = get_post( (int) $post );

			return $loaded instanceof \WP_Post ? $loaded : null;
		}

		return null;
	}

	private function currentRequestUrl(): string {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';

		if ( '' === $host ) {
			return '';
		}

		$scheme = is_ssl() ? 'https://' : 'http://';

		return $scheme . $host . $uri;
	}
}
