<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\WooCommerce;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Enrollment\Dto\EnrollmentDto;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Plugin;

final class EnrollmentBridge {

	public const META_COURSE_ID = '_mintlms_course_id';

	public const ORDER_META_ENROLLMENTS = '_mintlms_wc_enrollments';

	public const ORDER_META_PROCESSED = '_mintlms_wc_enrollment_processed';

	/**
	 * Enroll a customer from a paid order. Uses purchase proof (order ID), not a staff actor —
	 * so the first/only administrator can buy their own Paid course successfully.
	 *
	 * @return EnrollmentDto|null Null when enrollment could not be created or returned.
	 */
	public function enrollUser( int $userId, int $courseId, int $orderId, int $productId ): ?EnrollmentDto {
		if ( $userId <= 0 || $courseId <= 0 || $orderId <= 0 ) {
			return null;
		}

		/**
		 * Fires before Mint LMS enrollment is created for a WooCommerce purchase.
		 *
		 * @param int $userId   Customer user ID.
		 * @param int $courseId Mint LMS course ID.
		 * @param int $orderId  WooCommerce order ID.
		 * @param int $productId WooCommerce product ID.
		 */
		do_action( 'mintlms_wc_before_enroll', $userId, $courseId, $orderId, $productId );

		try {
			$enrollment = Plugin::enrollmentService()->enrollFromPurchase( $userId, $courseId, $orderId );

			/**
			 * Fires after Mint LMS enrollment is created (or confirmed active) for a WooCommerce purchase.
			 *
			 * @param EnrollmentDto $enrollment Created or existing active enrollment.
			 * @param int           $orderId    WooCommerce order ID.
			 * @param int           $productId  WooCommerce product ID.
			 */
			do_action( 'mintlms_wc_after_enroll', $enrollment, $orderId, $productId );

			return $enrollment;
		} catch ( ValidationException $exception ) {
			$this->log(
				'error',
				sprintf(
					'Validation error enrolling user %1$d in course %2$d (order #%3$d): %4$s',
					$userId,
					$courseId,
					$orderId,
					$exception->getMessage()
				)
			);
		} catch ( NotFoundException $exception ) {
			$this->log(
				'error',
				sprintf(
					'Course %1$d not found for order #%2$d: %3$s',
					$courseId,
					$orderId,
					$exception->getMessage()
				)
			);
		} catch ( ForbiddenException $exception ) {
			$this->log(
				'error',
				sprintf(
					'Forbidden enrolling user %1$d in course %2$d (order #%3$d): %4$s',
					$userId,
					$courseId,
					$orderId,
					$exception->getMessage()
				)
			);
		} catch ( \Throwable $exception ) {
			$this->log(
				'error',
				sprintf(
					'Unexpected error enrolling user %1$d in course %2$d (order #%3$d): %4$s',
					$userId,
					$courseId,
					$orderId,
					$exception->getMessage()
				)
			);
		}

		return null;
	}

	public function cancelEnrollment( int $userId, int $courseId, int $orderId ): bool {
		if ( $userId <= 0 || $courseId <= 0 ) {
			return false;
		}

		/**
		 * Filter whether WooCommerce refund/cancel should revoke Mint LMS enrollment.
		 *
		 * @param bool $cancel   Whether to cancel enrollment.
		 * @param int  $userId   Customer user ID.
		 * @param int  $courseId Mint LMS course ID.
		 * @param int  $orderId  WooCommerce order ID.
		 */
		$shouldCancel = (bool) apply_filters( 'mintlms_wc_cancel_enrollment_on_refund', $this->shouldCancelOnRefund(), $userId, $courseId, $orderId );

		if ( ! $shouldCancel ) {
			return false;
		}

		/**
		 * Fires before Mint LMS enrollment is cancelled for a WooCommerce refund/cancel.
		 *
		 * @param int $userId   Customer user ID.
		 * @param int $courseId Mint LMS course ID.
		 * @param int $orderId  WooCommerce order ID.
		 */
		do_action( 'mintlms_wc_before_cancel_enrollment', $userId, $courseId, $orderId );

		try {
			// Customer is a valid cancel actor for their own enrollment — no staff actor required.
			$cancelled = Plugin::enrollmentService()->cancelByUserAndCourse( $userId, $courseId, $userId );

			if ( $cancelled ) {
				/**
				 * Fires after Mint LMS enrollment is cancelled for a WooCommerce refund/cancel.
				 *
				 * @param int $userId   Customer user ID.
				 * @param int $courseId Mint LMS course ID.
				 * @param int $orderId  WooCommerce order ID.
				 */
				do_action( 'mintlms_wc_after_cancel_enrollment', $userId, $courseId, $orderId );
			}

			return $cancelled;
		} catch ( NotFoundException | ForbiddenException $exception ) {
			$this->log(
				'error',
				sprintf(
					'Could not cancel enrollment for user %1$d in course %2$d (order #%3$d): %4$s',
					$userId,
					$courseId,
					$orderId,
					$exception->getMessage()
				)
			);
		} catch ( \Throwable $exception ) {
			$this->log(
				'error',
				sprintf(
					'Unexpected error cancelling enrollment for user %1$d in course %2$d (order #%3$d): %4$s',
					$userId,
					$courseId,
					$orderId,
					$exception->getMessage()
				)
			);
		}

		return false;
	}

	public function shouldCancelOnRefund(): bool {
		/**
		 * Filter the default cancel-on-refund option value.
		 *
		 * @param bool $default Default option value.
		 */
		$default = (bool) apply_filters( 'mintlms_wc_default_cancel_on_refund', true );

		return 'yes' === get_option( 'mintlms_wc_cancel_on_refund', $default ? 'yes' : 'no' );
	}

	/**
	 * Optional staff actor for legacy filters / tooling. Purchase enroll no longer requires this.
	 */
	public function resolveActorUserId(): int {
		/**
		 * Filter the WordPress user ID used as the Mint LMS enrollment actor.
		 *
		 * Must have the `enroll_mintlms_students` or `manage_mintlms` capability.
		 *
		 * @param int $actorId Actor user ID. 0 to auto-detect.
		 */
		$actorId = (int) apply_filters( 'mintlms_wc_enrollment_actor_id', 0 );

		if ( $actorId > 0 && $this->userCanEnroll( $actorId ) ) {
			return $actorId;
		}

		$admins = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'fields'  => 'ID',
			)
		);

		if ( isset( $admins[0] ) && $this->userCanEnroll( (int) $admins[0] ) ) {
			return (int) $admins[0];
		}

		$managers = get_users(
			array(
				'role'    => 'mintlms_manager',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'fields'  => 'ID',
			)
		);

		if ( isset( $managers[0] ) && $this->userCanEnroll( (int) $managers[0] ) ) {
			return (int) $managers[0];
		}

		$instructors = get_users(
			array(
				'role'    => 'mintlms_instructor',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'fields'  => 'ID',
			)
		);

		if ( isset( $instructors[0] ) && $this->userCanEnroll( (int) $instructors[0] ) ) {
			return (int) $instructors[0];
		}

		return 0;
	}

	private function userCanEnroll( int $userId ): bool {
		return user_can( $userId, 'enroll_mintlms_students' ) || user_can( $userId, 'manage_mintlms' );
	}

	private function log( string $level, string $message ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->log( $level, $message, array( 'source' => 'mint-lms' ) );
	}
}
