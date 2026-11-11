<?php
declare(strict_types=1);

namespace MintLMS\Application\Certificate;

use MintLMS\Application\Contract\CertificateTemplateProviderInterface;
use MintLMS\Application\Contract\CertificateUrlBuilderInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Shared\Clock;

final class CertificateService {

	public function __construct(
		private CourseRepositoryInterface $courseRepository,
		private ProgressRepositoryInterface $progressRepository,
		private Clock $clock,
		private CertificateTemplateProviderInterface $templateProvider,
		private CertificateUrlBuilderInterface $urlBuilder,
		private UserLookupInterface $userLookup,
	) {
	}

	public function generateCertificate( int $userId, int $courseId ): string {
		$this->assertEligible( $userId, $courseId );

		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$dateFormat = $this->templateProvider->getDateFormat();
		$summary    = $this->progressRepository->getSummary( $userId, $courseId );
		$date       = null !== $summary
			? $summary->updatedAt->format( $dateFormat )
			: $this->clock->now()->format( $dateFormat );

		$replacements = array(
			'{{student_name}}'    => $this->resolveStudentName( $userId ),
			'{{course_name}}'     => $course->title,
			'{{completion_date}}' => $date,
		);

		$template = $this->templateProvider->getTemplate();

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	public function getCertificateUrl( int $userId, int $courseId ): string {
		$this->assertEligible( $userId, $courseId );

		return $this->urlBuilder->buildDownloadUrl( $userId, $courseId );
	}

	public function isEligible( int $userId, int $courseId ): bool {
		if ( $userId <= 0 || $courseId <= 0 ) {
			return false;
		}

		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course || ! $course->settings->certificate ) {
			return false;
		}

		$summary = $this->progressRepository->getSummary( $userId, $courseId );

		return null !== $summary && $summary->isCourseComplete();
	}

	private function assertEligible( int $userId, int $courseId ): void {
		if ( ! $this->isEligible( $userId, $courseId ) ) {
			throw new ForbiddenException( 'Certificate is not available for this course.' );
		}
	}

	private function resolveStudentName( int $userId ): string {
		$name = $this->userLookup->getDisplayName( $userId );

		return null !== $name ? $name : 'Student';
	}
}
