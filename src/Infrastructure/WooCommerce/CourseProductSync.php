<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\EnrollmentType;

/**
 * LearnDash-style bridge: create/link a WooCommerce product for a Mint course.
 */
final class CourseProductSync {

	public function __construct(
		private CourseRepositoryInterface $courses,
		private ProductMeta $productMeta,
	) {
	}

	public function findProductIdForCourse( int $courseId ): int {
		if ( $courseId <= 0 || ! function_exists( 'wc_get_products' ) ) {
			return 0;
		}

		$ids = wc_get_products(
			array(
				'limit'      => 1,
				'status'     => array( 'publish', 'draft', 'pending', 'private' ),
				'return'     => 'ids',
				'meta_key'   => EnrollmentBridge::META_COURSE_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Intentional product lookup by course.
				'meta_value' => (string) $courseId, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return isset( $ids[0] ) ? (int) $ids[0] : 0;
	}

	public function getPurchaseUrl( int $courseId ): string {
		$productId = $this->findProductIdForCourse( $courseId );

		if ( $productId <= 0 ) {
			return '';
		}

		$product = wc_get_product( $productId );

		if ( ! $product instanceof \WC_Product || 'publish' !== $product->get_status() ) {
			return '';
		}

		$url = $product->add_to_cart_url();

		return is_string( $url ) && '' !== $url ? $url : (string) get_permalink( $productId );
	}

	public function getProductPrice( int $productId ): string {
		$product = wc_get_product( $productId );

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		return (string) $product->get_regular_price( 'edit' );
	}

	/**
	 * Create or update a WooCommerce "Mint LMS Course" product for this course.
	 *
	 * @return array{productId:int,productUrl:string,editUrl:string,price:string}
	 */
	public function createOrUpdateForCourse( int $courseId, string $price, int $actorUserId ): array {
		$course = $this->courses->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$price      = $this->normalizePrice( $price );
		$existingId = $this->findProductIdForCourse( $courseId );

		if ( $existingId > 0 ) {
			$product = wc_get_product( $existingId );
		} else {
			$product = new CourseProduct();
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_virtual( true );
			$product->set_sold_individually( true );
		}

		if ( ! $product instanceof \WC_Product ) {
			throw new ValidationException( 'Unable to create WooCommerce product.' );
		}

		$product->set_name( $course->title );
		$product->set_description( $course->description );
		$product->set_short_description( wp_trim_words( wp_strip_all_tags( $course->description ), 40 ) );
		$product->set_regular_price( $price );
		$product->set_virtual( true );
		$product->set_sold_individually( true );

		if ( $course->featuredImageId ) {
			$product->set_image_id( $course->featuredImageId );
		}

		$productId = (int) $product->save();

		if ( $productId <= 0 ) {
			throw new ValidationException( 'WooCommerce product could not be saved.' );
		}

		$this->productMeta->linkCourse( $productId, $courseId );

		if ( EnrollmentType::Paid !== $course->enrollmentType ) {
			$this->courses->save(
				new Course(
					$course->id,
					$course->title,
					$course->slug,
					$course->description,
					$course->featuredImageId,
					$course->status,
					EnrollmentType::Paid,
					$course->authorId,
					$course->createdAt,
					new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) ),
				)
			);
		}

		unset( $actorUserId );

		return array(
			'productId'  => $productId,
			'productUrl' => (string) get_permalink( $productId ),
			'editUrl'    => (string) get_edit_post_link( $productId, 'raw' ),
			'price'      => $price,
		);
	}

	private function normalizePrice( string $price ): string {
		$price = trim( $price );

		if ( '' === $price ) {
			throw new ValidationException( 'Price is required.' );
		}

		if ( ! is_numeric( $price ) || (float) $price < 0 ) {
			throw new ValidationException( 'Price must be a non-negative number.' );
		}

		return wc_format_decimal( $price );
	}
}
