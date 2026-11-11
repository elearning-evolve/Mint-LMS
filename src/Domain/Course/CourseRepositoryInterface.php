<?php
declare(strict_types=1);

namespace MintLMS\Domain\Course;

interface CourseRepositoryInterface {

	public function findById( int $id ): ?Course;

	public function findBySlug( string $slug ): ?Course;

	public function save( Course $course ): Course;

	public function delete( int $id ): bool;

	/**
	 * @return array{courses: list<Course>, total: int}
	 */
	public function list( int $page, int $perPage, ?int $authorId = null, ?CourseStatus $status = null, ?string $search = null ): array;
}
