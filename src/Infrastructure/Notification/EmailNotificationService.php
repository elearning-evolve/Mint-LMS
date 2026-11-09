<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Notification;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Certificate\CertificateService;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentRepositoryInterface;
use MintLMS\Domain\Enrollment\EnrollmentStatus;
use MintLMS\Domain\Event\CourseCompleted;
use MintLMS\Domain\Event\CoursePublished;
use MintLMS\Domain\Event\EnrollmentCreated;
use MintLMS\Infrastructure\View\ScopedPhpTemplate;

final class EmailNotificationService {

	public function __construct(
		private CourseRepositoryInterface $courseRepository,
		private string $viewsPath,
		private ?CertificateService $certificateService = null,
		private ?EnrollmentRepositoryInterface $enrollmentRepository = null,
	) {
	}

	public function register(): void {
		add_action( 'mintlms_enrollment_created', array( $this, 'onEnrollmentCreated' ) );
		add_action( 'mintlms_course_completed', array( $this, 'onCourseCompleted' ) );
		add_action( 'mintlms_course_published', array( $this, 'onCoursePublished' ) );
	}

	public function onEnrollmentCreated( EnrollmentCreated $event ): void {
		if ( ! $this->isEnabled( 'mintlms_email_enroll_enabled' ) ) {
			return;
		}

		$user = get_userdata( $event->userId->toInt() );

		if ( ! $user instanceof \WP_User || '' === $user->user_email ) {
			return;
		}

		$course = $this->courseRepository->findById( $event->courseId );

		if ( null === $course ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: course title */
			__( 'Welcome to %s', 'mint-lms' ),
			$course->title
		);

		$body = $this->renderView(
			'enrolled.php',
			array(
				'studentName' => $user->display_name,
				'courseTitle' => $course->title,
			)
		);

		$this->send( $user->user_email, $subject, $body );
	}

	public function onCourseCompleted( CourseCompleted $event ): void {
		if ( ! $this->isEnabled( 'mintlms_email_complete_enabled' ) ) {
			return;
		}

		$user = get_userdata( $event->userId->toInt() );

		if ( ! $user instanceof \WP_User || '' === $user->user_email ) {
			return;
		}

		$course = $this->courseRepository->findById( $event->courseId );

		if ( null === $course ) {
			return;
		}

		$certificateUrl = '';

		if ( null !== $this->certificateService && $this->certificateService->isEligible( $event->userId->toInt(), $event->courseId ) ) {
			$certificateUrl = $this->certificateService->getCertificateUrl( $event->userId->toInt(), $event->courseId );
		}

		$subject = sprintf(
			/* translators: %s: course title */
			__( 'Congratulations! You completed %s', 'mint-lms' ),
			$course->title
		);

		$body = $this->renderView(
			'course-completed.php',
			array(
				'studentName'    => $user->display_name,
				'courseTitle'    => $course->title,
				'certificateUrl' => $certificateUrl,
			)
		);

		$this->send( $user->user_email, $subject, $body );
	}

	public function onCoursePublished( CoursePublished $event ): void {
		if ( null === $this->enrollmentRepository ) {
			return;
		}

		$course = $this->courseRepository->findById( $event->courseId );

		if ( null === $course || ! $course->settings->emailOnPublish ) {
			return;
		}

		$page    = 1;
		$perPage = 100;
		$sentTo  = array();

		do {
			$result = $this->enrollmentRepository->listByCourse(
				$event->courseId,
				$page,
				$perPage,
				EnrollmentStatus::Active
			);

			foreach ( $result['enrollments'] as $enrollment ) {
				if ( isset( $sentTo[ $enrollment->userId ] ) ) {
					continue;
				}

				$user = get_userdata( $enrollment->userId );

				if ( ! $user instanceof \WP_User || '' === $user->user_email ) {
					continue;
				}

				$subject = sprintf(
					/* translators: %s: course title */
					__( '%s is now available', 'mint-lms' ),
					$course->title
				);

				$body = $this->renderView(
					'course-published.php',
					array(
						'studentName' => $user->display_name,
						'courseTitle' => $course->title,
					)
				);

				$this->send( $user->user_email, $subject, $body );
				$sentTo[ $enrollment->userId ] = true;
			}

			$fetched = count( $result['enrollments'] );
			++$page;
		} while ( $fetched === $perPage );
	}

	private function isEnabled( string $option ): bool {
		return (bool) get_option( $option, true );
	}

	/**
	 * @param array<string, mixed> $vars
	 */
	private function renderView( string $template, array $vars ): string {
		$path = trailingslashit( $this->viewsPath ) . ltrim( $template, '/' );

		return ScopedPhpTemplate::render( $path, $vars );
	}

	private function send( string $to, string $subject, string $body ): void {
		if ( '' === trim( $body ) ) {
			return;
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $body, $headers );
	}
}
