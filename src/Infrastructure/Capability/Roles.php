<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

defined( 'ABSPATH' ) || exit;

/**
 * Canonical Mint LMS WordPress roles and capabilities.
 */
final class Roles {

	public const ADMINISTRATOR = 'administrator';
	public const MANAGER       = 'mintlms_manager';
	public const INSTRUCTOR    = 'mintlms_instructor';
	public const STUDENT       = 'mintlms_student';

	public const CAP_MANAGE       = 'manage_mintlms';
	public const CAP_EDIT         = 'edit_mintlms_courses';
	public const CAP_EDIT_OTHERS  = 'edit_others_mintlms_courses';
	public const CAP_ENROLL       = 'enroll_mintlms_students';
	public const CAP_REPORTS      = 'view_mintlms_reports';
	public const CAP_TAKE         = 'take_mintlms_courses';

	/**
	 * @return list<string>
	 */
	public static function customRoleSlugs(): array {
		return array( self::MANAGER, self::INSTRUCTOR, self::STUDENT );
	}

	/**
	 * Unique labels so Users → Role is not confused with Lifter/LearnDash.
	 * WordPress shows this name, never the slug.
	 */
	public static function displayName( string $slug ): string {
		return match ( $slug ) {
			self::MANAGER    => __( 'Mint LMS Manager', 'mint-lms' ),
			self::INSTRUCTOR => __( 'Mint LMS Instructor', 'mint-lms' ),
			self::STUDENT    => __( 'Mint LMS Student', 'mint-lms' ),
			default          => $slug,
		};
	}

	/**
	 * @return list<string>
	 */
	public static function mintCapabilities(): array {
		return array(
			self::CAP_MANAGE,
			self::CAP_EDIT,
			self::CAP_EDIT_OTHERS,
			self::CAP_ENROLL,
			self::CAP_REPORTS,
			self::CAP_TAKE,
		);
	}

	/**
	 * @return array<string, bool>
	 */
	public static function administratorCaps(): array {
		return array_fill_keys( self::mintCapabilities(), true );
	}

	/**
	 * @return array<string, bool>
	 */
	public static function managerCaps(): array {
		return array(
			'read'                => true,
			'upload_files'        => true,
			'list_users'          => true,
			self::CAP_MANAGE      => true,
			self::CAP_EDIT        => true,
			self::CAP_EDIT_OTHERS => true,
			self::CAP_ENROLL      => true,
			self::CAP_REPORTS     => true,
			self::CAP_TAKE        => true,
		);
	}

	/**
	 * @return array<string, bool>
	 */
	public static function instructorCaps(): array {
		return array(
			'read'            => true,
			'upload_files'    => true,
			self::CAP_EDIT    => true,
			self::CAP_ENROLL  => true,
			self::CAP_REPORTS => true,
			self::CAP_TAKE    => true,
		);
	}

	/**
	 * Same WP primitives as Subscriber, plus an LMS learner identity cap.
	 * Course / quiz / progress access is still enrollment — not this cap alone.
	 *
	 * @return array<string, bool>
	 */
	public static function studentCaps(): array {
		return array(
			'read'         => true,
			self::CAP_TAKE => true,
		);
	}
}
