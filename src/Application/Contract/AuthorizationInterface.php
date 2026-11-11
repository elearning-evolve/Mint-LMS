<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface AuthorizationInterface {

	public function getCurrentUserId(): int;

	public function canCreateCourse( int $userId ): bool;

	public function canEditCourse( int $userId, int $authorId ): bool;

	public function canDeleteCourse( int $userId, int $authorId ): bool;

	public function canViewCourse( int $userId, int $authorId ): bool;

	public function canPublishCourse( int $userId, int $authorId ): bool;

	public function canListCourses( int $userId ): bool;

	public function canViewAllCourses( int $userId ): bool;

	public function canEnrollStudents( int $userId ): bool;
}
