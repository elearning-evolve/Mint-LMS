<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Auth;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;

final class WpAuthorization implements AuthorizationInterface {

	public function getCurrentUserId(): int {
		return get_current_user_id();
	}

	public function canCreateCourse( int $userId ): bool {
		return $this->hasCapability( $userId, 'edit_mintlms_courses' )
			|| $this->hasCapability( $userId, 'manage_mintlms' );
	}

	public function canEditCourse( int $userId, int $authorId ): bool {
		if ( $this->hasCapability( $userId, 'manage_mintlms' ) ) {
			return true;
		}

		if ( $this->hasCapability( $userId, 'edit_others_mintlms_courses' ) ) {
			return true;
		}

		return $userId === $authorId && $this->hasCapability( $userId, 'edit_mintlms_courses' );
	}

	public function canDeleteCourse( int $userId, int $authorId ): bool {
		return $this->canEditCourse( $userId, $authorId );
	}

	public function canViewCourse( int $userId, int $authorId ): bool {
		return $this->canEditCourse( $userId, $authorId );
	}

	public function canPublishCourse( int $userId, int $authorId ): bool {
		return $this->canEditCourse( $userId, $authorId );
	}

	public function canListCourses( int $userId ): bool {
		return $this->canCreateCourse( $userId );
	}

	public function canViewAllCourses( int $userId ): bool {
		return $this->hasCapability( $userId, 'manage_mintlms' )
			|| $this->hasCapability( $userId, 'edit_others_mintlms_courses' );
	}

	public function canEnrollStudents( int $userId ): bool {
		return $this->hasCapability( $userId, 'enroll_mintlms_students' )
			|| $this->hasCapability( $userId, 'manage_mintlms' );
	}

	private function hasCapability( int $userId, string $capability ): bool {
		if ( $userId <= 0 ) {
			return false;
		}

		return user_can( $userId, $capability );
	}
}
