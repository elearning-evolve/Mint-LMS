<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit;

use MintLMS\Infrastructure\Ui\UiRoot;
use PHPUnit\Framework\TestCase;

final class TailwindConfigTest extends TestCase
{
    public function test_tailwind_config_has_required_scoping(): void
    {
        $configPath = dirname( __DIR__, 2 ) . '/tailwind.config.js';
        $this->assertFileExists( $configPath );

        $contents = file_get_contents( $configPath );

        $this->assertStringContainsString( "prefix: 'mint-'", $contents );
        $this->assertStringContainsString( "important: '#mint-lms-root'", $contents );
        $this->assertStringContainsString( 'preflight: false', $contents );
    }

    public function test_design_tokens_file_exists_and_scopes_root(): void
    {
        $tokensPath = dirname( __DIR__, 2 ) . '/assets/css/mint-tokens.css';
        $this->assertFileExists( $tokensPath );

        $contents = file_get_contents( $tokensPath );

        $this->assertStringContainsString( '#mint-lms-root', $contents );
        $this->assertStringContainsString( '--mint-accent: #3F00FF', $contents );
        $this->assertStringNotContainsString( '.mint-t-display', $contents );
    }

    public function test_compiled_css_includes_design_tokens_and_scoped_utilities(): void
    {
        $cssPath = dirname( __DIR__, 2 ) . '/assets/dist/main.css';
        $this->assertFileExists( $cssPath );

        $contents = file_get_contents( $cssPath );

        $this->assertGreaterThan(
            5_000,
            strlen( $contents ),
            'Compiled admin CSS looks unprocessed; run npm run build and verify postcss.config.js exports plugins.'
        );
        $this->assertStringContainsString( '--mint-accent:#3f00ff', strtolower( $contents ) );
        $this->assertStringContainsString( '#mint-lms-root', $contents );
        $this->assertStringContainsString( 'mint-bg-accent', $contents );
        $this->assertStringContainsString( 'mint-text-display', $contents );
    }

    public function test_ui_root_helper_renders_isolated_wrapper(): void
    {
        $admin   = UiRoot::open( 'admin' );
        $student = UiRoot::open( 'student' );

        $this->assertStringContainsString( 'id="mint-lms-root"', $admin );
        $this->assertStringContainsString( 'class="mint-lms-ui"', $admin );
        $this->assertStringContainsString( 'data-mint-lms-ui="1"', $admin );

        $this->assertStringContainsString( 'mint-lms-student', $student );
        $this->assertSame( '</div>', UiRoot::close() );
    }
}
