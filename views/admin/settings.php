<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Infrastructure\Setup\PageSettings;

$renderer     = new ViewRenderer();
$pageSettings = new PageSettings();

$content = $renderer->render(
	'admin/_settings-content',
	array(
		'renderer'     => $renderer,
		'pageSettings' => $pageSettings,
		'checklist'    => $pageSettings->getSetupChecklist(),
	)
);

$renderer->echo(
	'admin/layout',
	array(
		'pageLabel'     => __( 'Settings', 'mint-lms' ),
		'content'       => $content,
		'maxWidthClass' => 'mint-max-w-wide',
		// Hide default "+ New course" on Settings (students page uses the same pattern).
		'headerActions' => '<span class="mint-hidden" aria-hidden="true"></span>',
	)
);
