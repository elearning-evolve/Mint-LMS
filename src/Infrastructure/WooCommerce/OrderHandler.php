<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

final class OrderHandler {

	public function __construct(
		private EnrollmentBridge $bridge,
		private ProductMeta $productMeta,
	) {
	}

	public function register(): void {
		foreach ( $this->enrollStatuses() as $status ) {
			add_action( 'woocommerce_order_status_' . $status, array( $this, 'handleOrderCompleted' ), 10, 2 );
		}

		add_action( 'woocommerce_order_status_refunded', array( $this, 'handleOrderRevoked' ), 10, 2 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'handleOrderRevoked' ), 10, 2 );
	}

	/**
	 * @return list<string>
	 */
	public function enrollStatuses(): array {
		$raw = (string) get_option( 'mintlms_wc_enroll_statuses', 'processing,completed' );
		$parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

		/**
		 * Filter WooCommerce order statuses that trigger Mint LMS enrollment.
		 *
		 * @param list<string> $statuses Status slugs without the `wc-` prefix.
		 */
		$statuses = apply_filters( 'mintlms_wc_enroll_order_statuses', $parts );

		if ( ! is_array( $statuses ) || array() === $statuses ) {
			return array( 'processing', 'completed' );
		}

		return array_values( array_unique( array_map( 'strval', $statuses ) ) );
	}

	/**
	 * @param int             $orderId Order ID.
	 * @param \WC_Order|false $order   Order object when HPOS provides it.
	 */
	public function handleOrderCompleted( int $orderId, $order = false ): void {
		$order = $this->getOrder( $orderId, $order );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( 'yes' === $order->get_meta( EnrollmentBridge::ORDER_META_PROCESSED, true ) ) {
			return;
		}

		$userId = (int) $order->get_user_id();

		if ( $userId <= 0 ) {
			$this->log(
				'warning',
				sprintf(
					'Order #%d has no linked customer account; skipping Mint LMS enrollment.',
					$order->get_id()
				)
			);

			return;
		}

		$records = array();

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$productId   = (int) $item->get_product_id();
			$variationId = (int) $item->get_variation_id();
			$lookupId    = $variationId > 0 ? $variationId : $productId;

			foreach ( $this->productMeta->getCourseIdsForProduct( $lookupId ) as $courseId ) {
				$enrollment = $this->bridge->enrollUser( $userId, $courseId, $order->get_id(), $lookupId );

				if ( null === $enrollment ) {
					continue;
				}

				$records[] = array(
					'course_id'     => $courseId,
					'product_id'    => $lookupId,
					'enrollment_id' => $enrollment->id,
				);
			}
		}

		if ( array() !== $records ) {
			$order->update_meta_data( EnrollmentBridge::ORDER_META_ENROLLMENTS, $records );
		}

		$order->update_meta_data( EnrollmentBridge::ORDER_META_PROCESSED, 'yes' );
		$order->save();

		/**
		 * Fires after Mint LMS enrollments are processed for a paid order.
		 *
		 * @param \WC_Order $order   WooCommerce order.
		 * @param array     $records Enrollment records stored on the order.
		 */
		do_action( 'mintlms_wc_order_enrollments_processed', $order, $records );
	}

	/**
	 * @param int             $orderId Order ID.
	 * @param \WC_Order|false $order   Order object when HPOS provides it.
	 */
	public function handleOrderRevoked( int $orderId, $order = false ): void {
		$order = $this->getOrder( $orderId, $order );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( 'yes' !== $order->get_meta( EnrollmentBridge::ORDER_META_PROCESSED, true ) ) {
			return;
		}

		$userId = (int) $order->get_user_id();

		if ( $userId <= 0 ) {
			return;
		}

		$records = $order->get_meta( EnrollmentBridge::ORDER_META_ENROLLMENTS, true );

		if ( ! is_array( $records ) || array() === $records ) {
			return;
		}

		$cancelled = array();

		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$courseId = isset( $record['course_id'] ) ? (int) $record['course_id'] : 0;

			if ( $courseId <= 0 ) {
				continue;
			}

			if ( $this->bridge->cancelEnrollment( $userId, $courseId, $order->get_id() ) ) {
				$cancelled[] = $courseId;
			}
		}

		if ( array() !== $cancelled ) {
			/**
			 * Fires after Mint LMS enrollments are cancelled for a refunded/cancelled order.
			 *
			 * @param \WC_Order $order     WooCommerce order.
			 * @param int[]     $cancelled Course IDs whose enrollments were cancelled.
			 */
			do_action( 'mintlms_wc_order_enrollments_cancelled', $order, $cancelled );
		}
	}

	/**
	 * @param int             $orderId Order ID.
	 * @param \WC_Order|false $order   Order object.
	 */
	private function getOrder( int $orderId, $order ): ?\WC_Order {
		if ( $order instanceof \WC_Order ) {
			return $order;
		}

		$loaded = wc_get_order( $orderId );

		return $loaded instanceof \WC_Order ? $loaded : null;
	}

	private function log( string $level, string $message ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->log( $level, $message, array( 'source' => 'mint-lms' ) );
	}
}
