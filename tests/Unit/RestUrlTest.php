<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class RestUrlTest extends TestCase
{
    public function test_join_rest_url_handles_missing_slashes(): void
    {
        $this->assertSame(
            'https://local.wp/wp-json/mintlms/v1/courses',
            $this->joinRestUrl('https://local.wp/wp-json/mintlms/v1', 'courses')
        );
        $this->assertSame(
            'https://local.wp/wp-json/mintlms/v1/courses',
            $this->joinRestUrl('https://local.wp/wp-json/mintlms/v1/', 'courses')
        );
    }

    private function joinRestUrl(string $base, string $path): string
    {
        $normalizedBase = rtrim($base, '/');
        $normalizedPath = ltrim($path, '/');

        return $normalizedBase . '/' . $normalizedPath;
    }
}
