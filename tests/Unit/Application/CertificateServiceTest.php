<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Application;

use MintLMS\Application\Certificate\CertificateService;
use MintLMS\Application\Contract\CertificateTemplateProviderInterface;
use MintLMS\Application\Contract\CertificateUrlBuilderInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseSettings;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Progress\ProgressRepositoryInterface;
use MintLMS\Domain\Progress\ProgressSummary;
use MintLMS\Domain\Shared\Clock;
use MintLMS\Domain\Shared\UserId;
use PHPUnit\Framework\TestCase;

final class CertificateServiceTest extends TestCase
{
    private CourseRepositoryInterface $courseRepository;

    private ProgressRepositoryInterface $progressRepository;

    private Clock $clock;

    private CertificateTemplateProviderInterface $templateProvider;

    private CertificateUrlBuilderInterface $urlBuilder;

    private UserLookupInterface $userLookup;

    protected function setUp(): void
    {
        $this->courseRepository = $this->createMock(CourseRepositoryInterface::class);
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);
        $this->clock = $this->createMock(Clock::class);
        $this->templateProvider = $this->createMock(CertificateTemplateProviderInterface::class);
        $this->urlBuilder = $this->createMock(CertificateUrlBuilderInterface::class);
        $this->userLookup = $this->createMock(UserLookupInterface::class);

        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-03-01 12:00:00'));
        $this->templateProvider->method('getDateFormat')->willReturn('F j, Y');
        $this->templateProvider->method('getTemplate')->willReturn(
            '<div>{{student_name}} completed {{course_name}} on {{completion_date}}</div>'
        );
    }

    public function test_generate_certificate_replaces_student_and_course_names(): void
    {
        $userId = 5;
        $courseId = 10;
        $now = new \DateTimeImmutable('2026-03-01 12:00:00');

        $this->courseRepository
            ->method('findById')
            ->with($courseId)
            ->willReturn($this->sampleCourse($courseId, 'Advanced WordPress'));

        $this->progressRepository
            ->method('getSummary')
            ->with($userId, $courseId)
            ->willReturn(new ProgressSummary(UserId::fromInt($userId), $courseId, 3, 3, 100.0, 101, $now));

        $this->userLookup
            ->method('getDisplayName')
            ->with($userId)
            ->willReturn('Jane Student');

        $service = new CertificateService(
            $this->courseRepository,
            $this->progressRepository,
            $this->clock,
            $this->templateProvider,
            $this->urlBuilder,
            $this->userLookup,
        );

        $html = $service->generateCertificate($userId, $courseId);

        $this->assertStringContainsString('Jane Student', $html);
        $this->assertStringContainsString('Advanced WordPress', $html);
    }

    private function sampleCourse(int $id, string $title): Course
    {
        return new Course(
            $id,
            $title,
            'course-' . $id,
            'Description',
            null,
            CourseStatus::Published,
            EnrollmentType::Open,
            1,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new CourseSettings(true, true, true),
        );
    }
}
