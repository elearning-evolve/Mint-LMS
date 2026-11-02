<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;

$renderer = new ViewRenderer();
$courseId  = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$content = $renderer->render(
	'admin/courses/_students-content',
	array(
		'renderer' => $renderer,
		'courseId'  => $courseId,
	)
);

$btnSecondary = 'mint-inline-flex mint-h-control mint-items-center mint-justify-center mint-rounded-md mint-border mint-border-control mint-bg-bg mint-px-[13px] mint-text-sm mint-font-semibold mint-text-ink hover:mint-bg-bg-subtle';

$headerActions = '<button type="button" class="' . esc_attr( $btnSecondary ) . '" onclick="window.dispatchEvent(new CustomEvent(\'mint-export-students\'))">'
	. esc_html__( 'Export CSV', 'mint-lms' )
	. '</button>';

$renderer->echo(
	'admin/layout',
	array(
		'pageTitle'     => __( 'Students', 'mint-lms' ),
		'pageLabel'     => __( 'Students', 'mint-lms' ),
		'activeNav'     => 'courses',
		'headerActions' => $headerActions,
		'content'       => $content,
	)
);
