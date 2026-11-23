<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View/template variables scoped to this file.

use MintLMS\Infrastructure\Admin\ViewRenderer;

$renderer = new ViewRenderer();

$content = $renderer->render(
	'admin/_ai-assistant-content',
	array(
		'renderer' => $renderer,
	)
);

$renderer->echo(
	'admin/layout',
	array(
		'pageLabel'     => __( 'AI Assistant', 'mint-lms' ),
		'content'       => $content,
		'maxWidthClass' => 'mint-max-w-wide',
		'headerActions' => '<span class="mint-hidden" aria-hidden="true"></span>',
	)
);
