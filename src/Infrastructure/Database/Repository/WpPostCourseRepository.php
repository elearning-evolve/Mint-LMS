<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Database\Repository;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseSettings;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Infrastructure\PostType\PostTypes;

final class WpPostCourseRepository implements CourseRepositoryInterface {

	public function findById( int $id ): ?Course {
		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post || PostTypes::COURSE !== $post->post_type ) {
			return null;
		}

		return $this->mapPostToCourse( $post );
	}

	public function findBySlug( string $slug ): ?Course {
		$query = new \WP_Query(
			array(
				'post_type'              => PostTypes::COURSE,
				'name'                   => $slug,
				'post_status'            => array( 'draft', 'publish', PostTypes::STATUS_ARCHIVED, 'trash' ),
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		if ( array() === $query->posts || ! $query->posts[0] instanceof \WP_Post ) {
			return null;
		}

		return $this->mapPostToCourse( $query->posts[0] );
	}

	public function save( Course $course ): Course {
		$createdGmt  = $course->createdAt->format( 'Y-m-d H:i:s' );
		$modifiedGmt = $course->updatedAt->format( 'Y-m-d H:i:s' );

		$postarr = array(
			'post_title'        => $course->title,
			'post_name'         => $course->slug,
			'post_content'      => $course->description,
			'post_status'       => $this->statusToPostStatus( $course->status ),
			'post_type'         => PostTypes::COURSE,
			'post_author'       => $course->authorId,
			'post_date'         => get_date_from_gmt( $createdGmt ),
			'post_date_gmt'     => $createdGmt,
			'post_modified'     => get_date_from_gmt( $modifiedGmt ),
			'post_modified_gmt' => $modifiedGmt,
		);

		if ( 0 === $course->id ) {
			$postId = wp_insert_post( $postarr, true );
		} else {
			$postarr['ID'] = $course->id;
			$postId        = wp_update_post( $postarr, true );
		}

		if ( is_wp_error( $postId ) ) {
			throw new \RuntimeException( esc_html( $postId->get_error_message() ) );
		}

		$postId = (int) $postId;

		if ( $postId <= 0 ) {
			throw new \RuntimeException( 0 === $course->id ? 'Failed to insert course.' : 'Failed to update course.' );
		}

		update_post_meta( $postId, PostTypes::META_ENROLLMENT_TYPE, $course->enrollmentType->value );

		$settingsJson = wp_json_encode( $course->settings->toArray() );
		update_post_meta(
			$postId,
			PostTypes::META_COURSE_SETTINGS,
			is_string( $settingsJson ) ? $settingsJson : '{}'
		);

		if ( null !== $course->featuredImageId && $course->featuredImageId > 0 ) {
			set_post_thumbnail( $postId, $course->featuredImageId );
		} else {
			delete_post_thumbnail( $postId );
		}

		$saved = $this->findById( $postId );

		if ( null === $saved ) {
			throw new \RuntimeException( 'Failed to load course after save.' );
		}

		return $saved;
	}

	public function delete( int $id ): bool {
		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post || PostTypes::COURSE !== $post->post_type ) {
			return false;
		}

		$result = wp_delete_post( $id, true );

		return $result instanceof \WP_Post;
	}

	public function list( int $page, int $perPage, ?int $authorId = null, ?CourseStatus $status = null, ?string $search = null ): array {
		$args = array(
			'post_type'              => PostTypes::COURSE,
			'posts_per_page'         => $perPage,
			'paged'                  => max( 1, $page ),
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		);

		if ( null !== $status ) {
			$args['post_status'] = $this->statusToPostStatus( $status );
		} else {
			$args['post_status'] = array( 'draft', 'publish', PostTypes::STATUS_ARCHIVED );
		}

		if ( null !== $authorId ) {
			$args['author'] = $authorId;
		}

		if ( null !== $search && '' !== trim( $search ) ) {
			$args['s'] = trim( $search );
		}

		$query   = new \WP_Query( $args );
		$courses = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$courses[] = $this->mapPostToCourse( $post );
			}
		}

		return array(
			'courses' => $courses,
			'total'   => (int) $query->found_posts,
		);
	}

	private function mapPostToCourse( \WP_Post $post ): Course {
		$thumbnailId     = (int) get_post_thumbnail_id( $post );
		$featuredImageId = $thumbnailId > 0 ? $thumbnailId : null;

		$enrollmentRaw  = get_post_meta( $post->ID, PostTypes::META_ENROLLMENT_TYPE, true );
		$enrollmentType = EnrollmentType::tryFrom( is_string( $enrollmentRaw ) ? $enrollmentRaw : '' )
			?? EnrollmentType::Free;

		$settingsRaw = get_post_meta( $post->ID, PostTypes::META_COURSE_SETTINGS, true );
		$settings    = CourseSettings::defaults();

		if ( is_string( $settingsRaw ) && '' !== $settingsRaw ) {
			$decoded  = json_decode( $settingsRaw, true );
			$settings = CourseSettings::fromArray( is_array( $decoded ) ? $decoded : null );
		} elseif ( is_array( $settingsRaw ) ) {
			$settings = CourseSettings::fromArray( $settingsRaw );
		}

		$createdGmt  = $this->gmtOrFallback( $post->post_date_gmt, $post->post_date );
		$modifiedGmt = $this->gmtOrFallback( $post->post_modified_gmt, $post->post_modified );

		return new Course(
			(int) $post->ID,
			(string) $post->post_title,
			(string) $post->post_name,
			(string) $post->post_content,
			$featuredImageId,
			$this->postStatusToCourseStatus( (string) $post->post_status ),
			$enrollmentType,
			(int) $post->post_author,
			new \DateTimeImmutable( $createdGmt ),
			new \DateTimeImmutable( $modifiedGmt ),
			$settings,
		);
	}

	private function statusToPostStatus( CourseStatus $status ): string {
		return match ( $status ) {
			CourseStatus::Draft     => 'draft',
			CourseStatus::Published => 'publish',
			CourseStatus::Archived  => PostTypes::STATUS_ARCHIVED,
			CourseStatus::Trashed   => 'trash',
		};
	}

	private function postStatusToCourseStatus( string $postStatus ): CourseStatus {
		return match ( $postStatus ) {
			'publish'                   => CourseStatus::Published,
			'draft', 'auto-draft', 'pending', 'private' => CourseStatus::Draft,
			PostTypes::STATUS_ARCHIVED  => CourseStatus::Archived,
			'trash'                     => CourseStatus::Trashed,
			default                     => CourseStatus::Draft,
		};
	}

	private function gmtOrFallback( string $gmt, string $local ): string {
		if ( '' !== $gmt && '0000-00-00 00:00:00' !== $gmt ) {
			return $gmt;
		}

		return $local;
	}
}
