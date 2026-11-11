<?php
declare(strict_types=1);

namespace MintLMS\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class RestControllerApiResponseTest extends TestCase
{
    public function test_controllers_using_api_response_import_it(): void
    {
        $controllerDir = dirname(__DIR__, 2) . '/src/Http/Rest/Controller';
        $files         = glob($controllerDir . '/*.php') ?: array();

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);

            if (! str_contains($contents, 'ApiResponse::')) {
                continue;
            }

            $this->assertStringContainsString(
                'use MintLMS\\Http\\Rest\\Response\\ApiResponse;',
                $contents,
                basename($file) . ' uses ApiResponse but is missing the import.'
            );
        }
    }
}
