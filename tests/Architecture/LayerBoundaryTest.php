<?php
declare(strict_types=1);

namespace MintLMS\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Architecture boundaries for Domain / Application / Http.
 *
 * Note: until 1.0.536, Composer autoload of src/Api/V1/functions.php used
 * `defined( 'ABSPATH' ) || exit`, so `composer arch` often exited 0 without
 * running these tests. After that guard was fixed, these known legacy
 * violations surfaced. They are allowlisted so CI stays honest about *new*
 * debt; remove entries as files are cleaned up.
 */
final class LayerBoundaryTest extends TestCase
{
    private const PURE_DIRS = ['Domain', 'Application'];

    private const FORBIDDEN_PATTERNS = [
        '/\$wpdb\b/'                        => '$wpdb',
        '/\bwp_[a-z_]+\s*\(/'               => 'wp_* function',
        '/\b(get|update|add|delete)_option\s*\(/' => '*_option()',
        '/\b(add|do)_action\s*\(/'          => 'add_action/do_action',
        '/\b(add|apply)_filters?\s*\(/'     => 'add_filter/apply_filters',
        '/\bcurrent_user_can\s*\(/'         => 'current_user_can()',
        '/\bget_current_user_id\s*\(/'      => 'get_current_user_id()',
        '/\b(esc_html|esc_attr|esc_url)\s*\(/' => 'esc_* function',
        '/\bsanitize_[a-z_]+\s*\(/'         => 'sanitize_* function',
        '/\bis_admin\s*\(/'                 => 'is_admin()',
        '/\bWP_[A-Z][A-Za-z_]*/'            => 'WP_* class',
        '/\b__\s*\(\s*[\'"]/'               => '__() translation',
        '/\b_n\s*\(/'                       => '_n() translation',
        '/\bget_userdata\s*\(/'             => 'get_userdata()',
        '/\besc_html__\s*\(/'               => 'esc_html__()',
        '/\bABSPATH\b/'                     => 'ABSPATH',
        '/\bplugin_dir_(path|url)\s*\(/'    => 'plugin_dir_*',
    ];

    /**
     * Legacy WP coupling already present when arch tests started actually running.
     * Paths are relative to the repo root (same format as failure messages).
     *
     * @var list<string>
     */
    private const KNOWN_PURE_LAYER_VIOLATIONS = [
        'tests/Architecture/../../src/Application/Quiz/QuizService.php uses __() translation',
        'tests/Architecture/../../src/Application/Lesson/LessonService.php uses __() translation',
        'tests/Architecture/../../src/Application/Enrollment/EnrollmentService.php uses WP_* class',
        'tests/Architecture/../../src/Application/Enrollment/EnrollmentService.php uses __() translation',
        'tests/Architecture/../../src/Application/Enrollment/EnrollmentService.php uses get_userdata()',
        'tests/Architecture/../../src/Application/Admin/SampleCourseSeeder.php uses $wpdb',
        'tests/Architecture/../../src/Application/Admin/SampleCourseSeeder.php uses __() translation',
        'tests/Architecture/../../src/Application/Admin/SampleCourseSeeder.php uses esc_html__()',
        'tests/Architecture/../../src/Application/Admin/SampleCourseSeeder.php uses ABSPATH',
        'tests/Architecture/../../src/Application/Admin/CreatorDashboardService.php uses __() translation',
        'tests/Architecture/../../src/Application/Ai/AiAssistantService.php uses sanitize_* function',
        'tests/Architecture/../../src/Application/Ai/AiAssistantService.php uses ABSPATH',
        'tests/Architecture/../../src/Application/Course/Dto/CourseDto.php uses ABSPATH',
        'tests/Architecture/../../src/Application/Student/StudentExperienceService.php uses wp_* function',
        'tests/Architecture/../../src/Application/Student/StudentExperienceService.php uses WP_* class',
        'tests/Architecture/../../src/Application/Student/StudentExperienceService.php uses __() translation',
        'tests/Architecture/../../src/Application/Student/StudentExperienceService.php uses get_userdata()',
    ];

    /** @var list<string> */
    private const KNOWN_APPLICATION_INFRA_IMPORTS = [
        'SampleCourseSeeder.php imports MintLMS\Infrastructure',
        'AiAssistantService.php imports MintLMS\Infrastructure',
    ];

    /** @var list<string> */
    private const KNOWN_CONTROLLER_PERSISTENCE = [
        'ContentPostListController.php uses $wpdb directly',
    ];

    /** Domain and Application must contain zero WordPress symbols (beyond allowlist). */
    public function test_pure_layers_contain_no_wordpress_code(): void
    {
        $violations = [];

        foreach (self::PURE_DIRS as $dir) {
            foreach ($this->phpFilesIn(__DIR__ . '/../../src/' . $dir) as $file) {
                $contents = file_get_contents($file);

                foreach (self::FORBIDDEN_PATTERNS as $pattern => $label) {
                    if (preg_match($pattern, $contents)) {
                        $violations[] = sprintf(
                            '%s uses %s',
                            str_replace(dirname(__DIR__, 2) . '/', '', $file),
                            $label
                        );
                    }
                }
            }
        }

        $new = array_values(array_diff($violations, self::KNOWN_PURE_LAYER_VIOLATIONS));

        $this->assertSame([], $new, sprintf(
            "Layer boundary violated. Domain and Application must never touch WordPress.\n" .
            "Move this into src/Infrastructure/ behind an interface.\n\n%s",
            implode("\n", $new)
        ));
    }

    /** Domain must not depend on Application, Infrastructure or Http. */
    public function test_domain_does_not_depend_on_outer_layers(): void
    {
        $violations = [];

        foreach ($this->phpFilesIn(__DIR__ . '/../../src/Domain') as $file) {
            $contents = file_get_contents($file);
            foreach (['Application', 'Infrastructure', 'Http', 'Api', 'Frontend'] as $outer) {
                if (preg_match('/use\s+MintLMS\\\\' . $outer . '\\\\/', $contents)) {
                    $violations[] = basename($file) . ' imports MintLMS\\' . $outer;
                }
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    /** Application must not depend on Infrastructure or Http (beyond allowlist). */
    public function test_application_does_not_depend_on_infrastructure(): void
    {
        $violations = [];

        foreach ($this->phpFilesIn(__DIR__ . '/../../src/Application') as $file) {
            $contents = file_get_contents($file);
            foreach (['Infrastructure', 'Http', 'Frontend'] as $outer) {
                if (preg_match('/use\s+MintLMS\\\\' . $outer . '\\\\/', $contents)) {
                    $violations[] = basename($file) . ' imports MintLMS\\' . $outer;
                }
            }
        }

        $new = array_values(array_diff($violations, self::KNOWN_APPLICATION_INFRA_IMPORTS));

        $this->assertSame([], $new, implode("\n", $new));
    }

    /** REST controllers stay thin: no direct repository or $wpdb use (beyond allowlist). */
    public function test_controllers_do_not_touch_persistence(): void
    {
        $violations = [];

        foreach ($this->phpFilesIn(__DIR__ . '/../../src/Http') as $file) {
            $contents = file_get_contents($file);
            if (preg_match('/\$wpdb\b/', $contents)) {
                $violations[] = basename($file) . ' uses $wpdb directly';
            }
            if (preg_match('/use\s+MintLMS\\\\Infrastructure\\\\Database\\\\Repository/', $contents)) {
                $violations[] = basename($file) . ' imports a concrete repository';
            }
        }

        $new = array_values(array_diff($violations, self::KNOWN_CONTROLLER_PERSISTENCE));

        $this->assertSame([], $new, implode("\n", $new));
    }

    /** Every REST route must declare a real permission_callback. */
    public function test_no_open_rest_routes(): void
    {
        $violations = [];

        foreach ($this->phpFilesIn(__DIR__ . '/../../src/Http') as $file) {
            $contents = file_get_contents($file);
            if (str_contains($contents, '__return_true')) {
                $violations[] = basename($file) . ' uses __return_true as a permission_callback';
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    /** Only the agreed prefixes appear in table names. */
    public function test_tables_use_the_agreed_prefix(): void
    {
        $violations = [];

        foreach ($this->phpFilesIn(__DIR__ . '/../../src') as $file) {
            $contents = file_get_contents($file);
            if (preg_match('/\$wpdb->prefix\s*\.\s*[\'"](?!mintlms_)/', $contents)) {
                $violations[] = basename($file) . ' builds a table name without the mintlms_ prefix';
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    /** @return iterable<string> */
    private function phpFilesIn(string $dir): iterable
    {
        if (!is_dir($dir)) {
            return [];
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }
}
