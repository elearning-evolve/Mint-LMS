<?php
declare(strict_types=1);

namespace MintLMS\Tests\Integration\Repository;

use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository;
use MintLMS\Infrastructure\PostType\PostTypes;
use PHPUnit\Framework\TestCase;

final class WpPostCourseRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('ABSPATH') || !isset($GLOBALS['wpdb']) || !$GLOBALS['wpdb'] instanceof \wpdb) {
            $this->markTestSkipped('WordPress test environment not available.');
        }

        if (!post_type_exists(PostTypes::COURSE)) {
            $registrar = new \MintLMS\Infrastructure\PostType\PostTypeRegistrar();
            $registrar->registerPostTypes();
            $registrar->registerStatuses();
        }
    }

    public function test_save_and_find_by_id_round_trip(): void
    {
        $repository = new WpPostCourseRepository();
        $now = new \DateTimeImmutable('2026-02-01 12:00:00');

        $course = new Course(
            0,
            'Integration Course',
            'integration-course-' . wp_generate_password(6, false),
            'Created during integration test.',
            null,
            CourseStatus::Draft,
            EnrollmentType::Open,
            1,
            $now,
            $now,
        );

        $saved = $repository->save($course);

        $this->assertGreaterThan(0, $saved->id);

        $found = $repository->findById($saved->id);

        $this->assertNotNull($found);
        $this->assertSame('Integration Course', $found->title);
        $this->assertSame($saved->slug, $found->slug);
        $this->assertSame(PostTypes::COURSE, get_post_type($saved->id));

        $repository->delete($saved->id);
    }
}
