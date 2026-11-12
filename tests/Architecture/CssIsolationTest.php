<?php
declare(strict_types=1);

namespace MintLMS\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class CssIsolationTest extends TestCase
{
    /** @var list<string> */
    private const CSS_FILES = array(
        'assets/dist/main.css',
        'assets/dist/student.css',
    );

    /** @var list<string> */
    private const UI_ENTRYPOINTS = array(
        'views/admin/layout.php',
        'views/admin/layout-builder.php',
        'views/admin/first-run.php',
        'views/admin/guided-course.php',
    );

    public function test_ui_entrypoints_render_isolated_root(): void
    {
        $rootId = \MintLMS\Infrastructure\Ui\UiRoot::ID;

        foreach ( self::UI_ENTRYPOINTS as $relativePath ) {
            $path = dirname( __DIR__, 2 ) . '/' . $relativePath;
            $this->assertFileExists( $path );

            $contents = file_get_contents( $path );

            $this->assertTrue(
                str_contains( $contents, $rootId ) || str_contains( $contents, 'UiRoot::open' ),
                sprintf( 'UI entrypoint %s must render the Mint LMS root wrapper.', $relativePath )
            );
        }
    }

    public function test_compiled_stylesheets_are_scoped_to_root(): void
    {
        foreach ( self::CSS_FILES as $relativePath ) {
            $path = dirname( __DIR__, 2 ) . '/' . $relativePath;
            $this->assertFileExists( $path );

            $contents = file_get_contents( $path );
            $this->assertStringContainsString( '#mint-lms-root', $contents );

            $this->assertDoesNotMatchRegularExpression(
                '/(?:^|[\n\r}])\s*\.mint-[a-z0-9_-]+(?:[^{]*\{)/',
                $contents,
                sprintf( 'Found unscoped Mint selector in %s', $relativePath )
            );

            $this->assertDoesNotMatchRegularExpression(
                '/(?:^|[\n\r{,])\s*(?:html|body|:root)\s*[,{]/',
                $contents,
                sprintf( 'Found global document selector in %s', $relativePath )
            );

            $this->assertDoesNotMatchRegularExpression(
                '/(?:^|[\n\r}])\s*\*,(?:before|after)/',
                $contents,
                sprintf( 'Found unscoped universal selector in %s', $relativePath )
            );
        }
    }

    public function test_shortcode_wrapper_uses_ui_root_helper(): void
    {
        $path = dirname( __DIR__, 2 ) . '/src/Frontend/ShortcodeRegistrar.php';
        $contents = file_get_contents( $path );

        $this->assertStringContainsString( 'UiRoot::open( \'student\' )', $contents );
        $this->assertStringContainsString( 'UiRoot::close()', $contents );
    }
}
