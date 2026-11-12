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
	'admin/courses/_edit-content',
	array(
		'renderer' => $renderer,
		'courseId' => $courseId,
	)
);

$courseStatus  = $courseId > 0 ? (string) get_post_status( $courseId ) : 'draft';
$courseIsLive  = 'publish' === $courseStatus;
$headerActions = '<button type="button" class="mint-builder-btn-save" onclick="window.dispatchEvent(new CustomEvent(\'mint-save-course\'))">'
	. esc_html__( 'Save changes', 'mint-lms' )
	. '</button>';

if ( ! $courseIsLive ) {
	$headerActions .= '<button type="button" class="mint-builder-btn-save" onclick="window.dispatchEvent(new CustomEvent(\'mint-publish-course\'))">'
		. esc_html__( 'Published', 'mint-lms' )
		. '</button>';
}

$renderer->echo(
	'admin/layout',
	array(
		'pageTitle'     => __( 'Course', 'mint-lms' ),
		'pageLabel'     => __( 'Course', 'mint-lms' ),
		'activeNav'     => 'courses',
		'headerActions' => $headerActions,
		'maxWidthClass' => 'mint-max-w-wide',
		'content'       => $content,
	)
);
