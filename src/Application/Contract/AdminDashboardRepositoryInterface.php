<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface AdminDashboardRepositoryInterface {

	/**
	 * @param list<int> $courseIds
	 * @return array<string, mixed>
	 */
	public function getAuthorStats( int $authorId, array $courseIds ): array;

	/**
	 * @param list<int> $courseIds
	 * @return array<int, array{students: int, completion_pct: int|null, lesson_count: int}>
	 */
	public function getCourseStatsBatch( array $courseIds ): array;

	/**
	 * Latest active enrollments across the given courses (excludes cancelled).
	 *
	 * @param list<int> $courseIds
	 * @return list<array{user_id: int, course_id: int, course_title: string, enrolled_at: string, enrollment_id: int}>
	 */
	public function getRecentActiveEnrollments( array $courseIds, int $limit = 5 ): array;

	/**
	 * Reports page payload: totals, range deltas/charts, top courses, activity.
	 *
	 * @return array<string, mixed>
	 */
	public function getReportsDashboard( ?int $authorId ): array;
}
