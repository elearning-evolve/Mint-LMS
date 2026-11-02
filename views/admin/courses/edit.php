<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Infrastructure\Setup\PageSettings;

/** @var ViewRenderer $renderer */
/** @var int $courseId */

$courseId = isset( $courseId ) ? (int) $courseId : 0;

if ( $courseId <= 0 ) {
	$courseId = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

$pageSettings = new PageSettings();
$previewUrl  = $courseId > 0 ? $pageSettings->getCourseUrl( $courseId ) : '';
if ( '' === $previewUrl || home_url( '/' ) === $previewUrl ) {
	$previewUrl = '';
}

$content = $renderer->render(
	'admin/courses/_edit-content',
	array(
		'renderer' => $renderer,
		'courseId'  => $courseId,
	)
);

$btnSecondary = 'mint-inline-flex mint-h-[38px] mint-items-center mint-justify-center mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-px-[15px] mint-text-[15px] mint-font-semibold mint-text-ink mint-no-underline mint-transition-colors mint-duration-hover hover:mint-bg-[#F4F3F8] focus-visible:mint-outline-none focus-visible:mint-shadow-[0_0_0_3px_rgba(63,0,255,0.32)]';
$btnPrimary   = 'mint-settings-save mint-inline-flex mint-h-[38px] mint-items-center mint-justify-center mint-rounded-md mint-border-0 mint-bg-cta mint-px-4 mint-text-[15px] mint-font-semibold mint-text-cta-ink mint-transition-colors mint-duration-hover focus-visible:mint-outline-none';

$courseStatus   = $courseId > 0 ? (string) get_post_status( $courseId ) : 'draft';
$courseIsLive   = 'publish' === $courseStatus;

$headerActions = '';

if ( '' !== $previewUrl ) {
	$headerActions .= '<a href="' . esc_url( $previewUrl ) . '" class="' . esc_attr( $btnSecondary ) . '" target="_blank" rel="noopener noreferrer">'
		. esc_html__( 'Preview', 'mint-lms' )
		. '</a>';
}

$headerActions .= '<button type="button" class="' . esc_attr( $btnSecondary ) . '" onclick="window.dispatchEvent(new CustomEvent(\'mint-save-course\'))">'
	. esc_html__( 'Save changes', 'mint-lms' )
	. '</button>';

if ( ! $courseIsLive ) {
	$headerActions .= '<button type="button" class="' . esc_attr( $btnPrimary ) . '" onclick="window.dispatchEvent(new CustomEvent(\'mint-publish-course\'))">'
		. esc_html__( 'Live', 'mint-lms' )
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
