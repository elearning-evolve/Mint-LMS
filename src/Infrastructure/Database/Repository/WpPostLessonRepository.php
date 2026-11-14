<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Infrastructure\PostType\PostTypes;

final class WpPostLessonRepository implements LessonRepositoryInterface {

	public function findById( int $id ): ?Lesson {
		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post || PostTypes::LESSON !== $post->post_type ) {
			return null;
		}

		return $this->mapPostToLesson( $post );
	}

	public function findBySlugAndCourseId( string $slug, int $courseId ): ?Lesson {
		$query = new \WP_Query(
			array(
				'post_type'              => PostTypes::LESSON,
				'name'                   => $slug,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => PostTypes::META_COURSE_ID,
						'value'   => $courseId,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		if ( array() === $query->posts || ! $query->posts[0] instanceof \WP_Post ) {
			return null;
		}

		return $this->mapPostToLesson( $query->posts[0] );
	}

	public function findBySectionId( int $sectionId, bool $publishedOnly = false ): array {
		return $this->queryLessonsByMeta(
			array(
				array(
					'key'     => PostTypes::META_SECTION_ID,
					'value'   => $sectionId,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
			$publishedOnly
		);
	}

	public function findByCourseIdAndSectionId( int $courseId, int $sectionId, bool $publishedOnly = false ): array {
		return $this->queryLessonsByMeta(
			array(
				array(
					'key'     => PostTypes::META_COURSE_ID,
					'value'   => $courseId,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => PostTypes::META_SECTION_ID,
					'value'   => $sectionId,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
			$publishedOnly
		);
	}

	/**
	 * @param list<array<string, mixed>> $metaQuery
	 * @return list<Lesson>
	 */
	private function queryLessonsByMeta( array $metaQuery, bool $publishedOnly ): array {
		$query = new \WP_Query(
			array(
				'post_type'              => PostTypes::LESSON,
				'post_status'            => $publishedOnly
					? 'publish'
					: array( 'publish', 'draft', 'private', PostTypes::STATUS_ARCHIVED ),
				'posts_per_page'         => -1,
				'orderby'                => 'meta_value_num',
				'meta_key'               => PostTypes::META_SORT_ORDER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => $metaQuery, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		$lessons = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$lessons[] = $this->mapPostToLesson( $post );
			}
		}

		return $lessons;
	}

	public function save( Lesson $lesson ): Lesson {
		$createdGmt  = $lesson->createdAt->format( 'Y-m-d H:i:s' );
		$modifiedGmt = $lesson->updatedAt->format( 'Y-m-d H:i:s' );

		$postarr = array(
			'post_title'        => $lesson->title,
			'post_name'         => $lesson->slug,
			'post_content'      => $lesson->content,
			'post_type'         => PostTypes::LESSON,
			'post_date'         => get_date_from_gmt( $createdGmt ),
			'post_date_gmt'     => $createdGmt,
			'post_modified'     => get_date_from_gmt( $modifiedGmt ),
			'post_modified_gmt' => $modifiedGmt,
		);

		if ( 0 === $lesson->id ) {
			$postarr['post_status'] = 'publish';
			$postId                 = wp_insert_post( $postarr, true );
		} else {
			$postarr['ID'] = $lesson->id;
			// Preserve draft/live/hidden on updates — never force publish on save.
			$postId = wp_update_post( $postarr, true );
		}

		if ( is_wp_error( $postId ) ) {
			throw new \RuntimeException( esc_html( $postId->get_error_message() ) );
		}

		$postId = (int) $postId;

		if ( $postId <= 0 ) {
			throw new \RuntimeException( 0 === $lesson->id ? 'Failed to insert lesson.' : 'Failed to update lesson.' );
		}

		$this->persistLessonMeta( $postId, $lesson );

		$saved = $this->findById( $postId );

		if ( null === $saved ) {
			throw new \RuntimeException( 'Failed to load lesson after save.' );
		}

		return $saved;
	}

	public function delete( int $id ): bool {
		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post || PostTypes::LESSON !== $post->post_type ) {
			return false;
		}

		$result = wp_delete_post( $id, true );

		return $result instanceof \WP_Post;
	}

	public function deleteBySectionId( int $sectionId ): void {
		// section_id 0 is the ungrouped/library bucket — never mass-delete it.
		if ( $sectionId <= 0 ) {
			return;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => PostTypes::LESSON,
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => PostTypes::META_SECTION_ID,
						'value'   => $sectionId,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		foreach ( $query->posts as $postId ) {
			wp_delete_post( (int) $postId, true );
		}
	}

	public function reorder( int $sectionId, array $lessonIds ): void {
		foreach ( $lessonIds as $index => $lessonId ) {
			$post = get_post( (int) $lessonId );

			if ( ! $post instanceof \WP_Post || PostTypes::LESSON !== $post->post_type ) {
				continue;
			}

			$storedSectionId = (int) get_post_meta( $post->ID, PostTypes::META_SECTION_ID, true );

			if ( $storedSectionId !== $sectionId ) {
				continue;
			}

			update_post_meta( $post->ID, PostTypes::META_SORT_ORDER, (int) $index );
		}
	}

	public function nextSortOrder( int $sectionId, ?int $courseId = null ): int {
		$metaQuery = array(
			array(
				'key'     => PostTypes::META_SECTION_ID,
				'value'   => $sectionId,
				'compare' => '=',
				'type'    => 'NUMERIC',
			),
		);

		if ( null !== $courseId ) {
			$metaQuery[] = array(
				'key'     => PostTypes::META_COURSE_ID,
				'value'   => $courseId,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		$query = new \WP_Query(
			array(
				'post_type'              => PostTypes::LESSON,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'orderby'                => 'meta_value_num',
				'meta_key'               => PostTypes::META_SORT_ORDER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'                  => 'DESC',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => $metaQuery, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		if ( array() === $query->posts ) {
			return 0;
		}

		$max = get_post_meta( (int) $query->posts[0], PostTypes::META_SORT_ORDER, true );

		if ( '' === $max || false === $max || null === $max ) {
			return 0;
		}

		return (int) $max + 1;
	}

	private function persistLessonMeta( int $postId, Lesson $lesson ): void {
		update_post_meta( $postId, PostTypes::META_SECTION_ID, $lesson->sectionId );
		update_post_meta( $postId, PostTypes::META_COURSE_ID, $lesson->courseId );
		update_post_meta( $postId, PostTypes::META_SORT_ORDER, $lesson->sortOrder );
		update_post_meta( $postId, PostTypes::META_IS_PREVIEW, $lesson->isPreview ? '1' : '0' );

		if ( null !== $lesson->videoUrl && '' !== $lesson->videoUrl ) {
			update_post_meta( $postId, PostTypes::META_VIDEO_URL, $lesson->videoUrl );
		} else {
			delete_post_meta( $postId, PostTypes::META_VIDEO_URL );
		}

		if ( null !== $lesson->attachmentId && $lesson->attachmentId > 0 ) {
			update_post_meta( $postId, PostTypes::META_ATTACHMENT_ID, $lesson->attachmentId );
		} else {
			delete_post_meta( $postId, PostTypes::META_ATTACHMENT_ID );
		}

		if ( null !== $lesson->availableAfterDays ) {
			update_post_meta( $postId, PostTypes::META_AVAILABLE_AFTER_DAYS, $lesson->availableAfterDays );
		} else {
			delete_post_meta( $postId, PostTypes::META_AVAILABLE_AFTER_DAYS );
		}

		if ( null !== $lesson->featuredImageId && $lesson->featuredImageId > 0 ) {
			set_post_thumbnail( $postId, $lesson->featuredImageId );
		} else {
			delete_post_thumbnail( $postId );
		}
	}

	private function mapPostToLesson( \WP_Post $post ): Lesson {
		$videoRaw = get_post_meta( $post->ID, PostTypes::META_VIDEO_URL, true );
		$videoUrl = ( is_string( $videoRaw ) && '' !== $videoRaw ) ? $videoRaw : null;

		$attachmentRaw = get_post_meta( $post->ID, PostTypes::META_ATTACHMENT_ID, true );
		$attachmentId  = $this->nullablePositiveInt( $attachmentRaw );

		$thumbnailId     = (int) get_post_thumbnail_id( $post );
		$featuredImageId = $thumbnailId > 0 ? $thumbnailId : null;

		$isPreviewRaw = get_post_meta( $post->ID, PostTypes::META_IS_PREVIEW, true );
		$isPreview    = '1' === (string) $isPreviewRaw || 1 === $isPreviewRaw || true === $isPreviewRaw;

		$availableAfterDays = $this->nullableInt(
			get_post_meta( $post->ID, PostTypes::META_AVAILABLE_AFTER_DAYS, true )
		);

		$createdGmt  = $this->gmtOrFallback( $post->post_date_gmt, $post->post_date );
		$modifiedGmt = $this->gmtOrFallback( $post->post_modified_gmt, $post->post_modified );

		return new Lesson(
			(int) $post->ID,
			(int) get_post_meta( $post->ID, PostTypes::META_SECTION_ID, true ),
			(int) get_post_meta( $post->ID, PostTypes::META_COURSE_ID, true ),
			(string) $post->post_title,
			(string) $post->post_name,
			(string) $post->post_content,
			$videoUrl,
			$attachmentId,
			$isPreview,
			$availableAfterDays,
			(int) get_post_meta( $post->ID, PostTypes::META_SORT_ORDER, true ),
			new \DateTimeImmutable( $createdGmt ),
			new \DateTimeImmutable( $modifiedGmt ),
			$featuredImageId,
		);
	}

	private function nullableInt( mixed $value ): ?int {
		if ( null === $value || false === $value || '' === $value ) {
			return null;
		}

		return (int) $value;
	}

	private function nullablePositiveInt( mixed $value ): ?int {
		$int = $this->nullableInt( $value );

		if ( null === $int || $int <= 0 ) {
			return null;
		}

		return $int;
	}

	private function gmtOrFallback( string $gmt, string $local ): string {
		if ( '' !== $gmt && '0000-00-00 00:00:00' !== $gmt ) {
			return $gmt;
		}

		return $local;
	}
}
