<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Event\EnrollmentCreated;

/**
 * Grants the Student role on enrollment. Does not replace Subscriber/Customer
 * and does not grant course access by itself.
 */
final class StudentRoleAssigner {

	public function register(): void {
		add_action( 'mintlms_enrollment_created', array( $this, 'onEnrollmentCreated' ) );
	}

	public function onEnrollmentCreated( EnrollmentCreated $event ): void {
		$this->grant( $event->userId->toInt() );
	}

	public function grant( int $userId ): void {
		if ( $userId <= 0 ) {
			return;
		}

		$user = get_userdata( $userId );
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		if ( user_can( $user, 'manage_options' ) || user_can( $user, Roles::CAP_EDIT ) ) {
			return;
		}

		if ( in_array( Roles::STUDENT, (array) $user->roles, true ) ) {
			return;
		}

		$user->add_role( Roles::STUDENT );
	}
}
