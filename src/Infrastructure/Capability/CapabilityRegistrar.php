<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

final class CapabilityRegistrar {

	private const CAPABILITIES = array(
		'manage_mintlms',
		'edit_mintlms_courses',
		'edit_others_mintlms_courses',
		'enroll_mintlms_students',
		'view_mintlms_reports',
	);

	public function register(): void {
		$this->ensureInstructorRole();
		$this->assignAdministratorCaps();
		$this->assignInstructorCaps();
	}

	private function ensureInstructorRole(): void {
		$role = get_role( 'mintlms_instructor' );

		if ( null !== $role ) {
			return;
		}

		add_role(
			'mintlms_instructor',
			__( 'Instructor', 'mint-lms' ),
			array(
				'read' => true,
			)
		);
	}

	private function assignAdministratorCaps(): void {
		$role = get_role( 'administrator' );

		if ( null === $role ) {
			return;
		}

		foreach ( self::CAPABILITIES as $cap ) {
			$role->add_cap( $cap );
		}
	}

	private function assignInstructorCaps(): void {
		$role = get_role( 'mintlms_instructor' );

		if ( null === $role ) {
			return;
		}

		$instructorCaps = array(
			'edit_mintlms_courses',
			'enroll_mintlms_students',
			'view_mintlms_reports',
		);

		foreach ( $instructorCaps as $cap ) {
			$role->add_cap( $cap );
		}
	}
}
