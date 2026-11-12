<?php
declare(strict_types=1);

namespace MintLMS\Tests\Integration\Setup;

use MintLMS\Infrastructure\Setup\PageInstaller;
use MintLMS\Infrastructure\Setup\PageSettings;
use PHPUnit\Framework\TestCase;

final class PageInstallerTest extends TestCase
{
    /** @var list<int> */
    private array $createdPageIds = [];

    protected function setUp(): void
    {
        if (!defined('ABSPATH') || !function_exists('wp_insert_post')) {
            $this->markTestSkipped('WordPress test environment not available.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdPageIds as $pageId) {
            wp_delete_post($pageId, true);
        }

        delete_option(PageSettings::OPTION_DASHBOARD);
        delete_option(PageSettings::OPTION_CATALOG);
        delete_option(PageSettings::OPTION_PLAYER);

        $this->createdPageIds = [];
    }

    public function test_install_creates_required_pages_and_stores_options(): void
    {
        delete_option(PageSettings::OPTION_DASHBOARD);
        delete_option(PageSettings::OPTION_CATALOG);
        delete_option(PageSettings::OPTION_PLAYER);

        (new PageInstaller())->install();

        $dashboardId = (int) get_option(PageSettings::OPTION_DASHBOARD, 0);
        $catalogId = (int) get_option(PageSettings::OPTION_CATALOG, 0);
        $playerId = (int) get_option(PageSettings::OPTION_PLAYER, 0);

        $this->assertGreaterThan(0, $dashboardId);
        $this->assertGreaterThan(0, $catalogId);
        $this->assertGreaterThan(0, $playerId);

        $this->createdPageIds = [$dashboardId, $catalogId, $playerId];

        $dashboard = get_post($dashboardId);
        $catalog = get_post($catalogId);
        $player = get_post($playerId);

        $this->assertInstanceOf(\WP_Post::class, $dashboard);
        $this->assertInstanceOf(\WP_Post::class, $catalog);
        $this->assertInstanceOf(\WP_Post::class, $player);

        $this->assertStringContainsString('[mint_lms_dashboard]', (string) $dashboard->post_content);
        $this->assertStringContainsString('[mint_lms_catalog]', (string) $catalog->post_content);
        $this->assertStringContainsString('[mint_lms_player]', (string) $player->post_content);
    }

    public function test_install_is_idempotent_when_pages_already_exist(): void
    {
        (new PageInstaller())->install();

        $firstDashboardId = (int) get_option(PageSettings::OPTION_DASHBOARD, 0);
        $this->assertGreaterThan(0, $firstDashboardId);
        $this->createdPageIds[] = $firstDashboardId;

        (new PageInstaller())->install();

        $secondDashboardId = (int) get_option(PageSettings::OPTION_DASHBOARD, 0);

        $this->assertSame($firstDashboardId, $secondDashboardId);
    }
}
