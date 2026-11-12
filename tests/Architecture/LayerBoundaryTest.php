<?php
declare(strict_types=1);

namespace MintLMS\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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

    /** Domain and Application must contain zero WordPress symbols. */
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

        $this->assertSame([], $violations, sprintf(
            "Layer boundary violated. Domain and Application must never touch WordPress.\n" .
            "Move this into src/Infrastructure/ behind an interface.\n\n%s",
            implode("\n", $violations)
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

    /** Application must not depend on Infrastructure or Http. */
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

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    /** REST controllers stay thin: no direct repository or $wpdb use. */
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

        $this->assertSame([], $violations, implode("\n", $violations));
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
