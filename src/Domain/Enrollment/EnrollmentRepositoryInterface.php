<?php
declare(strict_types=1);

namespace MintLMS\Domain\Enrollment;

interface EnrollmentRepositoryInterface {

	public function findById( int $id ): ?Enrollment;

	public function findByUserAndCourse( int $userId, int $courseId ): ?Enrollment;

	public function save( Enrollment $enrollment ): Enrollment;

	/**
	 * @return array{enrollments: list<Enrollment>, total: int}
	 */
	public function listByCourse(
		int $courseId,
		int $page,
		int $perPage,
		?EnrollmentStatus $status = null,
	): array;

	/**
	 * @return array{enrollments: list<Enrollment>, total: int}
	 */
	public function listByUser(
		int $userId,
		int $page,
		int $perPage,
		?EnrollmentStatus $status = null,
	): array;

	/**
	 * @param list<int> $userIds
	 * @return array<int, float>
	 */
	public function getProgressPercentagesForCourse( int $courseId, array $userIds ): array;

	public function countActiveByCourse( int $courseId ): int;
}
