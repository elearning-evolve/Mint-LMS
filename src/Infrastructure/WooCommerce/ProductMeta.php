<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

use MintLMS\Plugin;

/**
 * LearnDash-style product ↔ course association UI and helpers.
 */
final class ProductMeta {

	public function register(): void {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'renderField' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'saveField' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'renderVariationField' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'saveVariationField' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
	}

	public function enqueueAdminAssets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );
	}

	public function renderField(): void {
		$productId = (int) get_the_ID();
		$courseId  = $this->getCourseIdForProduct( $productId );
		$options   = $this->courseOptions( $courseId );

		echo '<div class="options_group show_if_simple show_if_variable show_if_' . esc_attr( CourseProductType::TYPE ) . '">';
		echo '<p class="form-field">';
		echo '<label for="' . esc_attr( EnrollmentBridge::META_COURSE_ID ) . '">' . esc_html__( 'Mint LMS course', 'mint-lms' ) . '</label>';
		echo '<select class="wc-enhanced-select mintlms-course-select" style="width:50%" id="' . esc_attr( EnrollmentBridge::META_COURSE_ID ) . '" name="' . esc_attr( EnrollmentBridge::META_COURSE_ID ) . '" data-placeholder="' . esc_attr__( 'Search courses…', 'mint-lms' ) . '">';
		echo '<option value="">' . esc_html__( '— Select a course —', 'mint-lms' ) . '</option>';

		foreach ( $options as $id => $label ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $id,
				selected( $courseId, (int) $id, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
		echo '<span class="description">' . esc_html__( 'Customers are enrolled in this course when the order is paid (processing/completed).', 'mint-lms' ) . '</span>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * @param int      $loop          Variation loop index.
	 * @param array    $variationData Variation data.
	 * @param \WP_Post $variation     Variation post.
	 */
	public function renderVariationField( int $loop, array $variationData, \WP_Post $variation ): void {
		$courseId = $this->getCourseIdForProduct( (int) $variation->ID );
		$options  = $this->courseOptions( $courseId );
		$fieldId  = EnrollmentBridge::META_COURSE_ID . "_{$loop}";
		$fieldName = EnrollmentBridge::META_COURSE_ID . "[{$loop}]";

		echo '<div class="form-row form-row-full">';
		echo '<label for="' . esc_attr( $fieldId ) . '">' . esc_html__( 'Mint LMS course', 'mint-lms' ) . '</label>';
		echo '<select class="wc-enhanced-select" style="width:100%" id="' . esc_attr( $fieldId ) . '" name="' . esc_attr( $fieldName ) . '">';
		echo '<option value="">' . esc_html__( '— Use parent product course —', 'mint-lms' ) . '</option>';

		foreach ( $options as $id => $label ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $id,
				selected( $courseId, (int) $id, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
		echo '</div>';
	}

	public function saveField( int $productId ): void {
		if ( ! current_user_can( 'edit_post', $productId ) ) {
			return;
		}

		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		$courseId = isset( $_POST[ EnrollmentBridge::META_COURSE_ID ] )
			? absint( wp_unslash( (string) $_POST[ EnrollmentBridge::META_COURSE_ID ] ) )
			: 0;

		$this->updateCourseId( $productId, $courseId );
	}

	public function saveVariationField( int $variationId, int $loop ): void {
		if ( ! current_user_can( 'edit_post', $variationId ) ) {
			return;
		}

		if ( ! empty( $_REQUEST['security'] ) ) {
			check_ajax_referer( 'save-variations', 'security' );
		} elseif ( ! empty( $_POST['woocommerce_meta_nonce'] ) ) {
			check_admin_referer( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
		} else {
			return;
		}

		$posted = isset( $_POST[ EnrollmentBridge::META_COURSE_ID ] )
			&& is_array( $_POST[ EnrollmentBridge::META_COURSE_ID ] )
			? wp_unslash( $_POST[ EnrollmentBridge::META_COURSE_ID ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Absint below.
			: array();

		$courseId = isset( $posted[ $loop ] ) ? absint( (string) $posted[ $loop ] ) : 0;

		$this->updateCourseId( $variationId, $courseId );
	}

	public function getCourseIdForProduct( int $productId ): int {
		if ( $productId <= 0 ) {
			return 0;
		}

		$product = wc_get_product( $productId );

		if ( ! $product instanceof \WC_Product ) {
			return 0;
		}

		$courseId = (int) $product->get_meta( EnrollmentBridge::META_COURSE_ID, true );

		if ( $courseId > 0 ) {
			return $courseId;
		}

		if ( $product->is_type( 'variation' ) ) {
			$parentId = $product->get_parent_id();

			if ( $parentId > 0 ) {
				$parent = wc_get_product( $parentId );

				if ( $parent instanceof \WC_Product ) {
					return (int) $parent->get_meta( EnrollmentBridge::META_COURSE_ID, true );
				}
			}
		}

		return 0;
	}

	/**
	 * @return list<int>
	 */
	public function getCourseIdsForProduct( int $productId ): array {
		$courseId = $this->getCourseIdForProduct( $productId );

		return $courseId > 0 ? array( $courseId ) : array();
	}

	public function linkCourse( int $productId, int $courseId ): void {
		$this->updateCourseId( $productId, $courseId );
		wp_set_object_terms( $productId, CourseProductType::TYPE, 'product_type' );
	}

	private function updateCourseId( int $productId, int $courseId ): void {
		$product = wc_get_product( $productId );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( $courseId > 0 ) {
			$product->update_meta_data( EnrollmentBridge::META_COURSE_ID, $courseId );
		} else {
			$product->delete_meta_data( EnrollmentBridge::META_COURSE_ID );
		}

		$product->save();
	}

	/**
	 * @return array<int, string>
	 */
	private function courseOptions( int $selectedId ): array {
		$options = array();

		try {
			$service = Plugin::courseService();
			$list    = $service->list( 1, 100, get_current_user_id(), null, null );
			foreach ( $list->courses as $course ) {
				$options[ $course->id ] = sprintf( '#%d — %s', $course->id, $course->title );
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Fall through to selected-only options.
		}

		if ( $selectedId > 0 && ! isset( $options[ $selectedId ] ) ) {
			$options[ $selectedId ] = sprintf( '#%d', $selectedId );
		}

		return $options;
	}
}
