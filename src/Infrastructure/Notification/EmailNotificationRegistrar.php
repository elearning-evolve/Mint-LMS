<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Notification;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Certificate\CertificateService;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;

final class EmailNotificationRegistrar {

	public function __construct(
		private CourseRepositoryInterface $courseRepository,
		private ?CertificateService $certificateService = null,
		private ?EnrollmentRepositoryInterface $enrollmentRepository = null,
	) {
	}

	public function register(): void {
		$service = new EmailNotificationService(
			$this->courseRepository,
			MINTLMS_PATH . 'views/emails',
			$this->certificateService,
			$this->enrollmentRepository,
		);

		$service->register();
	}
}
