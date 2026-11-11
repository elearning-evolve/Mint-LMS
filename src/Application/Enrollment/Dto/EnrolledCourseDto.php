<?php
declare(strict_types=1);

namespace MintLMS\Application\Enrollment\Dto;

final readonly class EnrolledCourseDto {

	public function __construct(
		public int $enrollmentId,
		public int $courseId,
		public string $courseTitle,
		public string $courseSlug,
		public string $status,
		public string $enrolledAt,
		public float $progressPct,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'enrollmentId' => $this->enrollmentId,
			'courseId'     => $this->courseId,
			'courseTitle'  => $this->courseTitle,
			'courseSlug'   => $this->courseSlug,
			'status'       => $this->status,
			'enrolledAt'   => $this->enrolledAt,
			'progressPct'  => $this->progressPct,
		);
	}
}
