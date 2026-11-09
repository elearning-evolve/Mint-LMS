<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;

/** @var ViewRenderer $renderer */
/** @var int $courseId */

$courseId = isset( $courseId ) ? (int) $courseId : 0;

if ( $courseId <= 0 ) {
	$courseId = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

$content = $renderer->render(
	'admin/courses/_builder-content',
	array(
		'renderer' => $renderer,
		'courseId' => $courseId,
	)
);

$renderer->echo(
	'admin/layout-builder',
	array(
		'content' => $content,
	)
);
