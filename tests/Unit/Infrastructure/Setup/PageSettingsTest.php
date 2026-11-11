<?php
declare(strict_types=1);

namespace MintLMS\Tests\Unit\Infrastructure\Setup;

use MintLMS\Infrastructure\Setup\PageSettings;
use PHPUnit\Framework\TestCase;

final class PageSettingsTest extends TestCase
{
    public function test_getPageIds_read_from_options_override(): void
    {
        $settings = new PageSettings([
            PageSettings::OPTION_DASHBOARD => 10,
            PageSettings::OPTION_CATALOG => 20,
            PageSettings::OPTION_PLAYER => 30,
        ]);

        $this->assertSame(10, $settings->getDashboardPageId());
        $this->assertSame(20, $settings->getCatalogPageId());
        $this->assertSame(30, $settings->getPlayerPageId());
    }

    public function test_getDefaultEnrollment_falls_back_to_open_for_invalid_value(): void
    {
        $settings = new PageSettings([
            PageSettings::OPTION_DEFAULT_ENROLLMENT => 'invalid',
        ]);

        $this->assertSame('open', $settings->getDefaultEnrollment());
    }

    public function test_getDefaultEnrollment_accepts_manual(): void
    {
        $settings = new PageSettings([
            PageSettings::OPTION_DEFAULT_ENROLLMENT => 'manual',
        ]);

        $this->assertSame('manual', $settings->getDefaultEnrollment());
    }

    public function test_email_flags_read_as_booleans(): void
    {
        $settings = new PageSettings([
            PageSettings::OPTION_EMAIL_ENROLL => '1',
            PageSettings::OPTION_EMAIL_COMPLETE => 0,
        ]);

        $this->assertTrue($settings->isEmailEnrollEnabled());
        $this->assertFalse($settings->isEmailCompleteEnabled());
    }

    public function test_getCourseUrl_builds_catalog_query_arg(): void
    {
        if (!function_exists('home_url')) {
            $this->markTestSkipped('WordPress functions not available.');
        }

        $settings = new PageSettings([
            PageSettings::OPTION_CATALOG => 0,
        ]);

        $url = $settings->getCourseUrl( 42 );

        $this->assertStringContainsString('mintlms_course=42', $url);
    }

    public function test_getSetupChecklist_marks_unconfigured_pages_pending(): void
    {
        $settings = new PageSettings([
            PageSettings::OPTION_DASHBOARD => 0,
            PageSettings::OPTION_CATALOG => 0,
            PageSettings::OPTION_PLAYER => 0,
            PageSettings::OPTION_DEFAULT_ENROLLMENT => 'open',
        ]);

        $checklist = $settings->getSetupChecklist();

        $this->assertCount(4, $checklist);
        $this->assertFalse($checklist[0]['done']);
        $this->assertFalse($checklist[1]['done']);
        $this->assertFalse($checklist[2]['done']);
        $this->assertTrue($checklist[3]['done']);
    }
}
