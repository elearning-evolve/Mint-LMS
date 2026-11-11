<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class GuidedCourseApiContractTest extends TestCase
{
    public function test_rest_url_without_trailing_slash_joins_correctly(): void
    {
        $base = 'https://example.com/wp-json/mintlms/v1';
        $path = 'courses';

        $url = rtrim($base, '/') . '/' . ltrim($path, '/');

        $this->assertSame('https://example.com/wp-json/mintlms/v1/courses', $url);
        $this->assertStringNotContainsString('v1courses', $url);
    }
}
